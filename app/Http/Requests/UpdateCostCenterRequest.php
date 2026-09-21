<?php

namespace App\Http\Requests;

use App\Models\Erkap\CostElement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:cost_centers,code,' . $this->route('costCenter'),
                'regex:/^[A-Za-z]\d{14}$/',
                function ($attribute, $value, $fail) {
                    if (! CostElement::where('code', substr($value, -4))->exists()) {
                        $fail('4 digit terakhir kode harus sesuai dengan kode elemen biaya yang tersedia.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'owner' => ['required', 'string', 'max:255'],
            'division_id' => ['required', 'integer', 'exists:divisions,id'],
            'is_swakelola' => ['sometimes', 'boolean'],
        ];
    }
}