<?php

namespace App\Http\Requests;

use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Rules\CoaCodeFormat;
use App\Support\CoaCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cost_center_id' => ['required', 'integer', Rule::exists('cost_centers', 'id')],
            'cost_element_id' => ['required', 'integer', Rule::exists('erkap_cost_elements', 'id')],

            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:revenue,expense'],
            'description' => ['nullable', 'string'],

            // Kode a..e disusun dari Pusat Biaya + Elemen Biaya. Nilai dari
            // client diabaikan; yang divalidasi adalah hasil komposisi server.
            'code' => ['required', 'string', new CoaCodeFormat, Rule::unique('chart_of_accounts', 'code')],
        ];
    }

    public function messages(): array
    {
        return [
            'cost_center_id.required' => 'Pusat Biaya wajib dipilih.',
            'cost_element_id.required' => 'Elemen Biaya wajib dipilih.',
            'code.required' => 'Kode Chart of Account tidak dapat disusun dari Pusat Biaya dan Elemen Biaya yang dipilih.',
            'code.unique' => 'Kombinasi Pusat Biaya dan Elemen Biaya ini sudah memiliki Chart of Account.',
        ];
    }

    /**
     * Susun ulang `code` dari Pusat Biaya + Elemen Biaya, dan isi `name` serta
     * `type` dari Elemen Biaya bila form tidak mengirimkannya.
     */
    protected function prepareForValidation(): void
    {
        $costCenter = $this->input('cost_center_id');
        $costElementId = $this->input('cost_element_id');

        if (! is_numeric($costCenter) || ! is_numeric($costElementId)) {
            return;
        }

        $costCenter = CostCenter::query()
            ->with(['businessUnit', 'location', 'managementArea', 'activity'])
            ->find((int) $costCenter);

        $costElement = CostElement::query()->find((int) $costElementId);

        if (! $costCenter || ! $costElement) {
            return;
        }

        $code = CoaCode::compose(array_merge($costCenter->segments(), [
            'cost_element' => $costElement->code,
        ]));

        $this->merge([
            'code' => $code,
            'name' => $this->filled('name') ? $this->input('name') : $this->defaultName($costCenter, $costElement),
            'type' => $this->filled('type') ? $this->input('type') : $this->defaultType($costElement),
        ]);
    }

    /**
     * Nama default mengikuti Elemen Biaya, diawali konteks Pusat Biaya agar
     * mudah dibedakan di daftar akun yang menumpuk 16.700 baris.
     */
    private function defaultName(CostCenter $costCenter, CostElement $costElement): string
    {
        return Str::limit($costElement->name.' — '.$costCenter->name, 252, '...');
    }

    /**
     * Tipe default diambil dari COA yang sudah memakai elemen ini, sehingga
     * satu elemen biaya tidak pernah menghasilkan tipe yang berbeda antar Pusat
     * Biaya.
     */
    private function defaultType(CostElement $costElement): string
    {
        $type = $costElement->chartOfAccounts()->orderBy('code')->value('type');

        return in_array($type, ['revenue', 'expense'], true) ? $type : 'expense';
    }
}
