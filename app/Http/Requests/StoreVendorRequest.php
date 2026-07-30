<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'document_type_ids' => ['required', 'array', 'min:1'],
            'document_type_ids.*' => ['exists:document_types,id'],
        ];
    }
}
