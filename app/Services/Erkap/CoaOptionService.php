<?php

namespace App\Services\Erkap;

use App\Models\ChartOfAccount;
use App\Models\Division;
use App\Models\Erkap\Activity;
use App\Models\Erkap\BusinessUnit;
use App\Models\Erkap\CostCenter;
use App\Models\Erkap\CostElement;
use App\Models\Erkap\Location;
use App\Models\Erkap\ManagementArea;
use App\Services\ErkapAccess;
use App\Support\CoaCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Sumber data untuk dropdown cascading segment a..e beserta Pusat Biaya & COA.
 *
 * Rantai: Bisnis Unit (a) → Lokasi (b) → Manajemen Area (c) → Aktivitas (d)
 *         → Elemen Biaya (e) → Chart of Account.
 *
 * Setiap metode menerima filter parent (ID), sehingga dropdown anak hanya
 * menampilkan opsi yang sah untuk parent yang dipilih.
 */
class CoaOptionService
{
    /* ---------------------------------------------------------------------
     | Opsi dropdown (parent → child)
     | ------------------------------------------------------------------ */

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function businessUnits(): Collection
    {
        return BusinessUnit::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (BusinessUnit $unit) => $this->option($unit));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function locations(?int $businessUnitId = null): Collection
    {
        return Location::query()
            ->where('is_active', true)
            ->when($businessUnitId, fn (Builder $q) => $q->where('erkap_business_unit_id', $businessUnitId))
            ->with('businessUnit')
            ->orderBy('erkap_business_unit_id')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (Location $location) => $this->option($location));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function managementAreas(?int $locationId = null, ?int $businessUnitId = null, ?int $divisionId = null): Collection
    {
        $divisionId = ErkapAccess::scopeDivision($divisionId);

        return ManagementArea::query()
            ->where('is_active', true)
            ->when($locationId, fn (Builder $q) => $q->where('erkap_location_id', $locationId))
            ->when($businessUnitId, fn (Builder $q) => $q->where('erkap_business_unit_id', $businessUnitId))
            ->when($divisionId, fn (Builder $q) => $q->where('division_id', $divisionId))
            ->with('location')
            ->orderBy('code')
            ->get()
            ->map(fn (ManagementArea $area) => $this->option($area));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function activities(
        ?int $managementAreaId = null,
        ?int $locationId = null,
        ?int $businessUnitId = null
    ): Collection {
        $divisionId = ErkapAccess::scopeDivision(null);

        return Activity::query()
            ->where('is_active', true)
            ->when($managementAreaId, fn (Builder $q) => $q->where('erkap_management_area_id', $managementAreaId))
            ->when($locationId, fn (Builder $q) => $q->where('erkap_location_id', $locationId))
            ->when($businessUnitId, fn (Builder $q) => $q->where('erkap_business_unit_id', $businessUnitId))
            ->when($divisionId, fn (Builder $q) => $q->whereHas(
                'managementArea',
                fn (Builder $m) => $m->where('division_id', $divisionId)
            ))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(function (Activity $activity) {
                return $this->option($activity) + [
                    'is_swakelola' => (bool) $activity->is_swakelola,
                ];
            });
    }

    /**
     * Elemen Biaya — seluruh elemen aktif, atau elemen yang tersedia pada
     * Pusat Biaya tertentu bila `$costCenterId` diisi.
     *
     * `cost_center_id` datang dari client, jadi untuk pengguna berscope divisi
     * Pusat Biaya milik divisi lain harus ditolak, bukan sekadar difilter.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function costElements(?int $costCenterId = null, ?string $type = null): Collection
    {
        $this->assertCostCenterInScope($costCenterId);

        return CostElement::query()
            ->with('costElementCategory')
            ->when($costCenterId, fn (Builder $q) => $q->whereIn(
                'id',
                ChartOfAccount::query()->where('cost_center_id', $costCenterId)->select('cost_element_id')
            ))
            ->when($type, function (Builder $q) use ($type) {
                $q->whereHas('chartOfAccounts', fn ($coas) => $coas->where('type', $type));
            })
            ->orderBy('code')
            ->get()
            ->map(function (CostElement $element) use ($type) {
                return $this->option($element, $element->code.' — '.$element->name) + [
                    'type' => $type ?: $this->resolveElementType($element),
                ];
            });
    }

    /**
     * Cakupan kombinasi Pusat Biaya × Elemen Biaya yang sudah punya COA.
     *
     * @return array{expected:int,filled:int,missing:int,percent:int,costCenters:int,costElements:int}
     */
    public function coverage(): array
    {
        $costCenters = CostCenter::query()->count();
        $costElements = CostElement::query()->count();
        $expected = $costCenters * $costElements;
        $filled = ChartOfAccount::query()
            ->whereNotNull('cost_center_id')
            ->whereNotNull('cost_element_id')
            ->count();

        return [
            'expected' => $expected,
            'filled' => $filled,
            'missing' => max(0, $expected - $filled),
            'percent' => $expected > 0 ? (int) floor($filled / $expected * 100) : 100,
            'costCenters' => $costCenters,
            'costElements' => $costElements,
        ];
    }

    /**
     * Pusat Biaya hasil kombinasi segmen a..d.
     *
     * Cakupannya mengikuti `ErkapAccess`: pengguna berscope divisi hanya melihat
     * Pusat Biaya divisinya sendiri. Filter divisi dari client tidak boleh
     * mengmanuellekan pembatasan tersebut, jadi nilai selalu dilewatkan melalui
     * `ErkapAccess::scopeDivision()` lebih dulu.
     *
     * @param  array<string, int|null>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function costCenters(array $filters = []): Collection
    {
        $divisionId = ErkapAccess::scopeDivision($filters['division_id'] ?? null);

        return CostCenter::query()
            ->with(['businessUnit', 'location', 'managementArea', 'activity', 'division'])
            ->when($filters['business_unit_id'] ?? null, fn (Builder $q) => $q->where('erkap_business_unit_id', $filters['business_unit_id']))
            ->when($filters['location_id'] ?? null, fn (Builder $q) => $q->where('erkap_location_id', $filters['location_id']))
            ->when($filters['management_area_id'] ?? null, fn (Builder $q) => $q->where('erkap_management_area_id', $filters['management_area_id']))
            ->when($filters['activity_id'] ?? null, fn (Builder $q) => $q->where('erkap_activity_id', $filters['activity_id']))
            ->when($divisionId, fn (Builder $q) => $q->where('division_id', $divisionId))
            ->when(! $divisionId && ErkapAccess::isDivisionScoped(), fn (Builder $q) => $q->where('division_id', ErkapAccess::divisionId()))
            ->orderBy('code')
            ->get()
            ->map(function (CostCenter $costCenter) {
                $segments = $costCenter->segments();

                return $this->option(
                    $costCenter,
                    $costCenter->formattedCode.' — '.$costCenter->name,
                    $segments
                ) + [
                    'is_swakelola' => $costCenter->isSwakelola(),
                    'is_centralized' => $costCenter->isCentralized(),
                    'owner' => $costCenter->owner,
                    'division_id' => $costCenter->division_id,
                ];
            });
    }

    /**
     * Divisi — cakupannya mengikuti `ErkapAccess`, dipakai form Rencana Pendapatan
     * & Beban sebagai parent COA karena tabelnya tidak punya `cost_center_id`.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function divisions(): Collection
    {
        return Division::query()
            ->when(ErkapAccess::isDivisionScoped(), fn (Builder $q) => $q->where('id', ErkapAccess::divisionId()))
            ->orderBy('name')
            ->get()
            ->map(fn (Division $division) => $this->option($division));
    }

    /**
     * COA hasil kombinasi Pusat Biaya + Elemen Biaya.
     *
     * Filter `division_id` sengaja menelusuri Pusat Biaya milik COA, bukan
     * kolom langsung, karena `chart_of_accounts` tidak menyimpan divisi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function accounts(
        ?int $costCenterId = null,
        ?int $costElementId = null,
        ?string $type = null,
        ?int $divisionId = null
    ): Collection {
        $divisionId = ErkapAccess::scopeDivision($divisionId);

        return ChartOfAccount::query()
            ->with(['costCenter', 'costElement'])
            ->when($costCenterId, fn (Builder $q) => $q->where('cost_center_id', $costCenterId))
            ->when($costElementId, fn (Builder $q) => $q->where('cost_element_id', $costElementId))
            ->when($type, fn (Builder $q) => $q->where('type', $type))
            ->when($divisionId, fn (Builder $q) => $q->whereHas(
                'costCenter',
                fn (Builder $c) => $c->where('division_id', $divisionId)
            ))
            ->orderBy('code')
            ->get()
            ->map(function (ChartOfAccount $account) {
                return $this->option(
                    $account,
                    $account->formattedCode.' — '.$account->name,
                    $account->segmented_code
                ) + [
                    'type' => $account->type,
                    'cost_center_id' => $account->cost_center_id,
                    'cost_element_id' => $account->cost_element_id,
                ];
            });
    }

    /* ---------------------------------------------------------------------
     | Reverse lookup: satu kode → seluruh rantai parent
     | ------------------------------------------------------------------ */

    /**
     * Terima kode Pusat Biaya (11 karakter) maupun kode COA (15 karakter),
     * kembalikan seluruh rantai segmen agar parent dropdown bisa ter-set
     * otomatis saat memilih entitas utuh.
     *
     * Endpoint ini menerima kode bebas dari client, jadi hasilnya ikut
     * dibatasi `ErkapAccess`: pengguna berscope divisi yang mengetik kode Pusat
     * Biaya milik divisi lain diperlakukan sama seperti kode yang tidak
     * terdaftar (`found` = false), bukan `403`. Menjawab 403 sekaligus dengan
     * kode Pusat Biaya yang valid akan membocorkan keberadaan data divisi lain.
     *
     * @return array<string, mixed>
     */
    public function lookup(string $code): array
    {
        $code = trim($code);
        $isCoa = CoaCode::valid($code);
        $isCostCenter = CoaCode::validCostCenter($code);

        if (! $isCoa && ! $isCostCenter) {
            return [];
        }

        $segments = CoaCode::parse($code);
        $costCenter = $this->findCostCenter($segments);

        if ($costCenter && ! $this->costCenterInScope($costCenter)) {
            return [];
        }

        $costElement = null;

        if ($isCoa) {
            $costElement = CostElement::query()->where('code', $segments['cost_element'])->first();
        }

        $account = $costCenter && $costElement
            ? ChartOfAccount::query()
                ->where('cost_center_id', $costCenter->id)
                ->where('cost_element_id', $costElement->id)
                ->first()
            : null;

        return [
            'segments' => $segments,
            'business_unit' => $costCenter?->businessUnit,
            'location' => $costCenter?->location,
            'management_area' => $costCenter?->managementArea,
            'activity' => $costCenter?->activity,
            'cost_element' => $costElement,
            'cost_center' => $costCenter,
            'chart_of_account' => $account,
            'code' => $account?->code ?? $costCenter?->code,
        ];
    }

    /**
     * @param  array<string, string>  $segments
     */
    private function findCostCenter(array $segments): ?CostCenter
    {
        return CostCenter::query()
            ->whereHas('businessUnit', fn (Builder $q) => $q->where('code', $segments['business_unit']))
            ->whereHas('location', fn (Builder $q) => $q->where('code', $segments['location']))
            ->whereHas('managementArea', fn (Builder $q) => $q->where('code', $segments['management_area']))
            ->whereHas('activity', fn (Builder $q) => $q->where('code', $segments['activity']))
            ->with(['businessUnit', 'location', 'managementArea', 'activity', 'division'])
            ->first();
    }

    /* ---------------------------------------------------------------------
     | Helper
     | ------------------------------------------------------------------ */

    /**
     * Bentuk opsi dropdown seragam untuk semua segmen.
     *
     * @param  array<string, string>|null  $segments
     * @return array<string, mixed>
     */
    private function option($model, ?string $label = null, ?array $segments = null): array
    {
        $code = (string) $model->code;

        return [
            'id' => $model->id,
            'code' => $code,
            'name' => $model->name,
            'label' => $label ?: $code.' — '.$model->name,
            'segments' => $segments,
        ];
    }

    /**
     * Tolak Pusat Biaya yang di luar divisi pengguna berscope.
     *
     * Berbeda dengan `costCenters()` yang cukup difilter, endpoint ini mengembalikan
     * elemen milik satu Pusat Biaya tertentu sehingga hasil untuk divisi lain
     * tidak boleh bocor meski hanya lewat ID yang diketik manual.
     */
    private function assertCostCenterInScope(?int $costCenterId): void
    {
        if (! $costCenterId || ! ErkapAccess::isDivisionScoped()) {
            return;
        }

        $belongsToDivision = CostCenter::query()
            ->whereKey($costCenterId)
            ->where('division_id', ErkapAccess::divisionId())
            ->exists();

        if (! $belongsToDivision) {
            abort(403);
        }
    }

    /**
     * Versi non-abort dari `assertCostCenterInScope()`: dipakai `lookup()` yang
     * `lookup()` memakai bentuk "tidak ditemukan", sehingga kode milik divisi
     * lain tidak terkonfirmasi keberadaannya lewat perbedaan bentuk respons.
     */
    private function costCenterInScope(CostCenter $costCenter): bool
    {
        if (! ErkapAccess::isDivisionScoped()) {
            return true;
        }

        $divisionId = ErkapAccess::divisionId();

        if ($divisionId === null) {
            return false;
        }

        return (int) $costCenter->division_id === (int) $divisionId;
    }

    /**
     * Tipe COA untuk sebuah elemen: revenue bila kode elemen diawali "6".
     */
    private function resolveElementType(CostElement $element): ?string
    {
        $account = $element->chartOfAccounts()->orderBy('code')->first();

        return $account?->type;
    }
}
