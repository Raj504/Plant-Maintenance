<?php

namespace App\Models;

use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmPlan extends Model
{
    use SoftDeletes, HasHistory;

    protected $table = 'pm_plans';
    protected $guarded = ['id'];
    protected $casts = [
        'last_done_date' => 'date',
        'next_due_date'  => 'date',
        'is_active'      => 'boolean',
    ];

    public const TRIGGERS = [
        'calendar'      => 'Calendar Based',
        'running_hours' => 'Running Hours Based',
        'both'          => 'Calendar + Running Hours (whichever first)',
    ];

    /** Frequency label => days */
    public const FREQUENCIES = [
        'daily'       => 1,
        'weekly'      => 7,
        'monthly'     => 30,
        'quarterly'   => 90,
        'half_yearly' => 180,
        'yearly'      => 365,
        'custom'      => null,
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'component_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PmSchedule::class, 'plan_id')->latest('id');
    }

    public function openSchedule(): HasOne
    {
        return $this->hasOne(PmSchedule::class, 'plan_id')->whereIn('status', ['due', 'in_progress'])->latestOfMany();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PmLog::class, 'plan_id')->latest('completed_date');
    }

    public function usesCalendar(): bool
    {
        return in_array($this->trigger_type, ['calendar', 'both']);
    }

    public function usesRunningHours(): bool
    {
        return in_array($this->trigger_type, ['running_hours', 'both']);
    }

    /** Is this plan due today (by date or by running hours)? Returns reason or null. */
    public function dueReason(): ?string
    {
        if ($this->usesCalendar() && $this->next_due_date && $this->next_due_date->lte(now()->startOfDay())) {
            return 'calendar';
        }
        if ($this->usesRunningHours() && $this->next_due_hours !== null
            && $this->asset && $this->asset->current_running_hours >= $this->next_due_hours) {
            return 'running_hours';
        }
        return null;
    }

    public function isOverdue(): bool
    {
        return $this->dueReason() !== null;
    }
}
