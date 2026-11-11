<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotoRecord extends Model
{
    protected $table = 'pm_loto_records';
    protected $guarded = ['id'];
    protected $casts = ['isolated_at' => 'datetime', 'deisolated_at' => 'datetime'];

    public function permit(): BelongsTo
    {
        return $this->belongsTo(SafetyPermit::class, 'permit_id');
    }

    public function isolator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'isolated_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function deisolator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deisolated_by');
    }
}
