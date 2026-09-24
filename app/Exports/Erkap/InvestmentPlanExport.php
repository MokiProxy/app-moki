<?php

namespace App\Exports\Erkap;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvestmentPlanExport implements FromCollection, WithHeadings, WithMapping
{
    protected $investmentPlans;
    protected $row = 0;

    public function __construct(Collection $investmentPlans)
    {
        $this->investmentPlans = $investmentPlans;
    }

    public function collection()
    {
        return $this->investmentPlans;
    }

    public function headings(): array
    {
        return [
            'No', 'Program Kerja', 'Nama Investasi', 'Kategori', 'Tipe', 'Chart of Account', 'Kriteria', 'Prioritas',
            'Qty', 'Satuan', 'Harga Satuan',
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
            'Total', 'Status',
        ];
    }

    public function map($investmentPlan): array
    {
        $this->row++;

        return [
            $this->row,
            $investmentPlan->workProgram->name ?? '-',
            $investmentPlan->name,
            $investmentPlan->investattionCategory->name ?? '-',
            $investmentPlan->investationType->name ?? '-',
            $investmentPlan->chartOfAccount->code . ' - ' . $investmentPlan->chartOfAccount->name ?? '-',
            $investmentPlan->investationCriteria->name ?? '-',
            $investmentPlan->priority_order,
            $investmentPlan->qty,
            $investmentPlan->unit,
            $investmentPlan->unit_price,
            $investmentPlan->jan_plan,
            $investmentPlan->feb_plan,
            $investmentPlan->mar_plan,
            $investmentPlan->apr_plan,
            $investmentPlan->may_plan,
            $investmentPlan->jun_plan,
            $investmentPlan->jul_plan,
            $investmentPlan->aug_plan,
            $investmentPlan->sep_plan,
            $investmentPlan->oct_plan,
            $investmentPlan->nov_plan,
            $investmentPlan->dec_plan,
            $investmentPlan->total,
            $investmentPlan->statusLabel(),
        ];
    }
}