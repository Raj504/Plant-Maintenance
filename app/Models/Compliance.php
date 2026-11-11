<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compliance extends Model
{
    use HasAttachments, HasHistory;

    protected $table = 'pm_compliances';
    protected $guarded = ['id'];
    protected $casts = ['last_inspection_date' => 'date', 'next_due_date' => 'date'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class)->latest('inspection_date');
    }

    /** Recalculate status from the due date. Called whenever dates change and by the scheduler. */
    public function refreshStatus(int $dueSoonDays = 30): void
    {
        if ($this->result === 'failed') {
            $this->status = 'failed';
        } elseif (!$this->next_due_date) {
            $this->status = 'valid';
        } elseif ($this->next_due_date->isPast()) {
            $this->status = 'overdue';
        } elseif ($this->next_due_date->lte(now()->addDays($dueSoonDays))) {
            $this->status = 'due_soon';
        } else {
            $this->status = 'valid';
        }
    }
}
