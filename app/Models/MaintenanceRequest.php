<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends Model
{
    use SoftDeletes, HasAttachments, HasHistory;

    protected $table = 'pm_requests';
    protected $guarded = ['id'];
    protected $casts = [
        'request_date'          => 'datetime',
        'machine_stopped'       => 'boolean',
        'production_affected'   => 'boolean',
        'is_emergency'          => 'boolean',
        'emergency_reviewed'    => 'boolean',
        'emergency_approved_at' => 'datetime',
        'closed_at'             => 'datetime',
    ];

    public const TYPES = [
        'breakdown'   => 'Breakdown',
        'corrective'  => 'Corrective Maintenance',
        'preventive'  => 'Preventive Maintenance',
        'improvement' => 'Improvement',
        'inspection'  => 'Inspection',
        'replacement' => 'Replacement',
        'electrical'  => 'Electrical',
        'mechanical'  => 'Mechanical',
        'utility'     => 'Utility Maintenance',
    ];

    public const PRIORITIES = [
        'critical' => 'Critical',
        'high'     => 'High',
        'medium'   => 'Medium',
        'low'      => 'Low',
    ];

    public const STATUSES = [
        'draft'              => 'Draft',
        'submitted'          => 'Submitted',
        'inspection_pending' => 'Inspection Pending',
        'assessment_pending' => 'Technical Assessment Pending',
        'approval_pending'   => 'Budget Approval Pending',
        'sent_back'          => 'Sent Back for Clarification',
        'approved'           => 'Approved',
        'rejected'           => 'Rejected',
        'work_order_created' => 'Work Order Created',
        'in_progress'        => 'In Progress',
        'verification_pending' => 'Verification Pending',
        'closed'             => 'Closed',
        'cancelled'          => 'Cancelled',
    ];

    // ---------------------------------------------------------------- Relations

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'component_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function problemCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'problem_category_id');
    }

    public function emergencyApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emergency_approved_by');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(RequestInspection::class, 'request_id')->latest('id');
    }

    public function latestInspection(): HasOne
    {
        return $this->hasOne(RequestInspection::class, 'request_id')->latestOfMany();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(TechnicalAssessment::class, 'request_id')->latest('id');
    }

    public function latestAssessment(): HasOne
    {
        return $this->hasOne(TechnicalAssessment::class, 'request_id')->latestOfMany();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'request_id')->latest('id');
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class, 'request_id')->latestOfMany();
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'request_id');
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class, 'request_id');
    }

    // ---------------------------------------------------------------- Helpers

    public function isOpen(): bool
    {
        return !in_array($this->status, ['closed', 'cancelled', 'rejected']);
    }

    public function canBeEditedBy(User $user): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'sent_back'])
            && ($this->requested_by === $user->id || $user->hasPermission('pm.requests.manage'));
    }
}
