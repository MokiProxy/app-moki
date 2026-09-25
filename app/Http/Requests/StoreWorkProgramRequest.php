<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $percentageRules = ['required', 'numeric', 'min:0', 'max:100'];

        return [
            'erkap_risk_identification_id' => ['required', 'integer', 'exists:erkap_risk_identifications,id'],
            'name' => ['required', 'string'],
            'units' => ['required', 'string', 'max:255'],
            'year_plan' => $percentageRules,
            'jan_plan' => $percentageRules,
            'feb_plan' => $percentageRules,
            'mar_plan' => $percentageRules,
            'apr_plan' => $percentageRules,
            'may_plan' => $percentageRules,
            'jun_plan' => $percentageRules,
            'jul_plan' => $percentageRules,
            'aug_plan' => $percentageRules,
            'sep_plan' => $percentageRules,
            'oct_plan' => $percentageRules,
            'nov_plan' => $percentageRules,
            'dec_plan' => $percentageRules,
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $monthlyTotal = collect([
                $this->jan_plan,
                $this->feb_plan,
                $this->mar_plan,
                $this->apr_plan,
                $this->may_plan,
                $this->jun_plan,
                $this->jul_plan,
                $this->aug_plan,
                $this->sep_plan,
                $this->oct_plan,
                $this->nov_plan,
                $this->dec_plan,
            ])->sum();

            if (abs($monthlyTotal - (float) $this->year_plan) > 0.01) {
                $validator->errors()->add('year_plan', 'Jumlah persentase rencana bulanan harus sama dengan rencana tahunan.');
            }
        });
    }
}
