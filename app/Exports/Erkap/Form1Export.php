<?php

namespace App\Exports\Erkap;

use App\Services\Form1ImportExportService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class Form1Export implements FromCollection, WithHeadings, WithMapping
{
    protected Collection $risks;

    protected Form1ImportExportService $service;

    public function __construct(Collection $risks)
    {
        $this->risks = $risks;
        $this->service = app(Form1ImportExportService::class);
    }

    public function collection()
    {
        return $this->risks;
    }

    public function headings(): array
    {
        return Form1ImportExportService::HEADINGS;
    }

    public function map($risk): array
    {
        $row = $this->service->exportRow($risk, 0);

        return [
            $row['no'],
            $row['company_target'],
            $row['department_target'],
            $row['rating'],
            $row['risk'],
            $row['risk_direction'],
            $row['risk_type'],
            $row['risk_taxonomy'],
            $row['reasons'],
            $row['impacts'],
            $row['probability'],
            $row['impact'],
            $row['score'],
            $row['level'],
            $row['strategy'],
            $row['work_program'],
        ];
    }
}