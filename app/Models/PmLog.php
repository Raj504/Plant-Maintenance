<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmLog extends Model
{
    use HasAttachments;

    protected $table = 'pm_logs';
    protected $guarded = ['id'];
    protected $casts = ['completed_date' => 'date'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PmPlan::class, 'plan_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PmSchedule::class, 'schedule_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PmLogItem::class, 'log_id');
    }
}
