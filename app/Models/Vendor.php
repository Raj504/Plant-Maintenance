<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $table = 'pm_vendors';
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];

    public function contracts(): HasMany
    {
        return $this->hasMany(AmcContract::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(VendorService::class);
    }

    /**
     * SLA rating 0-100 calculated from service history.
     * Weights: response 20, resolution 20, quality 30, attendance 15, no repeat failure 15.
     */
    public function slaRating(): ?float
    {
        $services = $this->serviceRecords;
        if ($services->isEmpty()) {
            return null;
        }
        $count = $services->count();
        $response   = $services->where('response_within_sla', true)->count() / $count * 20;
        $resolution = $services->where('resolution_within_sla', true)->count() / $count * 20;
        $quality    = $services->avg('work_quality') / 5 * 30;
        $attendance = $services->where('attended', true)->count() / $count * 15;
        $repeat     = $services->where('repeat_failure', false)->count() / $count * 15;

        return round($response + $resolution + $quality + $attendance + $repeat, 1);
    }
}
