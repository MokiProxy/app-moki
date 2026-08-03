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

class ScanLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnWidths, WithCustomStartCell, WithEvents, WithProperties, WithTitle
{
    protected $logs;

    protected $filters;

    protected $row = 0;

    protected $lastColumn;

    public function __construct($logs, string $filters = '')
    {
        $this->logs = $logs;
        $this->filters = $filters;
        $this->lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
    }

    public function title(): string
    {
        return 'Log File';
    }

    public function properties(): array
    {
        return [
            'title' => 'Laporan Log File',
            'description' => 'Laporan aktivitas log file - '.now()->format('d-m-Y H:i:s'),
            'creator' => 'Aplikasi Dokter',
        ];
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function columnWidths(): array
    {
        return [
            'D' => 38,
            'I' => 45,
            'K' => 45,
            'N' => 55,
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Waktu',
            'Event',
            'Nama File',
            'Jenis Dokumen',
            'Nomor Dokumen',
            'Vendor',
            'Keterangan',
            'Uraian',
            'Status',
            'FTP Path',
            'Ukuran',
            'Waktu Proses',
            'Pesan',
        ];
    }

    public function map($log): array
    {
        $this->row++;

        return [
            $this->row,
            optional($log->created_at)->format('d-m-Y H:i:s'),
            $log->event_label,
            $log->filename ?? '-',
            $log->document_type_name ?? '-',
            $log->document_number ?? '-',
            $log->vendor_name ?? '-',
            $log->keterangan ?? '-',
            $log->uraian ?? '-',
            $log->status_label,
            $log->ftp_path ?? '-',
            $this->formatBytes($log->file_size),
            $log->processing_time_ms ? number_format($log->processing_time_ms, 0, ',', '.').' ms' : '-',
            $log->message ?? '-',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $this->lastColumn;
                $lastDataRow = 3 + count($this->logs);

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
        $sheet->setCellValue('A1', 'LAPORAN LOG FILE');
        $sheet->mergeCells("A1:{$lastColumn}1");

        $meta = 'Dibuat: '.now()->format('d-m-Y H:i:s');
        if ($this->filters !== '') {
            $meta .= ' | Filter: '.$this->filters;
        }
        $meta .= ' | Total Data: '.count($this->logs);
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
            'success' => ['fill' => 'C6EFCE', 'font' => '006100'],
            'failed' => ['fill' => 'FFC7CE', 'font' => '9C0006'],
            'warning' => ['fill' => 'FFEB9C', 'font' => '9C6500'],
            'skipped' => ['fill' => 'E7E6E6', 'font' => '808080'],
            'info' => ['fill' => 'DDEBF7', 'font' => '2E74B5'],
        ];

        foreach ($this->logs as $i => $log) {
            $r = 4 + $i;

            if ($r % 2 === 0) {
                $sheet->getStyle("A{$r}:{$lastColumn}{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('F2F7FC'));
            }

            $colors = $statusColors[$log->status] ?? null;
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
        $sheet->getStyle("L4:L{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("M4:M{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    protected function writeFooterRow($sheet, string $lastColumn, int $lastDataRow): void
    {
        $footerRow = $lastDataRow + 1;

        $sheet->setCellValue("A{$footerRow}", 'TOTAL DATA');
        $sheet->mergeCells("A{$footerRow}:J{$footerRow}");
        $sheet->setCellValue("K{$footerRow}", count($this->logs));
        $sheet->mergeCells("K{$footerRow}:{$lastColumn}{$footerRow}");

        $sheet->getStyle("A{$footerRow}:{$lastColumn}{$footerRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E2F3']],
            'font' => ['bold' => true, 'color' => ['rgb' => '1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B4C6E7']]],
        ]);
    }

    protected function formatBytes($bytes): string
    {
        if (!$bytes) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return number_format($size, $i === 0 ? 0 : 1, ',', '.').' '.$units[$i];
    }
}
