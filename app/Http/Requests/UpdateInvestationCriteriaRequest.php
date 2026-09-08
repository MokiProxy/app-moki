<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestationCriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:erkap_investation_criterias,code,' . $this->route('investationCriteria')],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}