<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class History extends Model
{
    protected $table = 'pm_history';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
