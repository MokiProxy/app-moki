<?php

namespace App\Http\Requests\Concerns;

use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Support\CoaCode;

/**
 * Menyusun ulang `code` Pusat Biaya dari segmen a..d sebelum validasi.
 *
 * `code` tidak lagi dipercaya dari input client: nilai yang tervalidasi harus
 * sama dengan hasil komposisi server, sehingga aturan `unique` dan
 * `CostCenterCodeFormat` memeriksa kode kanonik, bukan ketikan pengguna.
 *
 * Model `CostCenter::syncInheritedSegments()` mengulang pewarisan segmen sekali
 * lagi saat saving sebagai jaring pengaman kedua.
 */
trait ComposesCostCenterCode
{
    /**
     * Segmen a..d menurut urutan komposisi.
     *
     * @return array<int, string>
     */
    protected function costCenterSegmentFields(): array
    {
        return [
            'erkap_business_unit_id',
            'erkap_location_id',
            'erkap_management_area_id',
            'erkap_activity_id',
        ];
    }

    protected function prepareForValidation(): void
    {
        $segments = [];

        foreach ($this->costCenterSegmentFields() as $field) {
            $segments[$this->costCenterSegmentKey($field)] = $this->lookupSegmentCode($field);
        }

        $code = CoaCode::composeCostCenter($segments);

        $this->merge([
            'code' => $code,
        ]);
    }

    private function costCenterSegmentKey(string $field): string
    {
        return [
            'erkap_business_unit_id' => 'business_unit',
            'erkap_location_id' => 'location',
            'erkap_management_area_id' => 'management_area',
            'erkap_activity_id' => 'activity',
        ][$field];
    }

    private function lookupSegmentCode(string $field): ?string
    {
        $id = $this->input($field);

        if (! is_numeric($id)) {
            return null;
        }

        $model = match ($field) {
            'erkap_business_unit_id' => BusinessUnit::class,
            'erkap_location_id' => Location::class,
            'erkap_management_area_id' => ManagementArea::class,
            'erkap_activity_id' => Activity::class,
        };

        return $model::query()->whereKey((int) $id)->value('code');
    }
}
