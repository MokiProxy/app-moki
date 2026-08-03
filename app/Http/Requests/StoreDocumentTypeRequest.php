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
            'number_regex' => ['nullable', 'string', 'max:255'],
            'number_label' => ['nullable', 'string', 'max:255'],
            'keterangan_regex' => ['nullable', 'string', 'max:255'],
            'keterangan_label' => ['nullable', 'string', 'max:255'],
            'keterangan_enabled' => ['nullable', 'boolean'],
            'uraian_regex' => ['nullable', 'string', 'max:255'],
            'uraian_label' => ['nullable', 'string', 'max:255'],
            'uraian_enabled' => ['nullable', 'boolean'],
            'tanggal_regex' => ['nullable', 'string', 'max:255'],
            'tanggal_label' => ['nullable', 'string', 'max:255'],
            'tanggal_enabled' => ['nullable', 'boolean'],
            'filename_template' => ['nullable', 'string', 'max:255'],
            'ftp_folder_template' => ['nullable', 'string', 'max:255'],
            'ftp_failed_folder' => ['nullable', 'string', 'max:255'],
            'vendor_search_enabled' => ['nullable', 'boolean'],
        ];
    }
}
