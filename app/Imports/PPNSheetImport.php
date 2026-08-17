<?php

namespace App\Imports;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PPNSheetImport implements WithMultipleSheets
{
    public array $result = [];
    protected string $filePath;

    public function __construct(UploadedFile $file)
    {
        $this->filePath = $file->getRealPath();
    }

    public function sheets(): array
    {
        $sheetsArray = [];

        $spreadsheet = IOFactory::load($this->filePath);

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $sheetsArray[$sheetName] = new PPNSingleSheetImport($sheetName, $this);
        }

        return $sheetsArray;
    }
}
