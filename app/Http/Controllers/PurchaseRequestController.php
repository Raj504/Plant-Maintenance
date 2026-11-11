<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\PurchaseRequest;
use App\Models\Spare;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Services\StockLedgerService;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function __construct(protected StockLedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $prs = PurchaseRequest::with(['spare', 'requester', 'vendor', 'workOrder'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->q, fn ($q, $v) => $q->where('pr_no', 'like', "%$v%"))
            ->latest()->paginate(20)->withQueryString();
        return view('purchase-requests.index', ['prs' => $prs]);
    }

    public function create(Request $request)
    {
        return view('purchase-requests.form', [
            'spares'     => Spare::where('is_active', true)->orderBy('name')->get(),
            'workOrders' => WorkOrder::whereNotIn('status', ['closed', 'cancelled'])->latest()->get(['id', 'wo_no']),
            'requests'   => MaintenanceRequest::whereNotIn('status', ['closed', 'cancelled', 'rejected'])->latest()->get(['id', 'request_no']),
            'vendors'    => Vendor::where('is_active', true)->orderBy('name')->get(),
            'preset'     => $request->only(['spare_id', 'work_order_id', 'request_id', 'required_qty']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'spare_id'         => 'required|exists:pm_spares,id',
            'request_id'       => 'nullable|exists:pm_requests,id',
            'work_order_id'    => 'nullable|exists:pm_work_orders,id',
            'required_qty'     => 'required|numeric|min:0.01',
            'required_by_date' => 'nullable|date',
            'vendor_id'        => 'nullable|exists:pm_vendors,id',
            'remarks'          => 'nullable|string|max:1000',
        ]);
        $pr = $this->ledger->raisePurchaseRequest($data);
        flash_toast('success', 'Purchase requisition ' . $pr->pr_no . ' raised.');
        return redirect()->route('purchase-requests.show', $pr);
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['spare', 'requester', 'vendor', 'workOrder', 'request', 'history.user']);
        return view('purchase-requests.show', ['pr' => $purchaseRequest]);
    }

    /** pending -> approved -> ordered -> received (received also posts a stock receipt) */
    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest)
    {
        $data = $request->validate([
            'status'       => 'required|in:approved,ordered,received,cancelled',
            'remarks'      => 'nullable|string|max:1000',
            'received_qty' => 'required_if:status,received|nullable|numeric|min:0.01',
            'unit_cost'    => 'nullable|numeric|min:0',
        ]);

        $from = $purchaseRequest->status;
        $purchaseRequest->update(['status' => $data['status'], 'remarks' => $data['remarks'] ?? $purchaseRequest->remarks]);
        $purchaseRequest->logHistory('status_changed', $data['remarks'] ?? null, $from, $data['status']);

        if ($data['status'] === 'received') {
            $this->ledger->record([
                'spare_id'         => $purchaseRequest->spare_id,
                'type'             => 'receipt',
                'quantity'         => $data['received_qty'],
                'unit_cost'        => $data['unit_cost'] ?? $purchaseRequest->spare->unit_cost,
                'transaction_date' => today()->toDateString(),
                'reference_no'     => $purchaseRequest->pr_no,
                'work_order_id'    => null,
                'remarks'          => 'Received against ' . $purchaseRequest->pr_no,
            ]);
        }

        flash_toast('success', 'Requisition marked ' . status_label($data['status']) . '.');
        return back();
    }
}
