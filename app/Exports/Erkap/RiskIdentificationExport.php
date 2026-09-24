<?php

namespace App\Exports\Erkap;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RiskIdentificationExport implements FromCollection, WithHeadings, WithMapping
{
    protected $riskIdentifications;
    protected $row = 0;

    public function __construct(Collection $riskIdentifications)
    {
        $this->riskIdentifications = $riskIdentifications;
    }

    public function collection()
    {
        return $this->riskIdentifications;
    }

    public function headings(): array
    {
        return [
            'No', 'Risk', 'Arah Risiko', 'Sasaran', 'Divisi', 'Rating',
            'Risk Type', 'Risk Taxonomy', 'Jumlah Program Kerja',
        ];
    }

    public function map($riskIdentification): array
    {
        $this->row++;

        $departmentTarget = $riskIdentification->departmentTarget;

        return [
            $this->row,
            $riskIdentification->risk,
            $riskIdentification->risk_direction === 'positive' ? 'Positif' : 'Negatif',
            $departmentTarget->target ?? '-',
            $departmentTarget->division->name ?? '-',
            $departmentTarget->ratingCriteria->rating ?? '-',
            $riskIdentification->riskType->name ?? '-',
            $riskIdentification->riskTaxonomy->name ?? '-',
            $riskIdentification->work_programs_count ?? 0,
        ];
    }
}