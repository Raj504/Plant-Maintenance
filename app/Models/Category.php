<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic categories. "type" decides where the category is used:
 * asset | maintenance | spare | problem | permit | compliance
 */
class Category extends Model
{
    protected $table = 'pm_categories';
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];

    public const TYPES = [
        'asset'       => 'Asset Category',
        'maintenance' => 'Maintenance Category',
        'spare'       => 'Spare Part Category',
        'problem'     => 'Problem Category',
        'compliance'  => 'Compliance Type',
    ];

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type)->where('is_active', true)->orderBy('name');
    }
}
