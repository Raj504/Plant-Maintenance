<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'pm_settings';
    protected $guarded = ['id'];

    public static function get(string $key, $default = null)
    {
        $all = Cache::remember('pm_settings', 300, fn () => static::pluck('value', 'key')->all());
        return $all[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('pm_settings');
    }
}
