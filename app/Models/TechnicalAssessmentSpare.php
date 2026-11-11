<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalAssessmentSpare extends Model
{
    protected $table = 'pm_technical_assessment_spares';
    protected $guarded = ['id'];

    public function spare(): BelongsTo
    {
        return $this->belongsTo(Spare::class);
    }
}
