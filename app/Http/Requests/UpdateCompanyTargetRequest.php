<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string'],
            'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
        ];
    }
}
