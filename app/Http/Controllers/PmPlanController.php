<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\PmPlan;
use App\Models\Team;
use App\Models\User;
use App\Services\PreventiveMaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PmPlanController extends Controller
{
    public function __construct(protected PreventiveMaintenanceService $service)
    {
    }

    public function index(Request $request)
    {
        $plans = PmPlan::with(['asset', 'component', 'technician', 'team', 'openSchedule'])
            ->when($request->q, fn ($q, $v) => $q->where(fn ($s) => $s->where('name', 'like', "%$v%")->orWhere('plan_no', 'like', "%$v%")))
            ->when($request->asset_id, fn ($q, $v) => $q->where('asset_id', $v))
            ->when($request->trigger_type, fn ($q, $v) => $q->where('trigger_type', $v))
            ->when($request->due === 'overdue', fn ($q) => $q->where('is_active', true)->whereDate('next_due_date', '<', today()))
            ->when($request->due === 'upcoming', fn ($q) => $q->where('is_active', true)->whereBetween('next_due_date', [today(), today()->addDays(7)]))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('next_due_date')
            ->paginate(20)->withQueryString();

        return view('pm.plans.index', ['plans' => $plans, 'assets' => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name'])]);
    }

    public function create(Request $request)
    {
        return view('pm.plans.form', ['plan' => new PmPlan(['asset_id' => $request->asset_id, 'trigger_type' => 'calendar', 'frequency' => 'monthly', 'is_active' => true])] + $this->formData());
    }

    public function store(Request $request)
    {
        $plan = $this->service->createPlan($this->validated($request));
        flash_toast('success', 'PM plan ' . $plan->plan_no . ' created. Next due ' . show_date($plan->next_due_date) . '.');
        return redirect()->route('pm.plans.show', $plan);
    }

    public function show(PmPlan $plan)
    {
        $plan->load(['asset', 'component', 'category', 'team', 'technician', 'checklist.items', 'schedules.workOrder', 'logs.completer', 'logs.items', 'history.user']);
        return view('pm.plans.show', compact('plan'));
    }

    public function edit(PmPlan $plan)
    {
        return view('pm.plans.form', ['plan' => $plan] + $this->formData());
    }

    public function update(Request $request, PmPlan $plan)
    {
        $this->service->updatePlan($plan, $this->validated($request));
        flash_toast('success', 'PM plan updated.');
        return redirect()->route('pm.plans.show', $plan);
    }

    /** Generate the schedule + work order right now, without waiting for the scheduler. */
    public function generate(PmPlan $plan)
    {
        $schedule = $this->service->generateForPlan($plan);
        if (!$schedule) {
            flash_toast('warning', 'This plan already has an open schedule / work order.');
        } else {
            flash_toast('success', 'Work order ' . $schedule->workOrder->wo_no . ' generated.');
        }
        return back();
    }

    public function toggle(PmPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        $plan->logHistory($plan->is_active ? 'activated' : 'deactivated');
        flash_toast('success', 'Plan ' . ($plan->is_active ? 'activated' : 'deactivated') . '.');
        return back();
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'asset_id'                 => 'required|exists:pm_assets,id',
            'component_id'             => 'nullable|exists:pm_assets,id',
            'name'                     => 'required|string|max:150',
            'category_id'              => 'nullable|exists:pm_categories,id',
            'trigger_type'             => ['required', Rule::in(array_keys(PmPlan::TRIGGERS))],
            'frequency'                => ['nullable', Rule::in(array_keys(PmPlan::FREQUENCIES))],
            'interval_days'            => 'nullable|integer|min:1|required_if:frequency,custom',
            'interval_hours'           => 'nullable|numeric|min:1|required_if:trigger_type,running_hours,both',
            'last_done_date'           => 'nullable|date',
            'next_due_date'            => 'nullable|date',
            'last_done_hours'          => 'nullable|numeric|min:0',
            'next_due_hours'           => 'nullable|numeric|min:0',
            'team_id'                  => 'nullable|exists:pm_teams,id',
            'assigned_to'              => 'nullable|exists:users,id',
            'checklist_id'             => 'nullable|exists:pm_checklists,id',
            'estimated_duration_hours' => 'nullable|numeric|min:0',
            'estimated_cost'           => 'nullable|numeric|min:0',
            'remarks'                  => 'nullable|string|max:2000',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        if (in_array($data['trigger_type'], ['calendar', 'both']) && empty($data['frequency'])) {
            $data['frequency'] = 'custom';
        }
        return $data;
    }

    protected function formData(): array
    {
        return [
            'assets'     => Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name', 'parent_id', 'current_running_hours']),
            'categories' => Category::ofType('maintenance')->get(),
            'teams'      => Team::where('is_active', true)->get(),
            'users'      => User::where('is_active', true)->orderBy('name')->get(),
            'checklists' => Checklist::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
