<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestInspection extends Model
{
    use HasAttachments;

    protected $table = 'pm_request_inspections';
    protected $guarded = ['id'];
    protected $casts = ['inspection_date' => 'datetime', 'machine_running' => 'boolean'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'request_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
