<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Spare;
use App\Models\Vendor;
use Illuminate\Http\Request;

class SpareController extends Controller
{
    public function index(Request $request)
    {
        $spares = Spare::with(['category', 'preferredVendor'])
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('code', 'like', "%$v%")->orWhere('name', 'like', "%$v%")))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->low, fn ($q) => $q->whereColumn('current_stock', '<=', 'reorder_level'))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('spares.index', ['spares' => $spares, 'categories' => Category::ofType('spare')->get()]);
    }

    public function create()
    {
        return view('spares.form', ['spare' => new Spare(['uom' => 'Nos', 'is_active' => true])] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('pm/spares', 'public');
        }
        $spare = Spare::create($data);
        flash_toast('success', 'Spare ' . $spare->code . ' created. Record opening stock from the ledger.');
        return redirect()->route('spares.show', $spare);
    }

    public function show(Spare $spare)
    {
        $spare->load(['category', 'preferredVendor', 'bomItems.asset', 'purchaseRequests']);
        $transactions = $spare->transactions()->with(['user', 'workOrder'])->paginate(25);
        return view('spares.show', ['spare' => $spare, 'transactions' => $transactions, 'vendors' => Vendor::where('is_active', true)->orderBy('name')->get()]);
    }

    public function edit(Spare $spare)
    {
        return view('spares.form', ['spare' => $spare] + $this->formData());
    }

    public function update(Request $request, Spare $spare)
    {
        $data = $this->validated($request, $spare);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('pm/spares', 'public');
        }
        $spare->update($data);
        flash_toast('success', 'Spare updated.');
        return redirect()->route('spares.show', $spare);
    }

    protected function validated(Request $request, ?Spare $spare = null): array
    {
        $data = $request->validate([
            'code'                => 'required|string|max:50|unique:pm_spares,code,' . ($spare?->id ?? 'NULL') . ',id,deleted_at,NULL',
            'name'                => 'required|string|max:150',
            'category_id'         => 'nullable|exists:pm_categories,id',
            'description'         => 'nullable|string|max:2000',
            'uom'                 => 'required|string|max:20',
            'min_stock'           => 'nullable|numeric|min:0',
            'reorder_level'       => 'nullable|numeric|min:0',
            'unit_cost'           => 'nullable|numeric|min:0',
            'preferred_vendor_id' => 'nullable|exists:pm_vendors,id',
            'store_location'      => 'nullable|string|max:100',
            'remarks'             => 'nullable|string|max:2000',
            'image'               => 'nullable|image|max:4096',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['image']);
        return $data;
    }

    protected function formData(): array
    {
        return ['categories' => Category::ofType('spare')->get(), 'vendors' => Vendor::where('is_active', true)->orderBy('name')->get()];
    }
}
