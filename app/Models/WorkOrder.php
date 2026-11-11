<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use SoftDeletes, HasAttachments, HasHistory;

    protected $table = 'pm_work_orders';
    protected $guarded = ['id'];
    protected $casts = [
        'planned_start'   => 'date',
        'planned_end'     => 'date',
        'actual_start'    => 'datetime',
        'actual_end'      => 'datetime',
        'verified_at'     => 'datetime',
        'closed_at'       => 'datetime',
        'permit_required' => 'boolean',
    ];

    public const STATUSES = [
        'open'                 => 'Open',
        'assigned'             => 'Assigned',
        'in_progress'          => 'In Progress',
        'on_hold'              => 'On Hold',
        'completed'            => 'Completed',
        'verification_pending' => 'Verification Pending',
        'closed'               => 'Closed',
        'cancelled'            => 'Cancelled',
    ];

    // ---------------------------------------------------------------- Relations

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'request_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class);
    }

    public function labour(): HasMany
    {
        return $this->hasMany(WorkOrderLabour::class)->latest('work_date');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(WorkOrderVerification::class)->latest('id');
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class)->latest('id');
    }

    public function permits(): HasMany
    {
        return $this->hasMany(SafetyPermit::class);
    }

    public function pmSchedule(): HasOne
    {
        return $this->hasOne(PmSchedule::class);
    }

    public function pmLog(): HasOne
    {
        return $this->hasOne(PmLog::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function vendorServices(): HasMany
    {
        return $this->hasMany(VendorService::class);
    }

    // ---------------------------------------------------------------- Helpers

    public function isOpen(): bool
    {
        return !in_array($this->status, ['closed', 'cancelled']);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->planned_end && $this->planned_end->isPast();
    }

    /** Cost of spares consumed = issued - returned, valued at unit cost. */
    public function partsCost(): float
    {
        $issued   = $this->stockTransactions->where('type', 'issue')->sum(fn ($t) => $t->quantity * $t->unit_cost);
        $returned = $this->stockTransactions->where('type', 'return')->sum(fn ($t) => $t->quantity * $t->unit_cost);
        return round($issued - $returned, 2);
    }

    public function labourCost(): float
    {
        return round($this->labour->sum(fn ($l) => $l->hours * $l->rate), 2);
    }

    /** Permit is required and at least one permit is not yet closed/cancelled. */
    public function hasOpenPermit(): bool
    {
        return $this->permits->whereIn('status', ['draft', 'issued', 'active'])->isNotEmpty();
    }
}
