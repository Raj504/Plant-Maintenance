<?php

namespace App\Http\Requests;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class InspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inspection_date'    => 'required|date',
            'machine_condition'  => 'nullable|string|max:100',
            'actual_problem'     => 'required|string|max:3000',
            'root_cause'         => 'nullable|string|max:3000',
            'machine_running'    => 'boolean',
            'safety_risk'        => 'required|in:none,low,medium,high',
            'production_impact'  => 'required|in:none,partial,full',
            'temporary_solution' => 'nullable|string|max:2000',
            'required_action'    => 'nullable|string|max:2000',
            'remarks'            => 'nullable|string|max:2000',
            'recommendation'     => 'required|in:proceed,reject',
            'rejection_reason'   => 'required_if:recommendation,reject|nullable|string|max:1000',
            'attachments.*'      => 'nullable|' . AttachmentService::rule(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['machine_running' => $this->boolean('machine_running')]);
    }
}
