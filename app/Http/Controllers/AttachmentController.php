<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(Attachment $attachment)
    {
        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Only the uploader, or a user with the module's manage permission, may delete.
     * Permission is derived from the owning model's class name.
     */
    public function destroy(Attachment $attachment)
    {
        $user = auth()->user();
        $permission = $this->permissionFor($attachment->attachable_type);

        if ($attachment->uploaded_by !== $user->id && !$user->hasPermission($permission)) {
            abort(403, 'You cannot delete this file.');
        }

        AttachmentService::delete($attachment);
        flash_toast('success', 'Attachment removed.');
        return back();
    }

    protected function permissionFor(string $modelClass): string
    {
        return match (class_basename($modelClass)) {
            'Asset'                                               => 'pm.assets.edit',
            'MaintenanceRequest', 'RequestInspection', 'TechnicalAssessment' => 'pm.requests.manage',
            'WorkOrder'                                           => 'pm.workorders.verify',
            'PmLog'                                               => 'pm.pm.manage',
            'SafetyPermit'                                        => 'pm.safety.approve',
            'DailyLog'                                            => 'pm.logs.manage',
            'AmcContract'                                         => 'pm.amc.manage',
            'Compliance', 'ComplianceRecord', 'Calibration', 'CalibrationRecord' => 'pm.compliance.manage',
            default                                               => 'pm.settings.manage',
        };
    }
}
