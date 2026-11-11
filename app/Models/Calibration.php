<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calibration extends Model
{
    use HasAttachments, HasHistory;

    protected $table = 'pm_calibrations';
    protected $guarded = ['id'];
    protected $casts = ['last_calibration_date' => 'date', 'next_due_date' => 'date'];

    public const STATUSES = [
        'valid'             => 'Valid',
        'due_soon'          => 'Due Soon',
        'overdue'           => 'Overdue',
        'failed'            => 'Failed',
        'under_calibration' => 'Under Calibration',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(CalibrationRecord::class)->latest('calibration_date');
    }

    public function refreshStatus(int $dueSoonDays = 30): void
    {
        if ($this->status === 'under_calibration') {
            return;
        }
        if ($this->result === 'failed') {
            $this->status = 'failed';
        } elseif (!$this->next_due_date) {
            $this->status = 'valid';
        } elseif ($this->next_due_date->isPast()) {
            $this->status = 'overdue';
        } elseif ($this->next_due_date->lte(now()->addDays($dueSoonDays))) {
            $this->status = 'due_soon';
        } else {
            $this->status = 'valid';
        }
    }
}
