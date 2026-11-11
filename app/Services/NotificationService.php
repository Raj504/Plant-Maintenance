<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * In-app notifications.
 *
 * notifyUser()        -> one person
 * notifyPermission()  -> everyone whose role has that permission (e.g. all inspectors)
 *
 * $dedupeKey makes repeated scheduler runs safe: the same key never creates a second row.
 */
class NotificationService
{
    public static function notifyUser(?int $userId, string $type, string $title, ?string $message = null, ?string $link = null, string $level = 'info', ?string $dedupeKey = null): void
    {
        if (!$userId) {
            return;
        }
        $key = $dedupeKey ? $dedupeKey . ':' . $userId : null;

        if ($key && Notification::where('dedupe_key', $key)->exists()) {
            return;
        }

        Notification::create([
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'level'      => $level,
            'dedupe_key' => $key,
        ]);
    }

    public static function notifyUsers(Collection|array $users, string $type, string $title, ?string $message = null, ?string $link = null, string $level = 'info', ?string $dedupeKey = null): void
    {
        foreach ($users as $user) {
            $id = $user instanceof User ? $user->id : (int) $user;
            self::notifyUser($id, $type, $title, $message, $link, $level, $dedupeKey);
        }
    }

    /** Notify every active user who holds the given permission through any role. */
    public static function notifyPermission(string $permission, string $type, string $title, ?string $message = null, ?string $link = null, string $level = 'info', ?string $dedupeKey = null): void
    {
        $users = self::usersWithPermission($permission);
        self::notifyUsers($users, $type, $title, $message, $link, $level, $dedupeKey);
    }

    public static function usersWithPermission(string $permission): Collection
    {
        $roleIds = Role::whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->orWhere('slug', 'super-admin')
            ->pluck('id');

        return User::where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('pm_roles.id', $roleIds))
            ->get();
    }

    public static function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }
}
