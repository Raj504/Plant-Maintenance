<?php

namespace App\Http\Requests;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class AssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessment_date'          => 'required|date',
            'failure_nature'           => 'required|string|max:3000',
            'probable_root_cause'      => 'nullable|string|max:3000',
            'action_required'          => 'required|in:repair,replace,both',
            'required_manpower'        => 'nullable|string|max:150',
            'external_vendor_required' => 'boolean',
            'vendor_id'                => 'nullable|exists:pm_vendors,id',
            'estimated_downtime_hours' => 'nullable|numeric|min:0',
            'estimated_labour_cost'    => 'nullable|numeric|min:0',
            'estimated_material_cost'  => 'nullable|numeric|min:0',
            'estimated_service_cost'   => 'nullable|numeric|min:0',
            'permit_required'          => 'boolean',
            'recommendation'           => 'nullable|string|max:3000',
            'spares'                   => 'nullable|array',
            'spares.*.spare_id'        => 'nullable|exists:pm_spares,id',
            'spares.*.spare_name'      => 'nullable|string|max:150',
            'spares.*.quantity'        => 'nullable|numeric|min:0.01',
            'spares.*.estimated_cost'  => 'nullable|numeric|min:0',
            'attachments.*'            => 'nullable|' . AttachmentService::rule(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'external_vendor_required' => $this->boolean('external_vendor_required'),
            'permit_required'          => $this->boolean('permit_required'),
        ]);
    }
}
