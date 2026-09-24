<?php

namespace App\Exports\Erkap;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DrilldownExport implements FromCollection, WithHeadings, WithTitle
{
    protected $rows;
    protected $title;

    public function __construct(Collection $rows, string $title)
    {
        $this->rows = $rows;
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

        return array_keys(is_array($first) ? $first : $first->getAttributes());
    }

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            if (is_array($row)) {
                return array_values($row);
            }

            $attributes = $row->getAttributes();
            $values = [];

            foreach ($this->headings() as $column) {
                $values[] = $row->{$column} ?? ($attributes[$column] ?? null);
            }

            return $values;
        });
    }
}