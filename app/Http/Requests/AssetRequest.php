<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission checked by route middleware
    }

    public function rules(): array
    {
        $assetId = $this->route('asset')?->id;

        return [
            'asset_code'            => ['required', 'string', 'max:50', Rule::unique('pm_assets', 'asset_code')->ignore($assetId)->whereNull('deleted_at')],
            'name'                  => 'required|string|max:150',
            'category_id'           => 'nullable|exists:pm_categories,id',
            'parent_id'             => ['nullable', 'exists:pm_assets,id', Rule::notIn([$assetId])],
            'department_id'         => 'nullable|exists:pm_departments,id',
            'plant_id'              => 'nullable|exists:pm_plants,id',
            'location'              => 'nullable|string|max:150',
            'machine_number'        => 'nullable|string|max:50',
            'manufacturer'          => 'nullable|string|max:150',
            'model_number'          => 'nullable|string|max:100',
            'serial_number'         => 'nullable|string|max:100',
            'installation_date'     => 'nullable|date',
            'commissioning_date'    => 'nullable|date',
            'purchase_date'         => 'nullable|date',
            'warranty_expiry_date'  => 'nullable|date',
            'criticality'           => ['required', Rule::in(array_keys(Asset::CRITICALITIES))],
            'status'                => ['required', Rule::in(array_keys(Asset::STATUSES))],
            'current_running_hours' => 'nullable|numeric|min:0',
            'expected_life_years'   => 'nullable|numeric|min:0|max:100',
            'rated_capacity'        => 'nullable|numeric|min:0',
            'capacity_unit'         => 'nullable|string|max:20',
            'capacity_hours'        => 'nullable|numeric|min:0.1|max:24',
            'specifications'        => 'nullable|string',
            'remarks'               => 'nullable|string',
            'image'                 => 'nullable|image|max:4096',
            'attachments.*'         => 'nullable|' . \App\Services\AttachmentService::rule(),
        ];
    }
}
