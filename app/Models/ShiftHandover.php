<?php

namespace App\Models;

use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftHandover extends Model
{
    use HasHistory;

    protected $table = 'pm_shift_handovers';
    protected $guarded = ['id'];
    protected $casts = ['handover_date' => 'date', 'acknowledged_at' => 'datetime'];

    public function fromShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'from_shift_id');
    }

    public function toShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'to_shift_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
