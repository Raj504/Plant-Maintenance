<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plant extends Model
{
    protected $table = 'pm_plants';
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
