# Planning: Implementasi Sistem Retry Deteksi Jenis Dokumen

## 1. Ringkasan Masalah

Saat ini, ketika proses deteksi jenis dokumen gagal (menghasilkan `null`), sistem langsung memindahkan file ke folder `FAILED/` di FTP Final. Tidak ada mekanisme retry untuk melakukan OCR ulang guna deteksi jenis dokumen. Padahal, kegagalan deteksi bisa terjadi karena:
- OCR API mengembalikan teks yang tidak lengkap/bermasalah
- Gambar/PDF kualitas rendah yang membutuhukan pemrosesan ulang
- Gangguan sementara pada OCR API

## 2. Analisis Arsitektur Saat Ini

### Alur Deteksi Saat Ini
```
MonitorScanner (app:monitor-scanner)
  ↓
Polling ftp_scanner (/incoming)
  ↓
Untuk setiap file:
  → detectDocumentType():
    → Download file ke local storage (scanner/incoming/)
    → Buat UploadedFile instance
    → OcrService::extractText() → panggil OCR.space API (1x panggilan)
    → Iterasi semua DocumentType, panggil matchHeader() (regex matching)
    → return DocumentType | null
  ↓
Jika null → langsung uploadToFailedFolder() → file dipindah ke FAILED/
```

### File/Komponen Terkait
| Komponen | Path | Peran |
|----------|------|-------|
| **MonitorScanner.php** | `app/Console/Commands/Dokter/MonitorScanner.php` | Orchestrator utama, polling FTP, deteksi jenis dokumen |
| **ProcessScanFile.php** | `app/Jobs/ProcessScanFile.php` | Queue job OCR, upload ke FTP Final |
| **OcrService.php** | `app/Services/OcrService.php` | Panggil OCR.space API |
| **DocumentTypeProcessor.php** | `app/Services/DocumentTypeProcessor.php` | Ekstraksi field terstruktur dari teks OCR |
| **FileConversionService.php** | `app/Services/FileConversionService.php` | Konversi format gambar/PDF |
| **DocumentType.php** | `app/Models/DocumentType.php` | Model: regex patterns, metadata jenis dokumen |
| **ScanLogger.php** | `app/Services/ScanLogger.php` | Logging audit trail proses scan |

### Retry Logic yang Sudah Ada
- **FTP Upload Retry**: `ProcessScanFile.php` punya manual retry loop 3x untuk upload FTP
- **Laravel Queue Retry**: `ProcessScanFile.php` punya `$tries = 3` untuk queue job retry
- **FAILED Folder Upload Retry**: `MonitorScanner.php` punya retry loop 3x untuk upload ke FAILED folder

### Yang Belum Ada (Gap)
- **Tidak ada retry untuk OCR API call** — `OcrService::extractText()` hanya 1x panggilan
- **Tidak ada retry untuk deteksi jenis dokumen** — `detectDocumentType()` hanya 1x percobaan
- **Tidak ada retry untuk PDF conversion** — `FileConversionService` tanpa retry

## 3. Spesifikasi Fitur Retry

### Alur yang Diinginkan
```
MonitorScanner (app:monitor-scanner)
  ↓
Polling ftp_scanner (/incoming)
  ↓
Untuk setiap file:
  ↓
  ┌─── Loop MAX_RETRIES (5 kali) ───────────────────────┐
  │  → detectDocumentType():                            │
  │    → Download file ke local storage                 │
  │    → OcrService::extractText()                      │
  │    → Iterasi DocumentType, matchHeader()            │
  │    → return DocumentType | null                     │
  │                                                     │
  │  Jika DocumentType != null → PROSES BERHASIL        │
  │    → break loop, lanjut ke proses selanjutnya       │
  │                                                     │
  │  Jika null → log attempt, sleep interval            │
  │    → retry dari awal (OCR ulang)                    │
  └─────────────────────────────────────────────────────┘
  ↓
Jika setelah 5x tetap null:
  → uploadToFailedFolder() → file dipindah ke FAILED/
  → log gagal dengan info jumlah percobaan
```

### Parameter Konfigurasi
| Parameter | Nilai Default | Keterangan |
|-----------|---------------|------------|
| `MAX_RETRIES` | 5 | Maksimum percobaan OCR + deteksi |
| `RETRY_INTERVAL` | 3 detik | Jeda antar percobaan (detik) |
| `FAILED_FOLDER` | `FAILED/` | Folder tujuan file gagal di FTP Final |

### Behavior
1. **Setiap percobaan retry**: Melakukan OCR dari awal (panggil `OcrService::extractText()` ulang)
2. **Sleep antar retry**: Memberikan jeda untuk menghindari rate limit OCR API
3. **Logging**: Setiap percobaan dicatat di `scan_logs` dengan info attempt number
4. **Setelah 5x gagal**: Baru file dipindahkan ke `FAILED/`
5. **File dihapus dari scanner**: File hanya dihapus dari `ftp_scanner` setelah proses selesai (berhasil atau gagal permanen)

## 4. Detail Implementasi

### 4.1 Modifikasi `MonitorScanner.php`

#### 4.1.1 Tambah Property Konfigurasi
```php
private int $maxRetries = 5;
private int $retryInterval = 3; // detik
```

#### 4.1.2 Modifikasi Method `handle()`
Ubah alur pemrosesan file agar mendukung retry loop:

```php
// Sebelumnya (pseudocode):
foreach ($files as $file) {
    $documentType = $this->detectDocumentType($file);
    if (is_null($documentType)) {
        $this->uploadToFailedFolder($file);
        continue;
    }
    $this->processScanFile($file, $documentType);
}

// Sesudahnya (pseudocode):
foreach ($files as $file) {
    $documentType = null;
    $lastAttempt = 0;

    for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
        $lastAttempt = $attempt;
        $documentType = $this->detectDocumentType($file);

        if (!is_null($documentType)) {
            break; // Berhasil, keluar dari loop
        }

        if ($attempt < $this->maxRetries) {
            Log::info("Deteksi jenis dokumen gagal (percobaan {$attempt}/{$this->maxRetries}), retry dalam {$this->retryInterval} detik", [
                'file' => $file['filename'],
            ]);
            sleep($this->retryInterval);
        }
    }

    if (is_null($documentType)) {
        Log::warning("Deteksi jenis dokumen gagal setelah {$this->maxRetries} percobaan, memindahkan ke FAILED", [
            'file' => $file['filename'],
            'total_attempts' => $this->maxRetries,
        ]);
        $this->uploadToFailedFolder($file, $lastAttempt);
        continue;
    }

    $this->processScanFile($file, $documentType);
}
```

#### 4.1.3 Modifikasi Method `detectDocumentType()`
Tambah parameter attempt number untuk logging:

```php
private function detectDocumentType(array $file, int $attempt = 1): ?DocumentType
{
    // Existing logic tetap sama
    // Tambah logging untuk setiap attempt
    Log::info("Memulai deteksi jenis dokumen (percobaan {$attempt})", [
        'file' => $file['filename'],
    ]);

    // ... existing OCR + regex matching logic ...

    return $documentType; // bisa null atau DocumentType instance
}
```

#### 4.1.4 Modifikasi Method `uploadToFailedFolder()`
Tambah parameter attempt count untuk logging:

```php
private function uploadToFailedFolder(array $file, int $totalAttempts = 1): bool
{
    // Existing logic tetap sama
    // Tambah info totalAttempts ke log
    Log::warning("Memindahkan file ke FAILED folder", [
        'file' => $file['filename'],
        'total_attempts' => $totalAttempts,
    ]);

    // ... existing FTP upload logic ...
}
```

### 4.2 Modifikasi `ProcessScanFile.php` (Opsional)

Jika `ProcessScanFile` juga melakukan deteksi jenis dokumen via `resolveDocumentType()`, pertimbangkan untuk menambahkan retry serupa. Namun karena di `MonitorScanner` sudah dilakukan deteksi sebelum dispatch job, retry di sini mungkin tidak diperlukan. Evaluate dulu apakah `resolveDocumentType()` di `ProcessScanFile` juga bisa gagal secara terpisah.

### 4.3 Modifikasi `ScanLogger.php` (Opsional)

Tambahkan event type baru untuk logging retry:

```php
// Event types yang sudah ada:
// 'detection_started', 'detection_success', 'detection_failed', 'ocr_completed', dll.

// Event baru:
// 'detection_retry' — dicatat setiap kali retry dilakukan
```

### 4.4 Tidak Perlu Diubah
- **OcrService.php** — Tidak diubah, biarkan caller yang handle retry
- **DocumentTypeProcessor.php** — Tidak diubah
- **FileConversionService.php** — Tidak diubah
- **DocumentType.php** — Tidak diubah
- **config/filesystems.php** — Tidak diubah
- **config/services.php** — Tidak diubah

## 5. Dampak & Risiko

### Risiko
| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| OCR API rate limit | Retry 5x bisa memicu rate limit | Interval 3 detik antar retry; monitor rate limit OCR API |
| Latensi proses meningkat | File membutuhkan waktu lebih lama untuk diproses | Gunakan queue job agar tidak blocking; log durasi setiap attempt |
| FTP connection timeout | Koneksi FTP ke scanner bisa putus saat retry | `detectDocumentType()` sudah disconnect/reconnect; pastikan file tidak corrupt |
| Storage lokal penuh | File di-download ulang ke local storage setiap retry | Bersihkan file temporer setelah proses selesai |

### Dampak Positif
- Mengurangi jumlah file yang salah masuk ke `FAILED/`
- Meningkatkan akurasi deteksi jenis dokumen
- Memberikan visibilitas lebih baik melalui logging

## 6. Checklist Testing

- [ ] **Unit Test**: Test bahwa loop retry berhenti saat deteksi berhasil
- [ ] **Unit Test**: Test bahwa file dipindah ke FAILED setelah 5x gagal
- [ ] **Unit Test**: Test bahwa OCR dipanggil ulang pada setiap retry
- [ ] **Integration Test**: Test alur lengkap dengan mock OCR API
- [ ] **Manual Test**: Upload dokumen dengan kualitas rendah, verifikasi retry bekerja
- [ ] **Manual Test**: Cek log untuk memastikan setiap attempt tercatat
- [ ] **Manual Test**: Pastikan file yang berhasil terdeteksi pada attempt ke-N tidak masuk FAILED

## 7. Estimate Waktu

| Komponen | Estimasi |
|----------|----------|
| Modifikasi `MonitorScanner.php` | 1-2 jam |
| Logging & monitoring | 0.5 jam |
| Testing (manual + unit) | 1-2 jam |
| **Total** | **2.5 - 4.5 jam** |

## 8. Prioritas

**P0 (High)** — Fitur ini meningkatkan reliability sistem secara signifikan dan mengurangi jumlah file yang perlu di-handle manual.

## 9. Referensi

- `app/Console/Commands/Dokter/MonitorScanner.php` — File utama yang akan dimodifikasi
- `app/Services/OcrService.php` — Service OCR yang dipanggil
- `app/Models/DocumentType.php` — Model jenis dokumen
- `app/Services/ScanLogger.php` — Service logging
- `config/filesystems.php` — Konfigurasi FTP disks
