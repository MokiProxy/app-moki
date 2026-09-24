<?php

namespace App\Exports\Erkap;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RoutineCostExport implements FromCollection, WithHeadings, WithMapping
{
    protected $routineCosts;
    protected $row = 0;

    public function __construct(Collection $routineCosts)
    {
        $this->routineCosts = $routineCosts;
    }

    public function collection()
    {
        return $this->routineCosts;
    }

    public function headings(): array
    {
        return [
            'No', 'Program Kerja', 'Kebutuhan', 'Elemen Biaya', 'Chart of Account', 'Pusat Biaya',
            'Tipe',
            'Qty', 'Satuan', 'Harga Satuan',
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
            'Total', 'Status',
        ];
    }

    public function map($routineCost): array
    {
        $this->row++;

        return [
            $this->row,
            $routineCost->workProgram->name ?? '-',
            $routineCost->need,
            $routineCost->costElement->name ?? '-',
            $routineCost->chartOfAccount->code . ' - ' . $routineCost->chartOfAccount->name ?? '-',
            $routineCost->costCenter->name ?? '-',
            $routineCost->costCenter && $routineCost->costCenter->isCentralized()
                ? 'Terpusat (' . ($routineCost->costCenter->coordinatingDivision->name ?? '-') . ')'
                : 'Non-Terpusat',
            $routineCost->qty,
            $routineCost->units,
            $routineCost->unit_price,
            $routineCost->jan_cost,
            $routineCost->feb_cost,
            $routineCost->mar_cost,
            $routineCost->apr_cost,
            $routineCost->may_cost,
            $routineCost->jun_cost,
            $routineCost->jul_cost,
            $routineCost->aug_cost,
            $routineCost->sep_cost,
            $routineCost->oct_cost,
            $routineCost->nov_cost,
            $routineCost->des_cost,
            $routineCost->total,
            $routineCost->statusLabel(),
        ];
    }
}