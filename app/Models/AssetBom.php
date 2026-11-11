<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetBom extends Model
{
    protected $table = 'pm_asset_bom';
    protected $guarded = ['id'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(Spare::class);
    }

    public function preferredVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'preferred_vendor_id');
    }
}
