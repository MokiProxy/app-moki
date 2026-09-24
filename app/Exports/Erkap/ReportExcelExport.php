<?php

namespace App\Exports\Erkap;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportExcelExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $rows;
    protected $title;

    public function __construct($rows, string $title)
    {
        $this->rows = $rows instanceof Collection ? $rows : collect($rows);
        $this->title = $title;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function headings(): array
    {
        $first = $this->rows->first();

        if (! $first) {
            return [];
        }

        return array_keys($first);
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => array_values($row));
    }
}