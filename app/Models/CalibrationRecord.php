<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationRecord extends Model
{
    use HasAttachments;

    protected $table = 'pm_calibration_records';
    protected $guarded = ['id'];
    protected $casts = ['calibration_date' => 'date', 'next_due_date' => 'date'];

    public function calibration(): BelongsTo
    {
        return $this->belongsTo(Calibration::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
