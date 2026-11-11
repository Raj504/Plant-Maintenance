<?php

namespace App\Models;

use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use HasHistory;

    protected $table = 'pm_purchase_requests';
    protected $guarded = ['id'];
    protected $casts = ['required_by_date' => 'date'];

    public const STATUSES = [
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'ordered'   => 'Ordered',
        'received'  => 'Received',
        'cancelled' => 'Cancelled',
    ];

    public function spare(): BelongsTo
    {
        return $this->belongsTo(Spare::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'request_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
