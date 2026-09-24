<?php

namespace App\Exports\Erkap;

use App\Enums\ErkapRatingLevel;
use App\Services\Form1ImportExportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Form1TemplateExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    private const MAX_ROWS = 500;

    /**
     * Valid option lists per flat column (A-P), applied as dropdown constraints.
     */
    private function validations(): array
    {
        return [
            4 => ErkapRatingLevel::values(), // D - Rating
            6 => [ // F - Positif/Negatif
                'Positif', 'Negatif',
            ],
            11 => [ // K - Probabilitas (1-5)
                '1', '2', '3', '4', '5',
            ],
            12 => [ // L - Dampak (1-5)
                '1', '2', '3', '4', '5',
            ],
            14 => [ // N - Peringkat
                'VL', 'L', 'ML', 'M', 'MH', 'H', 'VH',
            ],
            15 => [ // O - Strategi
                'Hindari', 'Kurangi', 'Berbagi', 'Terima',
            ],
        ];
    }

    public function headings(): array
    {
        return Form1ImportExportService::HEADINGS;
    }

    public function collection(): Collection
    {
        return collect([
            [
                '1',
                'Meningkatkan profitabilitas perusahaan',
                'Meningkatkan efisiensi operasional divisi',
                'A',
                'Gangguan sistem informasi keuangan',
                'Negatif',
                'Operasional',
                'Teknologi Informasi',
                'Kegagalan sistem; Kesalahan manusia',
                'Kehilangan data; Penundaan pelaporan',
                '3',
                '4',
                '12',
                'H',
                'Kurangi',
                'Program pemeliharaan sistem',
            ],
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF343A40']],
                'alignment' => ['horizontal' => 'center'],
            ],
            'A1:P'.self::MAX_ROWS => [
                'borders' => ['allBorders' => ['borderStyle' => 'thin']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getColumnDimension('A')->setWidth(5);
                foreach (['B', 'C', 'D', 'F', 'G', 'H'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(24);
                }
                foreach (['E', 'I', 'J', 'P'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(32);
                }
                foreach (['K', 'L', 'M', 'N', 'O'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(16);
                }
                $sheet->getStyle('A1:P1')->getAlignment()->setVertical('center');

                foreach ($this->validations() as $columnIndex => $options) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                    $range = "{$columnLetter}2:{$columnLetter}".self::MAX_ROWS;

                    $validation = new DataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"' . implode(',', $options) . '"');
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setErrorTitle('Nilai tidak valid');
                    $validation->setError('Pilih salah satu nilai dari daftar yang tersedia.');
                    $validation->setSqref($range);

                    $sheet->setDataValidation($range, $validation);
                }
            },
        ];
    }
}