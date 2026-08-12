<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanLog extends Model
{
    protected $table = 'scan_logs';

    protected $fillable = [
        'source',
        'event',
        'status',
        'filename',
        'extension',
        'document_type_id',
        'document_type_name',
        'document_number',
        'tanggal',
        'vendor_name',
        'keterangan',
        'uraian',
        'ftp_path',
        'file_size',
        'processing_time_ms',
        'message',
        'metadata',
        'linked_numbers',
        'ocr_text',
    ];

    protected $casts = [
        'metadata' => 'array',
        'linked_numbers' => 'array',
        'file_size' => 'integer',
        'processing_time_ms' => 'integer',
    ];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'success' => 'Sukses',
            'failed' => 'Gagal',
            'warning' => 'Peringatan',
            'skipped' => 'Dilewati',
            default => 'Info',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'success' => 'bg-success',
            'failed' => 'bg-danger',
            'warning' => 'bg-warning',
            'skipped' => 'bg-secondary',
            default => 'bg-info',
        };
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'file_detected' => 'File Terdeteksi',
            'file_skipped' => 'File Dilewati',
            'detection_failed' => 'Deteksi Gagal',
            'doc_type_detected' => 'Jenis Dokumen Terdeteksi',
            'ocr_success' => 'OCR Sukses',
            'ocr_failed' => 'OCR Gagal',
            'pdf_conversion_failed' => 'Konversi PDF Gagal',
            'ftp_upload_success' => 'Upload ke FTP Sukses',
            'ftp_upload_failed' => 'Upload ke FTP Gagal',
            'job_completed' => 'Proses Selesai',
            'job_failed' => 'Proses Gagal',
            default => ucwords(str_replace('_', ' ', $this->event)),
        };
    }

    public function getUraianDecodedAttribute()
    {
        if (is_array($this->uraian)) {
            return $this->uraian;
        }

        $decoded = json_decode($this->uraian, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->uraian;
    }
}
