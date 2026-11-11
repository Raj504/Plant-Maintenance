<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalAssessment extends Model
{
    use HasAttachments;

    protected $table = 'pm_technical_assessments';
    protected $guarded = ['id'];
    protected $casts = [
        'assessment_date'          => 'datetime',
        'external_vendor_required' => 'boolean',
        'permit_required'          => 'boolean',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'request_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function spares(): HasMany
    {
        return $this->hasMany(TechnicalAssessmentSpare::class, 'assessment_id');
    }
}
