<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Models\Asset;
use App\Models\AssetBom;
use App\Models\AssetReading;
use App\Models\CapacityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Plant;
use App\Models\Shift;
use App\Models\Spare;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AttachmentService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = Asset::with(['category', 'department', 'parent'])
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('asset_code', 'like', "%$v%")->orWhere('name', 'like', "%$v%")->orWhere('serial_number', 'like', "%$v%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->criticality, fn ($q, $v) => $q->where('criticality', $v))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->orderBy('asset_code')
            ->paginate(20)->withQueryString();

        return view('assets.index', [
            'assets'      => $assets,
            'departments' => Department::orderBy('name')->get(),
            'categories'  => Category::ofType('asset')->get(),
        ]);
    }

    /** Hierarchical tree: Plant > ... parent > children */
    public function tree()
    {
        $roots = Asset::with('children.children.children.children')->whereNull('parent_id')->orderBy('name')->get();
        return view('assets.tree', compact('roots'));
    }

    public function create()
    {
        return view('assets.form', ['asset' => new Asset(['status' => 'active', 'criticality' => 'medium'])] + $this->formData());
    }

    public function store(AssetRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('pm/assets', 'public');
        }

        $asset = Asset::create($data);
        AttachmentService::storeMany($asset, $request->file('attachments'), 'document');
        $asset->logHistory('created', 'Asset registered');

        flash_toast('success', 'Asset ' . $asset->asset_code . ' created.');
        return redirect()->route('assets.show', $asset);
    }

    public function show(Asset $asset)
    {
        $asset->load(['category', 'department', 'plant', 'parent', 'children', 'readings.recorder', 'capacityLogs.shift',
            'requests.requester', 'workOrders.technician', 'pmPlans.technician', 'bomItems.spare', 'attachments.uploader', 'history.user', 'compliances', 'amcContracts.vendor']);

        return view('assets.show', [
            'asset'    => $asset,
            'spares'   => Spare::where('is_active', true)->orderBy('name')->get(),
            'vendors'  => Vendor::where('is_active', true)->orderBy('name')->get(),
            'shifts'   => Shift::where('is_active', true)->get(),
            'users'    => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** QR code scan target: /assets/scan/{code} -> asset page */
    public function scan(string $code)
    {
        $asset = Asset::where('asset_code', $code)->firstOrFail();
        return redirect()->route('assets.show', $asset);
    }

    public function edit(Asset $asset)
    {
        return view('assets.form', ['asset' => $asset] + $this->formData());
    }

    public function update(AssetRequest $request, Asset $asset)
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('pm/assets', 'public');
        }

        $old = $asset->only(['status', 'criticality', 'department_id', 'parent_id', 'current_running_hours', 'rated_capacity']);
        $asset->update($data);
        AttachmentService::storeMany($asset, $request->file('attachments'), 'document');

        $changes = [];
        foreach ($old as $k => $v) {
            if ((string) $v !== (string) $asset->$k) {
                $changes[$k] = ['old' => $v, 'new' => $asset->$k];
            }
        }
        $asset->logHistory('updated', 'Asset details updated', $old['status'] !== $asset->status ? $old['status'] : null, $old['status'] !== $asset->status ? $asset->status : null, $changes ?: null);

        flash_toast('success', 'Asset updated.');
        return redirect()->route('assets.show', $asset);
    }

    public function destroy(Asset $asset)
    {
        if ($asset->children()->exists() || $asset->requests()->exists() || $asset->workOrders()->exists()) {
            flash_toast('error', 'Asset has child assets or maintenance history. Decommission it instead of deleting.');
            return back();
        }
        $asset->delete();
        flash_toast('success', 'Asset deleted.');
        return redirect()->route('assets.index');
    }

    // ------------------------------------------------------------ Documents

    public function storeDocument(Request $request, Asset $asset)
    {
        $request->validate([
            'category'      => 'required|string|max:50',
            'attachments'   => 'required|array|min:1',
            'attachments.*' => AttachmentService::rule(),
        ]);
        AttachmentService::storeMany($asset, $request->file('attachments'), $request->category);
        flash_toast('success', 'Document(s) uploaded.');
        return back();
    }

    // ------------------------------------------------------------ Running hours

    public function storeReading(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'reading_date' => 'required|date',
            'reading_type' => 'required|in:running_hours,meter',
            'value'        => 'required|numeric|min:0',
            'remarks'      => 'nullable|string|max:255',
        ]);

        if ($data['reading_type'] === 'running_hours' && $data['value'] < $asset->current_running_hours) {
            flash_toast('error', 'Running hours cannot be lower than the current value (' . $asset->current_running_hours . '). Use an adjustment remark.');
            return back();
        }

        AssetReading::create($data + ['asset_id' => $asset->id, 'recorded_by' => auth()->id()]);

        if ($data['reading_type'] === 'running_hours') {
            $asset->update(['current_running_hours' => $data['value']]);
            $asset->logHistory('reading', 'Running hours updated to ' . $data['value']);
        }

        flash_toast('success', 'Reading recorded.');
        return back();
    }

    // ------------------------------------------------------------ Capacity / production log

    public function storeCapacityLog(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'log_date'        => 'required|date',
            'shift_id'        => 'nullable|exists:pm_shifts,id',
            'hours_run'       => 'required|numeric|min:0.1|max:24',
            'output_quantity' => 'required|numeric|min:0',
            'operator_id'     => 'nullable|exists:users,id',
            'remarks'         => 'nullable|string|max:255',
        ]);

        CapacityLog::create($data + ['asset_id' => $asset->id, 'unit' => $asset->capacity_unit, 'created_by' => auth()->id()]);
        flash_toast('success', 'Production log recorded.');
        return back();
    }

    // ------------------------------------------------------------ BOM

    public function storeBom(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'spare_id'                  => 'required|exists:pm_spares,id',
            'quantity'                  => 'required|numeric|min:0.01',
            'criticality'               => 'required|in:critical,high,medium,low',
            'preferred_vendor_id'       => 'nullable|exists:pm_vendors,id',
            'replacement_interval_days' => 'nullable|integer|min:1',
            'remarks'                   => 'nullable|string|max:255',
        ]);
        $spare = Spare::find($data['spare_id']);

        AssetBom::updateOrCreate(['asset_id' => $asset->id, 'spare_id' => $data['spare_id']], $data + ['unit' => $spare->uom]);
        flash_toast('success', 'Spare added to BOM.');
        return back();
    }

    public function destroyBom(Asset $asset, AssetBom $bom)
    {
        abort_unless($bom->asset_id === $asset->id, 404);
        $bom->delete();
        flash_toast('success', 'Removed from BOM.');
        return back();
    }

    // ------------------------------------------------------------ Helpers

    protected function formData(): array
    {
        return [
            'categories'  => Category::ofType('asset')->get(),
            'departments' => Department::orderBy('name')->get(),
            'plants'      => Plant::orderBy('name')->get(),
            'parents'     => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name']),
        ];
    }
}
