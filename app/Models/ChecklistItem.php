<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    protected $table = 'pm_checklist_items';
    protected $guarded = ['id'];
}
