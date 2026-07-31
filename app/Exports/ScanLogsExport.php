<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ScanLogsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $logs;

    protected $row = 0;

    public function __construct($logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs;
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
            'Status',
            'S3 Filename',
            'FTP Path',
            'Ukuran (B)',
            'Waktu Proses (ms)',
            'Pesan',
        ];
    }

    public function map($log): array
    {
        $this->row++;

        return [
            $this->row,
            optional($log->created_at)->format('Y-m-d H:i:s'),
            $log->event_label,
            $log->filename ?? '-',
            $log->document_type_name ?? '-',
            $log->document_number ?? '-',
            $log->vendor_name ?? '-',
            strtoupper($log->status),
            $log->s3_filename ?? '-',
            $log->ftp_path ?? '-',
            $log->file_size ?? '-',
            $log->processing_time_ms ?? '-',
            $log->message ?? '-',
        ];
    }
}
