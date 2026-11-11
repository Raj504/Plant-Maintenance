<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmcVisit extends Model
{
    protected $table = 'pm_amc_visits';
    protected $guarded = ['id'];
    protected $casts = ['scheduled_date' => 'date', 'actual_date' => 'date'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(AmcContract::class, 'amc_id');
    }
}
