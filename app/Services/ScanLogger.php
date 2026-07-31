<?php

namespace App\Services;

use App\Models\ScanLog;

class ScanLogger
{
    /**
     * Catat aktivitas scan / masuk file ke sistem.
     *
     * @param string $event kode event (contoh: 'file_detected', 'ftp_upload_success')
     * @param string $status status ('success', 'failed', 'warning', 'skipped', 'info')
     * @param array<string, mixed> $data kolom tambahan (filename, document_type_id, message, dll)
     */
    public function log(string $event, string $status = 'info', array $data = []): ScanLog
    {
        return ScanLog::create(array_merge([
            'source' => $data['source'] ?? 'scanner',
            'event' => $event,
            'status' => $status,
        ], $data));
    }
}
