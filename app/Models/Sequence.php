<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $table = 'pm_sequences';
    protected $guarded = ['id'];
}
