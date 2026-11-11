<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceRecord extends Model
{
    use HasAttachments;

    protected $table = 'pm_compliance_records';
    protected $guarded = ['id'];
    protected $casts = ['inspection_date' => 'date', 'next_due_date' => 'date'];

    public function compliance(): BelongsTo
    {
        return $this->belongsTo(Compliance::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
