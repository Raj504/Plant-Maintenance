<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetReading extends Model
{
    protected $table = 'pm_asset_readings';
    protected $guarded = ['id'];
    protected $casts = ['reading_date' => 'date'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
