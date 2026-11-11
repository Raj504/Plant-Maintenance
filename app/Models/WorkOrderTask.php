<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderTask extends Model
{
    protected $table = 'pm_work_order_tasks';
    protected $guarded = ['id'];
    protected $casts = ['is_done' => 'boolean', 'done_at' => 'datetime'];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
