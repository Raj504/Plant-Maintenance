<?php

namespace App\Services;

use App\Models\Calibration;
use App\Models\Compliance;
use Illuminate\Support\Facades\DB;

/**
 * Statutory compliance & calibration: record an inspection and roll the due date forward.
 * Old records / certificates are never removed.
 */
class ComplianceService
{
    public function recordInspection(Compliance $compliance, array $data, ?array $files = null): void
    {
        DB::transaction(function () use ($compliance, $data, $files) {
            $record = $compliance->records()->create(array_merge($data, ['recorded_by' => auth()->id()]));
            AttachmentService::storeMany($record, $files, 'certificate');

            $nextDue = $data['next_due_date'] ?? null;
            if (!$nextDue && $compliance->frequency_days) {
                $nextDue = \Carbon\Carbon::parse($data['inspection_date'])->addDays($compliance->frequency_days)->toDateString();
            }

            $compliance->fill([
                'last_inspection_date' => $data['inspection_date'],
                'next_due_date'        => $nextDue,
                'certificate_no'       => $data['certificate_no'] ?? $compliance->certificate_no,
                'agency'               => $data['agency'] ?? $compliance->agency,
                'result'               => $data['result'] ?? null,
            ]);
            $compliance->refreshStatus((int) setting('pm.due_soon_days', 30));
            $compliance->save();
            $compliance->logHistory('inspected', 'Inspection recorded. Result: ' . ($data['result'] ?? '-') . '. Next due ' . show_date($nextDue));
        });
    }

    public function recordCalibration(Calibration $calibration, array $data, ?array $files = null): void
    {
        DB::transaction(function () use ($calibration, $data, $files) {
            $record = $calibration->records()->create(array_merge($data, ['recorded_by' => auth()->id()]));
            AttachmentService::storeMany($record, $files, 'certificate');

            $nextDue = $data['next_due_date'] ?? \Carbon\Carbon::parse($data['calibration_date'])->addDays($calibration->frequency_days)->toDateString();

            $calibration->fill([
                'last_calibration_date' => $data['calibration_date'],
                'next_due_date'         => $nextDue,
                'certificate_no'        => $data['certificate_no'] ?? $calibration->certificate_no,
                'agency'                => $data['agency'] ?? $calibration->agency,
                'result'                => $data['result'] ?? null,
                'status'                => 'valid',
            ]);
            $calibration->refreshStatus((int) setting('pm.due_soon_days', 30));
            $calibration->save();
            $calibration->logHistory('calibrated', 'Calibration recorded. Result: ' . ($data['result'] ?? '-') . '. Next due ' . show_date($nextDue));
        });
    }

    /** Scheduler: refresh every status and raise reminders. */
    public function refreshAllStatuses(): void
    {
        $days = (int) setting('pm.due_soon_days', 30);

        Compliance::all()->each(function (Compliance $c) use ($days) {
            $c->refreshStatus($days);
            $c->save();
            if (in_array($c->status, ['due_soon', 'overdue'])) {
                NotificationService::notifyPermission('pm.compliance.manage', 'compliance_due',
                    ($c->status === 'overdue' ? 'OVERDUE: ' : 'Due soon: ') . $c->inspection_name,
                    ($c->asset?->name ?? '') . ' due on ' . show_date($c->next_due_date),
                    route('compliances.show', $c), $c->status === 'overdue' ? 'danger' : 'warning',
                    'compliance:' . $c->id . ':' . $c->status . ':' . now()->format('YW'));
            }
        });

        Calibration::all()->each(function (Calibration $c) use ($days) {
            $c->refreshStatus($days);
            $c->save();
            if (in_array($c->status, ['due_soon', 'overdue'])) {
                NotificationService::notifyPermission('pm.compliance.manage', 'calibration_due',
                    ($c->status === 'overdue' ? 'OVERDUE calibration: ' : 'Calibration due soon: ') . $c->instrument_name,
                    'Due on ' . show_date($c->next_due_date),
                    route('calibrations.show', $c), $c->status === 'overdue' ? 'danger' : 'warning',
                    'calibration:' . $c->id . ':' . $c->status . ':' . now()->format('YW'));
            }
        });
    }
}
