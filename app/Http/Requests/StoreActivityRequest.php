<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'size:3', 'regex:/^\d{3}$/',
                Rule::unique('erkap_activities')->where(
                    fn ($query) => $query->where('erkap_management_area_id', $this->input('erkap_management_area_id'))
                ),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'erkap_management_area_id' => ['required', 'integer', Rule::exists('erkap_management_areas', 'id')],
            'is_swakelola' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'Kode Aktivitas harus tepat 3 digit (d), contoh 202.',
            'code.regex' => 'Kode Aktivitas harus 3 digit angka.',
            'code.unique' => 'Kode Aktivitas sudah dipakai pada Manajemen Area tersebut.',
            'erkap_management_area_id.required' => 'Manajemen Area wajib dipilih.',
        ];
    }
}
