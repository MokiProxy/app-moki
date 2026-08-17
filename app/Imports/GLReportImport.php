<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GLReportImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'PPNMO' => new PPNSheetImport('PPNMO'),
            'PPNHO' => new PPNSheetImport('PPNHO'),
        ];
    }
}
