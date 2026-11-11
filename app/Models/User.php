<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_code', 'name', 'email', 'password', 'department_id',
        'designation', 'phone', 'avatar', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /** Cached permission list so we only query once per request. */
    protected ?array $permissionCache = null;

    // ---------------------------------------------------------------- Relations

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'pm_role_user', 'user_id', 'role_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'pm_team_user', 'user_id', 'team_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    // ---------------------------------------------------------------- RBAC helpers

    public function hasRole(string|array $slug): bool
    {
        $slugs = (array) $slug;
        return $this->roles->contains(fn ($role) => in_array($role->slug, $slugs));
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /** All permission names this user has through any of their roles. */
    public function permissionNames(): array
    {
        if ($this->permissionCache === null) {
            $this->permissionCache = $this->roles()
                ->with('permissions:id,name')
                ->get()
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->unique()
                ->values()
                ->all();
        }
        return $this->permissionCache;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return in_array($permission, $this->permissionNames());
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', trim($this->name));
        $initials = strtoupper(substr($parts[0], 0, 1));
        if (count($parts) > 1) {
            $initials .= strtoupper(substr(end($parts), 0, 1));
        }
        return $initials;
    }
}
