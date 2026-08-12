<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'gemini_prompt' => ['nullable', 'string', 'max:5000'],
            'gemini_fields' => ['nullable', 'string', 'max:1000'],
            'filename_template' => ['nullable', 'string', 'max:255'],
            'ftp_folder_template' => ['nullable', 'string', 'max:255'],
            'ftp_failed_folder' => ['nullable', 'string', 'max:255'],
            'vendor_search_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'gemini_fields' => 'Format Gemini Fields harus berupa JSON array yang valid.',
        ];
    }
}
