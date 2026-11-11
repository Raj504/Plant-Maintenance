<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmcContract extends Model
{
    use HasAttachments, HasHistory;

    protected $table = 'pm_amc_contracts';
    protected $guarded = ['id'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'pm_amc_assets', 'amc_id', 'asset_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(AmcVisit::class, 'amc_id')->orderBy('scheduled_date');
    }

    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class, 'amc_id');
    }

    public function daysToExpiry(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->end_date, false);
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }
}
