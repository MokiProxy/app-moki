<?php

namespace App\Http\Requests\Erkap;

use Illuminate\Foundation\Http\FormRequest;

class StoreRKAPDirectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('erkap.rkap.edit');
    }

    public function rules(): array
    {
        return [
            'direction_notes' => ['nullable', 'string'],
            'direction_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png', 'max:10240'],
        ];
    }
}