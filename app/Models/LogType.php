<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configurable daily log form. "fields" example:
 * [ {"key":"temperature","label":"Temperature","unit":"°C","type":"number"},
 *   {"key":"running_status","label":"Running Status","type":"select","options":"Running,Stopped,Standby"} ]
 */
class LogType extends Model
{
    protected $table = 'pm_log_types';
    protected $guarded = ['id'];
    protected $casts = ['fields' => 'array', 'has_meter_readings' => 'boolean', 'is_active' => 'boolean'];
}
