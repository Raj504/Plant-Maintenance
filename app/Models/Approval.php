<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every approval decision is a new row. Rows are never updated or deleted,
 * so the full decision history is always available.
 */
class Approval extends Model
{
    protected $table = 'pm_approvals';
    protected $guarded = ['id'];
    protected $casts = ['acted_at' => 'datetime'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'request_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
