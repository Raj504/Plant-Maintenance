<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'pm_permissions';
    protected $guarded = ['id'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'pm_permission_role', 'permission_id', 'role_id');
    }
}
