<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $table = 'pm_shifts';
    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean'];
}
