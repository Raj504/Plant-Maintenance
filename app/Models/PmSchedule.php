<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmSchedule extends Model
{
    protected $table = 'pm_schedules';
    protected $guarded = ['id'];
    protected $casts = [
        'due_date'     => 'date',
        'generated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PmPlan::class, 'plan_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
