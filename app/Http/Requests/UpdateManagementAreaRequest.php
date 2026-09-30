<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagementAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'size:5', 'regex:/^\d{5}$/',
                Rule::unique('erkap_management_areas')
                    ->where(
                        fn ($query) => $query->where('erkap_location_id', $this->input('erkap_location_id'))
                    )
                    ->ignoreModel($this->route('managementArea')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'erkap_location_id' => ['required', 'integer', Rule::exists('erkap_locations', 'id')],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'Kode Manajemen Area harus tepat 5 digit (c), contoh 20200.',
            'code.regex' => 'Kode Manajemen Area harus 5 digit angka.',
            'code.unique' => 'Kode Manajemen Area sudah dipakai pada Lokasi tersebut.',
            'erkap_location_id.required' => 'Lokasi wajib dipilih.',
        ];
    }
}
