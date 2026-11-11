<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One production run of a machine. Used by the Machine Capacity report:
 *   rated per hour  = rated_capacity / capacity_hours   (200 kg / 8 h = 25 kg/h)
 *   expected output = rated per hour * hours_run        (25 * 5 = 125 kg)
 *   actual per hour = output_quantity / hours_run       (100 / 5 = 20 kg/h)
 *   efficiency      = output_quantity / expected * 100  (100 / 125 = 80 %)
 */
class CapacityLog extends Model
{
    protected $table = 'pm_capacity_logs';
    protected $guarded = ['id'];
    protected $casts = ['log_date' => 'date'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function expectedOutput(): ?float
    {
        $perHour = $this->asset?->ratedPerHour();
        return $perHour === null ? null : round($perHour * $this->hours_run, 2);
    }

    public function actualPerHour(): float
    {
        return $this->hours_run > 0 ? round($this->output_quantity / $this->hours_run, 2) : 0;
    }

    public function efficiency(): ?float
    {
        $expected = $this->expectedOutput();
        if (!$expected) {
            return null;
        }
        return round($this->output_quantity / $expected * 100, 1);
    }
}
