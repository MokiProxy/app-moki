<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ComposesCostCenterCode;
use App\Rules\CostCenterCodeFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCostCenterRequest extends FormRequest
{
    use ComposesCostCenterCode;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Pusat Biaya tersusun dari segmen a..d. Rantai lengkap tetap
            // diminta supaya dropdown cascading punya konteks, namun nilai
            // final a..c diwarisi dari Aktivitas dan `code` disusun ulang di
            // `prepareForValidation()`.
            'erkap_business_unit_id' => ['required', 'integer', Rule::exists('erkap_business_units', 'id')],
            'erkap_location_id' => ['required', 'integer', Rule::exists('erkap_locations', 'id')],
            'erkap_management_area_id' => ['required', 'integer', Rule::exists('erkap_management_areas', 'id')],
            'erkap_activity_id' => ['required', 'integer', Rule::exists('erkap_activities', 'id')],

            'code' => [
                'required',
                'string',
                new CostCenterCodeFormat,
                Rule::unique('cost_centers', 'code'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'owner' => ['required', 'string', 'max:255'],
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'is_centralized' => ['sometimes', 'boolean'],
            'coordinating_division_id' => [
                'nullable',
                'integer',
                'exists:divisions,id',
                'required_if:is_centralized,1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'erkap_business_unit_id.required' => 'Bisnis Unit wajib dipilih.',
            'erkap_location_id.required' => 'Lokasi wajib dipilih.',
            'erkap_management_area_id.required' => 'Manajemen Area wajib dipilih.',
            'erkap_activity_id.required' => 'Aktivitas wajib dipilih.',
            'code.required' => 'Kode Pusat Biaya tidak dapat disusun dari segmen yang dipilih.',
            'code.unique' => 'Kombinasi segmen ini sudah dipakai Pusat Biaya lain.',
        ];
    }
}
