<?php

namespace App\Exports\Erkap;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WorkProgramExport implements FromCollection, WithHeadings, WithMapping
{
    protected $workPrograms;
    protected $row = 0;

    public function __construct(Collection $workPrograms)
    {
        $this->workPrograms = $workPrograms;
    }

    public function collection()
    {
        return $this->workPrograms;
    }

    public function headings(): array
    {
        return [
            'No', 'Program Kerja', 'Sasaran', 'Divisi', 'Rating', 'Satuan', 'Tahunan',
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
            'Kum Jan %', 'Kum Feb %', 'Kum Mar %', 'Kum Apr %', 'Kum Mei %', 'Kum Jun %',
            'Kum Jul %', 'Kum Agu %', 'Kum Sep %', 'Kum Okt %', 'Kum Nov %', 'Kum Des %',
            'Status',
        ];
    }

    public function map($workProgram): array
    {
        $this->row++;

        $departmentTarget = $workProgram->riskIdentification?->departmentTarget;
        $cumulativePercents = $workProgram->monthlyCumulativePercents();

        return [
            $this->row,
            $workProgram->name,
            $departmentTarget->target ?? '-',
            $departmentTarget->division->name ?? '-',
            $departmentTarget->ratingCriteria->rating ?? '-',
            $workProgram->units,
            $workProgram->year_plan,
            $workProgram->jan_plan,
            $workProgram->feb_plan,
            $workProgram->mar_plan,
            $workProgram->apr_plan,
            $workProgram->may_plan,
            $workProgram->jun_plan,
            $workProgram->jul_plan,
            $workProgram->aug_plan,
            $workProgram->sep_plan,
            $workProgram->oct_plan,
            $workProgram->nov_plan,
            $workProgram->dec_plan,
            $cumulativePercents['jan_plan'],
            $cumulativePercents['feb_plan'],
            $cumulativePercents['mar_plan'],
            $cumulativePercents['apr_plan'],
            $cumulativePercents['may_plan'],
            $cumulativePercents['jun_plan'],
            $cumulativePercents['jul_plan'],
            $cumulativePercents['aug_plan'],
            $cumulativePercents['sep_plan'],
            $cumulativePercents['oct_plan'],
            $cumulativePercents['nov_plan'],
            $cumulativePercents['dec_plan'],
            $workProgram->statusLabel(),
        ];
    }
}