<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyPermit extends Model
{
    use HasAttachments, HasHistory;

    protected $table = 'pm_safety_permits';
    protected $guarded = ['id'];
    protected $casts = [
        'valid_from'  => 'datetime',
        'valid_until' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    public const TYPES = [
        'electrical'      => 'Electrical Work',
        'hot_work'        => 'Hot Work',
        'height'          => 'Work at Height',
        'confined_space'  => 'Confined Space Entry',
        'heavy_equipment' => 'Heavy Equipment Work',
        'hazardous_area'  => 'Hazardous Area Maintenance',
        'general'         => 'General',
    ];

    public const STATUSES = [
        'draft'     => 'Draft',
        'issued'    => 'Issued (awaiting approval)',
        'active'    => 'Active',
        'closed'    => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function lotoRecords(): HasMany
    {
        return $this->hasMany(LotoRecord::class, 'permit_id');
    }

    /** Permit may close only when every isolation has been removed again. */
    public function hasPendingIsolation(): bool
    {
        return $this->lotoRecords->whereNull('deisolated_at')->isNotEmpty();
    }
}
