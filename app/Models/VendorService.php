<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorService extends Model
{
    protected $table = 'pm_vendor_services';
    protected $guarded = ['id'];
    protected $casts = [
        'service_date'          => 'date',
        'response_within_sla'   => 'boolean',
        'resolution_within_sla' => 'boolean',
        'attended'              => 'boolean',
        'repeat_failure'        => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(AmcContract::class, 'amc_id');
    }
}
