<?php

/**
 * Small global helper functions used in Blade views and controllers.
 * Loaded automatically through composer.json "autoload.files".
 */

use App\Models\Setting;

if (!function_exists('setting')) {
    /** Read a module setting: setting('pm.due_soon_days', 30) */
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('flash_toast')) {
    /** Queue a toast message for the next page. $type = success | error | warning | info */
    function flash_toast(string $type, string $message): void
    {
        session()->flash('toast', ['type' => $type, 'message' => $message]);
    }
}

if (!function_exists('status_label')) {
    /** "approval_pending" -> "Approval Pending" */
    function status_label(?string $status): string
    {
        return $status ? ucwords(str_replace('_', ' ', $status)) : '-';
    }
}

if (!function_exists('status_color')) {
    /** Bootstrap colour name for a status value (shared across modules). */
    function status_color(?string $status): string
    {
        return match ($status) {
            'active', 'approved', 'closed', 'completed', 'valid', 'acknowledged', 'received', 'passed', 'ok' => 'success',
            'breakdown', 'rejected', 'cancelled', 'overdue', 'failed', 'not_ok', 'missed', 'expired', 'critical' => 'danger',
            'under_maintenance', 'in_progress', 'assigned', 'due', 'due_soon', 'on_hold', 'sent_back',
            'issued', 'ordered', 'high', 'under_calibration' => 'warning',
            'submitted', 'inspection_pending', 'assessment_pending', 'approval_pending', 'verification_pending',
            'work_order_created', 'open', 'pending', 'scheduled', 'medium' => 'primary',
            'idle', 'draft', 'skipped', 'low', 'na', 'decommissioned', 'under_installation', 'terminated' => 'secondary',
            default => 'info',
        };
    }
}

if (!function_exists('status_badge')) {
    /** Returns a ready-to-print Bootstrap badge for a status / priority. */
    function status_badge(?string $status, ?string $label = null): string
    {
        $label = $label ?? status_label($status);
        $color = status_color($status);
        return '<span class="badge text-bg-' . $color . '">' . e($label) . '</span>';
    }
}

if (!function_exists('money')) {
    function money($amount, int $decimals = 2): string
    {
        return setting('pm.currency_symbol', '₹') . ' ' . number_format((float) $amount, $decimals);
    }
}

if (!function_exists('show_date')) {
    function show_date($date, string $format = 'd M Y'): string
    {
        if (!$date) {
            return '-';
        }
        return $date instanceof \DateTimeInterface
            ? $date->format($format)
            : \Carbon\Carbon::parse($date)->format($format);
    }
}

if (!function_exists('show_datetime')) {
    function show_datetime($date): string
    {
        return show_date($date, 'd M Y h:i A');
    }
}

if (!function_exists('is_active_route')) {
    /** Marks sidebar links active: is_active_route('assets.*') */
    function is_active_route(string|array $patterns, string $class = 'active'): string
    {
        return request()->routeIs($patterns) ? $class : '';
    }
}
