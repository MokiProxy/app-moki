<?php

namespace App\Http\Requests\Erkap;

use App\Services\ErkapAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestmentStageGateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        if ($user->hasAnyRole(['super-admin', 'admin', 'erkap-admin', 'erkap-auditor'])) {
            return true;
        }

        $gate = $this->route('gate');

        if (! $gate) {
            return false;
        }

        if (! $gate->canReviewBy($user)) {
            return false;
        }

        if ($user->hasRole('erkap-cost-owner')) {
            $plan = $gate->plan;
            $divisionId = $plan?->workProgram?->riskIdentification?->departmentTarget?->division_id;

            if ($divisionId && ErkapAccess::divisionId() !== $divisionId) {
                return false;
            }
        }

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