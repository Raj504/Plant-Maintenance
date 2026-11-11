<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderVerification extends Model
{
    protected $table = 'pm_work_order_verifications';
    protected $guarded = ['id'];
    protected $casts = [
        'verification_date'    => 'datetime',
        'machine_operation_ok' => 'boolean',
        'safety_ok'            => 'boolean',
        'repair_quality_ok'    => 'boolean',
        'production_ready'     => 'boolean',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
