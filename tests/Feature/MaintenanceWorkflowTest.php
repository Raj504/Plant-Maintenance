<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\PmPlan;
use App\Models\SafetyPermit;
use App\Models\Spare;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end business rules:
 *  - sequential approval workflow generates a work order and closes the request
 *  - stock cannot go negative, returns are capped at what was issued
 *  - a permit-required work order cannot start without an active permit
 *  - PM completion rolls the next due date / hours forward
 *  - permissions are enforced on the server
 *
 *   php artisan test --filter=MaintenanceWorkflowTest
 */
class MaintenanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_full_request_to_closure_workflow(): void
    {
        $asset = Asset::where('asset_code', 'GRD-01')->firstOrFail();
        $spare = Spare::where('code', 'SP-004')->firstOrFail(); // 12 in stock

        // 1. Employee raises a request
        $this->actingAs($this->user('employee@plant.com'))->post('/requests', [
            'asset_id' => $asset->id, 'request_type' => 'breakdown', 'priority' => 'high',
            'problem_description' => 'Grinder bearing making loud noise and vibration.', 'machine_stopped' => 1,
        ])->assertRedirect();
        $request = MaintenanceRequest::latest('id')->first();
        $this->assertSame('submitted', $request->status);
        $this->assertSame('breakdown', $asset->fresh()->status, 'Machine stopped should mark the asset breakdown');

        // Employee cannot inspect (server-side permission)
        $this->actingAs($this->user('employee@plant.com'))->post("/requests/{$request->id}/inspection", [])->assertForbidden();

        // 2. Inspector
        $this->actingAs($this->user('inspector@plant.com'))->post("/requests/{$request->id}/inspection", [
            'inspection_date' => now()->format('Y-m-d H:i'), 'actual_problem' => 'Bearing worn', 'safety_risk' => 'low',
            'production_impact' => 'full', 'recommendation' => 'proceed',
        ])->assertRedirect();
        $this->assertSame('assessment_pending', $request->fresh()->status);

        // 3. Technical expert (approval cannot happen before this step)
        $this->actingAs($this->user('approver@plant.com'))->post("/requests/{$request->id}/approval", ['action' => 'approved', 'remarks' => 'too early'])->assertSessionHasErrors();

        $this->actingAs($this->user('expert@plant.com'))->post("/requests/{$request->id}/assessment", [
            'assessment_date' => now()->format('Y-m-d H:i'), 'failure_nature' => 'Bearing failure', 'action_required' => 'replace',
            'estimated_labour_cost' => 1000, 'estimated_material_cost' => 1300, 'estimated_service_cost' => 0,
            'spares' => [['spare_id' => $spare->id, 'quantity' => 2, 'estimated_cost' => 1300]],
        ])->assertRedirect();
        $request->refresh();
        $this->assertSame('approval_pending', $request->status);
        $this->assertEquals(2300, $request->estimated_cost);

        // 4. Send back, re-submit, then approve
        $this->actingAs($this->user('approver@plant.com'))->post("/requests/{$request->id}/approval", ['action' => 'sent_back', 'remarks' => 'Please confirm bearing size'])->assertRedirect();
        $this->assertSame('sent_back', $request->fresh()->status);
        $this->actingAs($this->user('employee@plant.com'))->post("/requests/{$request->id}/resubmit", ['remarks' => 'Confirmed 6310'])->assertRedirect();
        $this->assertSame('approval_pending', $request->fresh()->status);

        $this->actingAs($this->user('approver@plant.com'))->post("/requests/{$request->id}/approval", ['action' => 'approved', 'approved_cost' => 2200, 'remarks' => 'Approved'])->assertRedirect();
        $request->refresh();
        $this->assertSame('work_order_created', $request->status);
        $this->assertEquals(2200, $request->approved_cost);
        $this->assertCount(2, $request->approvals, 'Both decisions must be kept');

        // 5. Work order auto-generated
        $wo = $request->workOrder;
        $this->assertNotNull($wo);
        $this->assertSame('open', $wo->status);
        $this->assertEquals(2200, $wo->estimated_cost);

        // 6. Supervisor assigns, technician starts
        $tech = $this->user('technician@plant.com');
        $this->actingAs($this->user('supervisor@plant.com'))->post("/work-orders/{$wo->id}/assign", [
            'assigned_to' => $tech->id, 'planned_start' => today()->toDateString(), 'planned_end' => today()->addDay()->toDateString(),
        ])->assertRedirect();
        $this->assertSame('assigned', $wo->fresh()->status);
        $this->actingAs($tech)->post("/work-orders/{$wo->id}/start")->assertRedirect();
        $this->assertSame('in_progress', $wo->fresh()->status);

        // 7. Store issues spares: over-issue blocked, normal issue ok, over-return blocked
        $this->actingAs($this->user('store@plant.com'))->post('/stock/issue', ['work_order_id' => $wo->id, 'spare_id' => $spare->id, 'quantity' => 999, 'transaction_date' => today()->toDateString()])->assertSessionHasErrors('quantity');
        $this->actingAs($this->user('store@plant.com'))->post('/stock/issue', ['work_order_id' => $wo->id, 'spare_id' => $spare->id, 'quantity' => 2, 'transaction_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertEquals(10, $spare->fresh()->current_stock);
        $this->actingAs($this->user('store@plant.com'))->post('/stock/return', ['work_order_id' => $wo->id, 'spare_id' => $spare->id, 'quantity' => 5, 'transaction_date' => today()->toDateString()])->assertSessionHasErrors('quantity');
        $this->actingAs($this->user('store@plant.com'))->post('/stock/return', ['work_order_id' => $wo->id, 'spare_id' => $spare->id, 'quantity' => 1, 'transaction_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertEquals(11, $spare->fresh()->current_stock);

        // 8. Technician records labour and completes
        $this->actingAs($tech)->post("/work-orders/{$wo->id}/labour", ['user_id' => $tech->id, 'work_date' => today()->toDateString(), 'hours' => 3, 'rate' => 200])->assertRedirect();
        $this->actingAs($tech)->post("/work-orders/{$wo->id}/complete", [
            'actual_problem_found' => 'Bearing seized', 'work_performed' => 'Replaced bearing', 'downtime_hours' => 4, 'other_cost' => 100,
        ])->assertRedirect();
        $wo->refresh();
        $this->assertSame('verification_pending', $wo->status);
        $this->assertEquals(650 + 600 + 100, $wo->actual_cost, 'parts (1 x 650) + labour (3 x 200) + other');

        // Verification must confirm all checks
        $this->actingAs($this->user('supervisor@plant.com'))->post("/work-orders/{$wo->id}/verify", ['decision' => 'approved', 'remarks' => 'ok'])->assertRedirect();
        $this->assertSame('verification_pending', $wo->fresh()->status, 'Cannot close without confirming operation/safety/quality');

        // 9. Supervisor verifies & closes -> request closed, asset active
        $this->actingAs($this->user('supervisor@plant.com'))->post("/work-orders/{$wo->id}/verify", [
            'decision' => 'approved', 'machine_operation_ok' => 1, 'safety_ok' => 1, 'repair_quality_ok' => 1, 'production_ready' => 1,
            'remarks' => 'Verified OK', 'final_machine_status' => 'active',
        ])->assertRedirect();
        $wo->refresh();
        $this->assertSame('closed', $wo->status);
        $this->assertSame('closed', $request->fresh()->status);
        $this->assertSame('active', $asset->fresh()->status);
        $this->assertTrue($wo->history->where('action', 'closed')->isNotEmpty(), 'Closure must be in the audit trail');
    }

    public function test_permit_required_work_order_cannot_start_without_active_permit(): void
    {
        $wo = WorkOrder::where('wo_no', 'WO-2026-0001')->firstOrFail(); // seeded with permit_required = true
        $this->assertTrue($wo->permit_required);
        $tech = $this->user('technician@plant.com');

        $this->actingAs($this->user('supervisor@plant.com'))->post("/work-orders/{$wo->id}/assign", ['assigned_to' => $tech->id, 'planned_start' => today()->toDateString(), 'planned_end' => today()->addDay()->toDateString()]);
        $this->actingAs($tech)->post("/work-orders/{$wo->id}/start")->assertSessionHasErrors('permit');
        $this->assertSame('assigned', $wo->fresh()->status);

        // Create permit with LOTO, submit, approve
        $this->actingAs($this->user('safety@plant.com'))->post('/permits', [
            'work_order_id' => $wo->id, 'asset_id' => $wo->asset_id, 'work_description' => 'Replace contactor', 'permit_type' => 'electrical',
            'valid_from' => now()->format('Y-m-d H:i'), 'valid_until' => now()->addHours(8)->format('Y-m-d H:i'),
        ])->assertRedirect();
        $permit = SafetyPermit::latest('id')->first();
        $this->actingAs($this->user('safety@plant.com'))->post("/permits/{$permit->id}/loto", ['energy_source' => 'Electrical', 'isolation_point' => 'MCC-1 F7', 'lock_number' => 'L-12', 'isolated_at' => now()->format('Y-m-d H:i'), 'isolated_by' => $tech->id]);
        $this->actingAs($this->user('safety@plant.com'))->post("/permits/{$permit->id}/issue");
        $this->actingAs($this->user('safety@plant.com'))->post("/permits/{$permit->id}/approve");
        $this->assertSame('active', $permit->fresh()->status);

        // Now work can start
        $this->actingAs($tech)->post("/work-orders/{$wo->id}/start")->assertSessionHasNoErrors();
        $this->assertSame('in_progress', $wo->fresh()->status);

        // Permit cannot close while isolation is in place
        $this->actingAs($this->user('safety@plant.com'))->post("/permits/{$permit->id}/close", ['closure_remarks' => 'done']);
        $this->assertSame('active', $permit->fresh()->status);
        $loto = $permit->lotoRecords()->first();
        $this->actingAs($this->user('safety@plant.com'))->post("/permits/{$permit->id}/loto/{$loto->id}/deisolate", ['deisolated_at' => now()->format('Y-m-d H:i'), 'deisolated_by' => $tech->id]);
        $this->actingAs($this->user('safety@plant.com'))->post("/permits/{$permit->id}/close", ['closure_remarks' => 'Area cleared']);
        $this->assertSame('closed', $permit->fresh()->status);
    }

    public function test_pm_completion_calculates_next_due_and_scheduler_is_idempotent(): void
    {
        $plan = PmPlan::where('trigger_type', 'both')->firstOrFail(); // compressor: 30 days / 500 hrs
        $service = app(\App\Services\PreventiveMaintenanceService::class);

        // Clear any other seeded plans that are already due, then force this plan overdue by date
        $service->generateDueSchedules();
        $plan->update(['next_due_date' => today()->subDay()]);
        $this->assertSame(1, $service->generateDueSchedules());
        $this->assertSame(0, $service->generateDueSchedules(), 'Second run must not duplicate');
        $schedule = $plan->fresh()->openSchedule;
        $this->assertNotNull($schedule);
        $this->assertNotNull($schedule->workOrder);

        // Technician completes the checklist
        $this->actingAs($this->user('technician@plant.com'))->post("/pm/schedules/{$schedule->id}/complete", [
            'completed_date' => today()->toDateString(), 'running_hours_at_completion' => 12500, 'actual_cost' => 5000,
            'items' => [['item_text' => 'Check oil level', 'result' => 'ok'], ['item_text' => 'Replace oil filter', 'result' => 'not_ok', 'remarks' => 'Filter not in stock']],
        ])->assertRedirect();

        $plan->refresh();
        $this->assertSame('completed', $schedule->fresh()->status);
        $this->assertTrue($plan->next_due_date->isSameDay(today()->addDays(30)));
        $this->assertEquals(13000, $plan->next_due_hours);
        $this->assertEquals(12500, $plan->asset->fresh()->current_running_hours);
        $this->assertSame('verification_pending', $schedule->workOrder->fresh()->status);
        $this->assertCount(2, $plan->logs->first()->items);
    }

    public function test_emergency_bypass_creates_work_order_and_keeps_audit(): void
    {
        $request = MaintenanceRequest::where('status', 'submitted')->firstOrFail();
        $this->actingAs($this->user('technician@plant.com'))->post("/requests/{$request->id}/emergency", ['emergency_reason' => 'Fire risk from motor'])->assertForbidden();
        $this->actingAs($this->user('approver@plant.com'))->post("/requests/{$request->id}/emergency", ['emergency_reason' => 'Fire risk from overheating motor, cannot wait'])->assertRedirect();
        $request->refresh();
        $this->assertTrue($request->is_emergency);
        $this->assertSame('work_order_created', $request->status);
        $this->assertSame('emergency', $request->workOrder->source);
        $this->assertTrue($request->history->where('action', 'emergency_approved')->isNotEmpty());
    }

    public function test_employee_only_sees_own_requests_and_cannot_reach_admin(): void
    {
        $employee = $this->user('employee@plant.com');
        $other = MaintenanceRequest::where('requested_by', '!=', $employee->id)->first()
            ?? tap(MaintenanceRequest::first())->update(['requested_by' => $this->user('operator@plant.com')->id]);

        $this->actingAs($employee)->get('/admin/users')->assertForbidden();
        $this->actingAs($employee)->get('/spares')->assertForbidden();
        $this->actingAs($employee)->get('/requests/' . $other->id)->assertForbidden();
        $this->actingAs($employee)->get('/requests/create')->assertOk();
    }
}
