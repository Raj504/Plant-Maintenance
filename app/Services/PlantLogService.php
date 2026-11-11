<?php

namespace App\Services;

use App\Models\AssetReading;
use App\Models\DailyLog;
use App\Models\LogType;
use Illuminate\Support\Facades\DB;

/**
 * Daily plant / utility logs.
 * Handles the configurable readings JSON, consumption calculation and abnormal flagging.
 */
class PlantLogService
{
    public function create(array $data, array $readings = [], ?array $files = null): DailyLog
    {
        return DB::transaction(function () use ($data, $readings, $files) {
            $logType = LogType::findOrFail($data['log_type_id']);

            $data['log_no']     = DocumentNumberService::next('daily_log');
            $data['created_by'] = auth()->id();
            $data['readings']   = $this->cleanReadings($logType, $readings);
            $data = $this->calculate($data);

            $log = DailyLog::create($data);
            AttachmentService::storeMany($log, $files, 'log');
            $log->logHistory('created', 'Log entry created');

            // Equipment log closing reading can update the machine running hours
            if (!empty($data['update_running_hours']) && $log->asset_id && $log->closing_reading !== null) {
                $this->updateRunningHours($log);
            }

            return $log;
        });
    }

    public function update(DailyLog $log, array $data, array $readings = [], ?array $files = null): DailyLog
    {
        return DB::transaction(function () use ($log, $data, $readings, $files) {
            $data['readings'] = $this->cleanReadings($log->logType, $readings);
            $data = $this->calculate($data);

            // Audit: keep old meter readings in history
            $changes = [];
            foreach (['opening_reading', 'closing_reading', 'consumption'] as $field) {
                if (array_key_exists($field, $data) && (string) $log->$field !== (string) $data[$field]) {
                    $changes[$field] = ['old' => $log->$field, 'new' => $data[$field]];
                }
            }

            $log->update($data);
            AttachmentService::storeMany($log, $files, 'log');
            $log->logHistory('updated', $changes ? 'Meter readings changed' : 'Log updated', null, null, $changes ?: null);

            return $log;
        });
    }

    protected function cleanReadings(LogType $logType, array $readings): array
    {
        $clean = [];
        foreach ($logType->fields ?? [] as $field) {
            $clean[$field['key']] = $readings[$field['key']] ?? null;
        }
        return $clean;
    }

    protected function calculate(array $data): array
    {
        if (isset($data['opening_reading'], $data['closing_reading']) && $data['opening_reading'] !== '' && $data['closing_reading'] !== '') {
            $data['consumption'] = (float) $data['closing_reading'] - (float) $data['opening_reading'];
        }
        $data['is_abnormal'] = !empty($data['abnormal_conditions'])
            || (isset($data['consumption']) && $data['consumption'] < 0);
        unset($data['update_running_hours']);
        return $data;
    }

    protected function updateRunningHours(DailyLog $log): void
    {
        $asset = $log->asset;
        if ($asset && $log->closing_reading > $asset->current_running_hours) {
            AssetReading::create([
                'asset_id'     => $asset->id,
                'reading_date' => $log->log_date,
                'reading_type' => 'running_hours',
                'value'        => $log->closing_reading,
                'recorded_by'  => auth()->id(),
                'remarks'      => 'From daily log ' . $log->log_no,
            ]);
            $asset->update(['current_running_hours' => $log->closing_reading]);
        }
    }
}
