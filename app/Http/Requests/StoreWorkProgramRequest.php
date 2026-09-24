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
        $monthRules = ['required', 'numeric', 'min:0'];

        return [
            'erkap_risk_identification_id' => ['required', 'integer', 'exists:erkap_risk_identifications,id'],
            'name' => ['required', 'string'],
            'units' => ['required', 'string', 'max:255'],
            'year_plan' => $monthRules,
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
                $validator->errors()->add('year_plan', 'Rencana tahunan harus sama dengan jumlah rencana bulanan.');
            }
        });
    }
}
