<?php

namespace App\Services;

use App\Models\MasterPegawaiHirarki;
use App\Models\PegawaiHirarki;
use App\Models\PegawaiMasterPosisi;
use Illuminate\Database\Eloquent\Collection;

class HierarchyService
{
    /**
     * Build flattened hierarchy cache (superior_1..superior_8) for a position.
     */
    public function buildPositionHierarchy(PegawaiMasterPosisi $position): MasterPegawaiHirarki
    {
        $superiors = [];
        $current = $position;

        for ($i = 1; $i <= 8; $i++) {
            if ($current->superior_id === null) {
                break;
            }
            $superiors["superior_{$i}"] = $current->superior_id;
            $current = PegawaiMasterPosisi::find($current->superior_id);
            if ($current === null) {
                break;
            }
        }

        $data = array_merge(['position_id' => $position->position_id], $superiors);

        return MasterPegawaiHirarki::updateOrCreate(
            ['position_id' => $position->position_id],
            $data
        );
    }

    /**
     * Fill employee hierarchy fields from the position cache.
     */
    public function buildEmployeeHierarchy(PegawaiHirarki $employee): void
    {
        $cache = MasterPegawaiHirarki::find($employee->position_id);
        if ($cache === null) {
            return;
        }

        $data = [];

        for ($i = 1; $i <= 8; $i++) {
            $superiorId = $cache->{"superior_{$i}"};
            if ($superiorId === null) {
                continue;
            }

            $data["superior_{$i}"] = $superiorId;

            $superiorPegawai = PegawaiHirarki::where('position_id', $superiorId)->first();
            if ($superiorPegawai !== null) {
                $data["nopeg_hier{$i}"] = $superiorPegawai->nopeg;
                $data["employee_id_hier{$i}"] = $superiorPegawai->employee_id;
                $data["nik{$i}"] = $superiorPegawai->nik;
                $data["nama_hier{$i}"] = $superiorPegawai->nama;
                $data["ilinier{$i}"] = $superiorPegawai->nama;
                $data["email{$i}"] = $superiorPegawai->email;
                $data["jabatan{$i}"] = $superiorPegawai->jabatan0;
                $data["kode_satker{$i}"] = $superiorPegawai->kode_satker;
            }
        }

        if (!empty($data)) {
            $employee->update($data);
        }
    }

    /**
     * Get superiors chain for a position from cache.
     */
    public function getSuperiors(string $positionId): Collection
    {
        $cache = MasterPegawaiHirarki::find($positionId);
        if ($cache === null) {
            return new Collection();
        }

        $superiorIds = [];
        for ($i = 1; $i <= 8; $i++) {
            $sid = $cache->{"superior_{$i}"};
            if ($sid !== null) {
                $superiorIds[] = $sid;
            }
        }

        if (empty($superiorIds)) {
            return new Collection();
        }

        $placeholders = implode(',', array_fill(0, count($superiorIds), '?'));
        return PegawaiMasterPosisi::whereIn('position_id', $superiorIds)
            ->orderByRaw("FIELD(position_id, {$placeholders})", $superiorIds)
            ->get();
    }

    /**
     * Get employee superiors chain by NIK.
     */
    public function getEmployeeSuperiors(string $employeeId): Collection
    {
        $employee = PegawaiHirarki::where('employee_id', $employeeId)->first();
        if ($employee === null) {
            return new Collection();
        }

        $superiorIds = [];
        for ($i = 1; $i <= 8; $i++) {
            $sid = $employee->{"superior_{$i}"};
            if ($sid !== null) {
                $superiorIds[] = $sid;
            }
        }

        if (empty($superiorIds)) {
            return new Collection();
        }

        $placeholders = implode(',', array_fill(0, count($superiorIds), '?'));
        return PegawaiMasterPosisi::whereIn('position_id', $superiorIds)
            ->orderByRaw("FIELD(position_id, {$placeholders})", $superiorIds)
            ->get();
    }
}
