<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $vendors = Vendor::withCount(['contracts', 'serviceRecords'])->with('serviceRecords')
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('name', 'like', "%$v%")->orWhere('code', 'like', "%$v%")))
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('vendors.form', ['vendor' => new Vendor(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $vendor = Vendor::create($this->validated($request));
        flash_toast('success', 'Vendor created.');
        return redirect()->route('vendors.show', $vendor);
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['contracts.assets', 'serviceRecords.workOrder', 'serviceRecords.contract']);
        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.form', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $vendor->update($this->validated($request, $vendor));
        flash_toast('success', 'Vendor updated.');
        return redirect()->route('vendors.show', $vendor);
    }

    protected function validated(Request $request, ?Vendor $vendor = null): array
    {
        $data = $request->validate([
            'code'           => 'required|string|max:30|unique:pm_vendors,code,' . ($vendor?->id ?? 'NULL'),
            'name'           => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:150',
            'address'        => 'nullable|string|max:1000',
            'gst_number'     => 'nullable|string|max:30',
            'services'       => 'nullable|string|max:255',
            'remarks'        => 'nullable|string|max:2000',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
