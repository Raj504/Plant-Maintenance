<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use App\Models\Spare;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock ledger. ALL stock movements go through here.
 *
 * - Rows are inserted, never edited or deleted.
 * - The spare row is locked (SELECT ... FOR UPDATE) while we check and move stock,
 *   so two technicians cannot both issue the last item at the same time.
 * - current_stock on pm_spares is recalculated from the ledger after every movement.
 */
class StockLedgerService
{
    public function record(array $data): StockTransaction
    {
        return DB::transaction(function () use ($data) {
            $spare = Spare::where('id', $data['spare_id'])->lockForUpdate()->firstOrFail();

            $type     = $data['type'];
            $quantity = (float) $data['quantity'];
            if ($quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => 'Quantity must be greater than zero.']);
            }

            $isInward = in_array($type, StockTransaction::IN_TYPES);
            $balance  = $this->balanceFromLedger($spare->id);

            if (!$isInward && $quantity > $balance) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$balance} {$spare->uom} available in stock. Cannot issue {$quantity}.",
                ]);
            }

            // Returns cannot exceed what was issued on that work order
            if ($type === 'return' && !empty($data['work_order_id'])) {
                $issued   = StockTransaction::where('spare_id', $spare->id)->where('work_order_id', $data['work_order_id'])->where('type', 'issue')->sum('quantity');
                $returned = StockTransaction::where('spare_id', $spare->id)->where('work_order_id', $data['work_order_id'])->where('type', 'return')->sum('quantity');
                if ($quantity > ($issued - $returned)) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Return quantity exceeds the quantity issued on this work order (' . ($issued - $returned) . ').',
                    ]);
                }
            }

            $newBalance = $isInward ? $balance + $quantity : $balance - $quantity;

            $transaction = StockTransaction::create([
                'txn_no'               => DocumentNumberService::next('stock'),
                'spare_id'             => $spare->id,
                'type'                 => $type,
                'quantity'             => $quantity,
                'unit_cost'            => $data['unit_cost'] ?? $spare->unit_cost,
                'transaction_date'     => $data['transaction_date'] ?? now()->toDateString(),
                'reference_no'         => $data['reference_no'] ?? null,
                'work_order_id'        => $data['work_order_id'] ?? null,
                'issue_transaction_id' => $data['issue_transaction_id'] ?? null,
                'balance_after'        => $newBalance,
                'user_id'              => auth()->id(),
                'remarks'              => $data['remarks'] ?? null,
            ]);

            $spare->update(['current_stock' => $newBalance]);

            // Receipt updates the last known unit cost
            if ($type === 'receipt' && !empty($data['unit_cost'])) {
                $spare->update(['unit_cost' => $data['unit_cost']]);
            }

            if ($spare->isLowStock()) {
                NotificationService::notifyPermission('pm.stock.view', 'low_stock',
                    'Low stock: ' . $spare->name,
                    'Current stock ' . $newBalance . ' ' . $spare->uom . ' (reorder level ' . $spare->reorder_level . ')',
                    route('spares.show', $spare), 'warning', 'low_stock:' . $spare->id . ':' . now()->format('Ymd'));
            }

            return $transaction;
        });
    }

    /** Sum of the ledger: inward - outward. This is the source of truth. */
    public function balanceFromLedger(int $spareId): float
    {
        $in  = StockTransaction::where('spare_id', $spareId)->whereIn('type', StockTransaction::IN_TYPES)->sum('quantity');
        $out = StockTransaction::where('spare_id', $spareId)->whereNotIn('type', StockTransaction::IN_TYPES)->sum('quantity');
        return (float) ($in - $out);
    }

    /** Re-sync the cached column for one or all spares (used by the scheduler / admin). */
    public function recalculate(?Spare $spare = null): void
    {
        $spares = $spare ? collect([$spare]) : Spare::all();
        foreach ($spares as $item) {
            $item->update(['current_stock' => $this->balanceFromLedger($item->id)]);
        }
    }

    /** Raise a purchase requisition for the shortage. */
    public function raisePurchaseRequest(array $data): PurchaseRequest
    {
        $spare = Spare::findOrFail($data['spare_id']);
        $available = $this->balanceFromLedger($spare->id);
        $required  = (float) $data['required_qty'];

        $pr = PurchaseRequest::create([
            'pr_no'            => DocumentNumberService::next('purchase'),
            'spare_id'         => $spare->id,
            'request_id'       => $data['request_id'] ?? null,
            'work_order_id'    => $data['work_order_id'] ?? null,
            'required_qty'     => $required,
            'available_stock'  => $available,
            'shortage_qty'     => max(0, $required - $available),
            'required_by_date' => $data['required_by_date'] ?? null,
            'vendor_id'        => $data['vendor_id'] ?? $spare->preferred_vendor_id,
            'status'           => 'pending',
            'requested_by'     => auth()->id(),
            'remarks'          => $data['remarks'] ?? null,
        ]);
        $pr->logHistory('created', 'Purchase requisition raised', null, 'pending');

        NotificationService::notifyPermission('pm.purchase.manage', 'purchase_pending',
            'Purchase requisition ' . $pr->pr_no . ' pending', $spare->name . ' x ' . $required, route('purchase-requests.show', $pr));

        return $pr;
    }
}
