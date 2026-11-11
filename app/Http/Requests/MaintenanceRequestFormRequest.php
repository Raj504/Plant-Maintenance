<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest;
use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id'            => 'required|exists:pm_assets,id',
            'component_id'        => 'nullable|exists:pm_assets,id|different:asset_id',
            'department_id'       => 'nullable|exists:pm_departments,id',
            'request_type'        => ['required', Rule::in(array_keys(MaintenanceRequest::TYPES))],
            'priority'            => ['required', Rule::in(array_keys(MaintenanceRequest::PRIORITIES))],
            'shift_id'            => 'nullable|exists:pm_shifts,id',
            'problem_description' => 'required|string|min:10|max:3000',
            'problem_category_id' => 'nullable|exists:pm_categories,id',
            'machine_stopped'     => 'boolean',
            'production_affected' => 'boolean',
            'remarks'             => 'nullable|string|max:2000',
            'attachments.*'       => 'nullable|' . AttachmentService::rule(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'machine_stopped'     => $this->boolean('machine_stopped'),
            'production_affected' => $this->boolean('production_affected'),
        ]);
    }
}
