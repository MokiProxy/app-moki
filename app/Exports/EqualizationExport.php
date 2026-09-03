<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EqualizationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnWidths, WithCustomStartCell, WithEvents, WithProperties, WithTitle
{
    protected $data;

    protected $summary;

    protected $row = 0;

    protected $lastColumn;

    public function __construct(Collection $data, array $summary)
    {
        $this->data = $data;
        $this->summary = $summary;
        $this->lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
    }

    public function title(): string
    {
        return 'Ekualisasi Pajak';
    }

    public function properties(): array
    {
        return [
            'title' => 'Laporan Ekualisasi Pajak',
            'description' => 'Laporan ekualisasi SPT Coretax vs General Ledger - '.$this->summary['masa_pajak'].' '.$this->summary['tahun'],
            'creator' => 'Aplikasi EQTax',
        ];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 30,
            'C' => 30,
            'D' => 20,
            'E' => 18,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 14,
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'No Faktur Pajak',
            'Nama Penjual',
            'NPWP',
            'DPP SPT',
            'DPP GL',
            'PPN SPT',
            'PPN GL',
            'Selisih PPN',
            'Status',
        ];
    }

    public function map($item): array
    {
        $this->row++;

        return [
            $this->row,
            $item->no_faktur_pajak ?? '-',
            $item->nama_penjual ?? '-',
            $item->npwp_penjual ?? '-',
            $item->dpp_spt ?? 0,
            $item->dpp_gl ?? 0,
            $item->ppn_spt ?? 0,
            $item->ppn_gl ?? 0,
            $item->selisih_ppn ?? 0,
            $item->status ?? '-',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $this->lastColumn;
                $lastDataRow = 3 + count($this->data);

                $this->writeTitleBlock($sheet, $lastColumn);
                $this->styleHeaderRow($sheet, $lastColumn);
                $this->styleDataRows($sheet, $lastColumn, $lastDataRow);
                $this->writeFooterRow($sheet, $lastColumn, $lastDataRow);

                $sheet->freezePane('A4');
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getRowDimension(3)->setRowHeight(22);
            },
        ];
    }

    protected function writeTitleBlock($sheet, string $lastColumn): void
    {
        $sheet->setCellValue('A1', 'LAPORAN EKUALISASI PAJAK');
        $sheet->mergeCells("A1:{$lastColumn}1");

        $meta = 'Masa Pajak: '.$this->summary['masa_pajak'].' | Tahun: '.$this->summary['tahun'];
        $meta .= ' | Dibuat: '.now()->format('d-m-Y H:i:s');
        $sheet->setCellValue('A2', $meta);
        $sheet->mergeCells("A2:{$lastColumn}2");

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F4E79']],
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'DDEBF7']],
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '2E74B5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    protected function styleHeaderRow($sheet, string $lastColumn): void
    {
        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2E74B5']],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9DC3E6']]],
        ]);
    }

    protected function styleDataRows($sheet, string $lastColumn, int $lastDataRow): void
    {
        if ($lastDataRow < 4) {
            return;
        }

        $statusColumn = Coordinate::stringFromColumnIndex(10);
        $statusColors = [
            'MATCH' => ['fill' => 'C6EFCE', 'font' => '006100'],
            'SPT_ONLY' => ['fill' => 'FFEB9C', 'font' => '9C6500'],
            'GL_ONLY' => ['fill' => 'FFC7CE', 'font' => '9C0006'],
        ];

        foreach ($this->data as $i => $item) {
            $r = 4 + $i;

            if ($r % 2 === 0) {
                $sheet->getStyle("A{$r}:{$lastColumn}{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('F2F7FC'));
            }

            $colors = $statusColors[$item->status] ?? null;
            if ($colors) {
                $sheet->getStyle("{$statusColumn}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colors['fill']]],
                    'font' => ['bold' => true, 'color' => ['rgb' => $colors['font']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }

        $sheet->getStyle("A4:{$lastColumn}{$lastDataRow}")->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
        ]);

        $sheet->getStyle("A4:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$statusColumn}4:{$statusColumn}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['E', 'F', 'G', 'H', 'I'] as $col) {
            $sheet->getStyle("{$col}4:{$col}{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("{$col}4:{$col}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    protected function writeFooterRow($sheet, string $lastColumn, int $lastDataRow): void
    {
        $footerRow = $lastDataRow + 1;

        $sheet->setCellValue("A{$footerRow}", 'TOTAL');
        $sheet->mergeCells("A{$footerRow}:D{$footerRow}");

        $totalDppSpt = $this->data->sum('dpp_spt');
        $totalDppGl = $this->data->sum('dpp_gl');
        $totalPpnSpt = $this->data->sum('ppn_spt');
        $totalPpnGl = $this->data->sum('ppn_gl');
        $totalSelisih = $this->data->sum('selisih_ppn');

        $sheet->setCellValue("E{$footerRow}", $totalDppSpt);
        $sheet->setCellValue("F{$footerRow}", $totalDppGl);
        $sheet->setCellValue("G{$footerRow}", $totalPpnSpt);
        $sheet->setCellValue("H{$footerRow}", $totalPpnGl);
        $sheet->setCellValue("I{$footerRow}", $totalSelisih);
        $sheet->setCellValue("J{$footerRow}", count($this->data).' data');
        $sheet->mergeCells("J{$footerRow}:{$lastColumn}{$footerRow}");

        $sheet->getStyle("A{$footerRow}:{$lastColumn}{$footerRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E2F3']],
            'font' => ['bold' => true, 'color' => ['rgb' => '1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B4C6E7']]],
        ]);

        foreach (['E', 'F', 'G', 'H', 'I'] as $col) {
            $sheet->getStyle("{$col}{$footerRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("{$col}{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }
}
