<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'filename_template' => ['nullable', 'string', 'max:255'],
            'ftp_folder_template' => ['nullable', 'string', 'max:255'],
            'ftp_failed_folder' => ['nullable', 'string', 'max:255'],
            'vendor_search_enabled' => ['nullable', 'boolean'],
        ];
    }
}
