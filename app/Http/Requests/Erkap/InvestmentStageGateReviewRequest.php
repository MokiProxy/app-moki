<?php

namespace App\Http\Requests\Erkap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestmentStageGateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected', 'revised'])],
            'result' => ['nullable', Rule::in(['layak', 'tidak_layak', 'revisi'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'cba_attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Keputusan evaluasi wajib dipilih.',
            'status.in' => 'Keputusan evaluasi tidak valid.',
            'cba_attachment.mimes' => 'Lampiran CBA harus berupa PDF/DOC/XLS.',
        ];
    }
}