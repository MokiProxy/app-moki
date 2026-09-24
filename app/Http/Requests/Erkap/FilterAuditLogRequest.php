<?php

namespace App\Http\Requests\Erkap;

use Illuminate\Foundation\Http\FormRequest;

class FilterAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string'],
            'id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'in:create,update,delete'],
            'user_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
        ];
    }
}