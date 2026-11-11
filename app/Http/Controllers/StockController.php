<?php

namespace App\Http\Controllers;

use App\Models\Spare;
use App\Models\StockTransaction;
use App\Models\WorkOrder;
use App\Services\StockLedgerService;
use Illuminate\Http\Request;

/**
 * All stock movements. Every method ends in StockLedgerService::record()
 * which locks the spare row, validates the balance and writes the ledger row.
 */
class StockController extends Controller
{
    public function __construct(protected StockLedgerService $ledger)
    {
    }

    public function ledger(Request $request)
    {
        $transactions = StockTransaction::with(['spare', 'user', 'workOrder'])
            ->when($request->spare_id, fn ($q, $v) => $q->where('spare_id', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->work_order_id, fn ($q, $v) => $q->where('work_order_id', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v))
            ->latest('id')->paginate(30)->withQueryString();

        return view('stock.ledger', ['transactions' => $transactions, 'spares' => Spare::orderBy('name')->get(['id', 'code', 'name'])]);
    }

    /** JSON helper for forms: current stock of a spare. */
    public function search(Request $request)
    {
        $spare = Spare::find($request->spare_id);
        return response()->json($spare ? ['stock' => (float) $spare->current_stock, 'uom' => $spare->uom, 'unit_cost' => (float) $spare->unit_cost] : []);
    }

    public function issueForm(Request $request)
    {
        return view('stock.issue', [
            'workOrders' => WorkOrder::with('asset')->whereIn('status', ['assigned', 'in_progress', 'on_hold', 'open'])->latest()->get(),
            'spares'     => Spare::where('is_active', true)->orderBy('name')->get(),
            'selectedWo' => $request->work_order_id,
            'recent'     => StockTransaction::with(['spare', 'workOrder', 'user'])->whereIn('type', ['issue', 'return'])->latest('id')->limit(15)->get(),
        ]);
    }

    public function issue(Request $request)
    {
        $data = $request->validate([
            'work_order_id'    => 'required|exists:pm_work_orders,id',
            'spare_id'         => 'required|exists:pm_spares,id',
            'quantity'         => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'remarks'          => 'nullable|string|max:255',
        ]);
        $txn = $this->ledger->record($data + ['type' => 'issue']);
        flash_toast('success', 'Issued ' . ($txn->quantity + 0) . ' ' . $txn->spare->uom . ' of ' . $txn->spare->name . '. Balance: ' . ($txn->balance_after + 0));
        return back();
    }

    public function return(Request $request)
    {
        $data = $request->validate([
            'work_order_id'    => 'required|exists:pm_work_orders,id',
            'spare_id'         => 'required|exists:pm_spares,id',
            'quantity'         => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'remarks'          => 'nullable|string|max:255',
        ]);
        // Link to the original issue on that work order (for traceability)
        $issue = StockTransaction::where('spare_id', $data['spare_id'])->where('work_order_id', $data['work_order_id'])->where('type', 'issue')->latest('id')->first();
        $txn = $this->ledger->record($data + ['type' => 'return', 'issue_transaction_id' => $issue?->id, 'unit_cost' => $issue?->unit_cost]);
        flash_toast('success', 'Returned ' . ($txn->quantity + 0) . ' ' . $txn->spare->uom . '. Balance: ' . ($txn->balance_after + 0));
        return back();
    }

    public function receive(Request $request)
    {
        $data = $request->validate([
            'spare_id'         => 'required|exists:pm_spares,id',
            'type'             => 'required|in:receipt,opening',
            'quantity'         => 'required|numeric|min:0.01',
            'unit_cost'        => 'nullable|numeric|min:0',
            'transaction_date' => 'required|date',
            'reference_no'     => 'nullable|string|max:100',
            'remarks'          => 'nullable|string|max:255',
        ]);
        $txn = $this->ledger->record($data);
        flash_toast('success', status_label($txn->type) . ' recorded. Balance: ' . ($txn->balance_after + 0));
        return back();
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'spare_id'         => 'required|exists:pm_spares,id',
            'type'             => 'required|in:adjustment_in,adjustment_out',
            'quantity'         => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'reference_no'     => 'nullable|string|max:100',
            'remarks'          => 'required|string|min:3|max:255',
        ]);
        $txn = $this->ledger->record($data);
        flash_toast('success', 'Adjustment recorded. Balance: ' . ($txn->balance_after + 0));
        return back();
    }
}
