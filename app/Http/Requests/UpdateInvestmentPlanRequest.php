<?php

namespace App\Http\Requests;

use App\Models\Erkap\InvestmentPlan;
use App\Models\Erkap\WorkProgram;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $monthRules = ['nullable', 'numeric'];

        return [
            'erkap_work_program_id' => ['required', 'integer', 'exists:erkap_work_programs,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'chart_of_account_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'erkap_investattion_category_id' => ['required', 'integer', 'exists:erkap_investattion_categories,id'],
            'erkap_investation_type_id' => ['required', 'integer', 'exists:erkap_investation_types,id'],
            'erkap_investation_criteria_id' => ['required', 'integer', 'exists:erkap_investation_criterias,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:50'],
            'qty' => ['required', 'numeric'],
            'unit_price' => ['required', 'numeric'],
            'jan_plan' => $monthRules,
            'feb_plan' => $monthRules,
            'mar_plan' => $monthRules,
            'apr_plan' => $monthRules,
            'may_plan' => $monthRules,
            'jun_plan' => $monthRules,
            'jul_plan' => $monthRules,
            'aug_plan' => $monthRules,
            'sep_plan' => $monthRules,
            'oct_plan' => $monthRules,
            'nov_plan' => $monthRules,
            'dec_plan' => $monthRules,
            'total' => ['required', 'numeric'],
            'is_kumulatif' => ['nullable', 'boolean'],
            'priority_order' => ['nullable', 'integer', 'min:1'],
            'proposal' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip', 'max:20480'],
            'cba_attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip', 'max:20480'],
            'cba_npv' => ['nullable', 'numeric'],
            'cba_irr' => ['nullable', 'numeric'],
            'cba_payback' => ['nullable', 'numeric'],
            'cba_justification' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateTotals($validator);
            $this->validatePriorityUniqueness($validator);
        });
    }

    private function validateTotals($validator): void
    {
        if ($this->boolean('is_kumulatif')) {
            return;
        }

        if (! $this->has('qty') || ! $this->has('unit_price')) {
            return;
        }

        $months = [
            'jan_plan', 'feb_plan', 'mar_plan', 'apr_plan', 'may_plan', 'jun_plan',
            'jul_plan', 'aug_plan', 'sep_plan', 'oct_plan', 'nov_plan', 'dec_plan',
        ];

        $totalMonthly = array_sum(array_map(fn ($month) => (float) $this->input($month, 0), $months));
        $expectedTotal = (float) $this->input('qty') * (float) $this->input('unit_price');

        if (abs($totalMonthly - $expectedTotal) > 0.01) {
            $validator->errors()->add('total', 'Total harus sama dengan qty × harga satuan dan jumlah bulanan.');
        }
    }

    private function validatePriorityUniqueness($validator): void
    {
        if (! $this->filled('priority_order') || ! $this->filled('erkap_work_program_id')) {
            return;
        }

        $divisionId = WorkProgram::query()
            ->with('riskIdentification.departmentTarget')
            ->find($this->integer('erkap_work_program_id'))
            ?->riskIdentification
            ?->departmentTarget
            ?->division_id;

        if (! $divisionId) {
            return;
        }

        $exists = InvestmentPlan::query()
            ->where('priority_order', $this->integer('priority_order'))
            ->whereHas('workProgram.riskIdentification.departmentTarget', fn ($query) => $query->where('division_id', $divisionId))
            ->when($this->route('investmentPlan')?->id, fn ($query, $id) => $query->whereKeyNot($id))
            ->exists();

        if ($exists) {
            $validator->errors()->add('priority_order', 'Urutan prioritas sudah dipakai pada divisi ini.');
        }
    }
}