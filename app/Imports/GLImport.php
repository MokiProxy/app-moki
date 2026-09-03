<?php

namespace App\Imports;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GLImport implements WithMultipleSheets
{
    public array $result = [];
    protected string $filePath;

    public function __construct(UploadedFile $file)
    {
        $this->filePath = $file->getRealPath();
    }

    public function sheets(): array
    {
        $spreadsheet = IOFactory::load($this->filePath);
        $firstSheetName = $spreadsheet->getSheetNames()[0] ?? 'Sheet1';

        return [
            $firstSheetName => new GLSheetImport($firstSheetName, $this),
        ];
    }
}
