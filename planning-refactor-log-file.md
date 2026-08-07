# Planning Refactor Log File

## 1. Overview

### Tujuan
Merapikan tampilan halaman Log File di modul Dokter:
1. **Hapus field "Uraian"** — dari tabel maupun export Excel
2. **Sederhanakan tabel** — tampilkan hanya kolom penting, tambahkan tombol **View** untuk melihat detail lengkap via modal

### Problem Saat Ini
- Tabel Log File memiliki **13 kolom** — terlalu lebar, sulit dibaca di layar
- Field "Uraian" sudah tidak dibutuhkan
- Tidak ada mekanisme detail view — semua data ditampilkan sekaligus di tabel

---

## 2. Analisis Kodebase Saat Ini

### Database: Table `scan_logs`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | Primary key |
| `source` | string | Sumber event (default: scanner) |
| `event` | string | Jenis event OCR/proses |
| `status` | string | success/failed/warning/skipped/info |
| `filename` | string | Nama file |
| `extension` | string | Ekstensi file |
| `document_type_id` | bigint | FK ke document_types |
| `document_type_name` | string | Nama jenis dokumen |
| `document_number` | string | Nomor dokumen |
| `vendor_name` | string | Nama vendor |
| `tanggal` | string | Tanggal dokumen |
| `keterangan` | string | Keterangan |
| `uraian` | string | **[HAPUS]** Uraian — sudah tidak dibutuhkan |
| `ftp_path` | string | Path file di FTP |
| `file_size` | bigint | Ukuran file (bytes) |
| `processing_time_ms` | int | Waktu proses (ms) |
| `message` | text | Pesan log |
| `metadata` | json | Metadata tambahan |
| `linked_numbers` | json | Nomor terkait (migration baru) |
| `ocr_text` | text | Hasil OCR (migration baru) |

### File yang Terpengaruh
| No | File | Perubahan |
|----|------|-----------|
| 1 | `resources/views/dokter/log-file/index.blade.php` | Hapus kolom Uraian, sembunyikan kolom detail, tambah tombol View + modal |
| 2 | `app/Exports/ScanLogsExport.php` | Hapus kolom Uraian dari headings & map |
| 3 | `app/Http/Controllers/Dokter/LogFileController.php` | Hapus `uraian` dari search filter |
| 4 | `app/Models/ScanLog.php` | (Opsional) Hapus `uraian` dari `$fillable` |

---

## 3. Perubahan Detail

### 3.1 Hapus Field "Uraian"

**File: `app/Exports/ScanLogsExport.php`**
- Hapus `'Uraian'` dari array `headings()`
- Hapus `$log->uraian ?? '-'` dari method `map()`
- Update `columnWidths()` — geser index kolom setelah Uraian dihapus
- Update `styleDataRows()` — sesuaikan index kolom status (dari 11 → 10 karena kolom J/Uraian hilang)

**File: `app/Http/Controllers/Dokter/LogFileController.php`**
- Hapus `->orWhere('uraian', 'like', "%{$search}%")` dari method `applyFilters()`

**File: `resources/views/dokter/log-file/index.blade.php`**
- Hapus kolom `<th>Uraian</th>` dan `<td>` terkait

### 3.2 Sederhanakan Tabel — Tampilkan Kolom Penting Saja

**Kolom yang Ditampilkan di Tabel (Simplified):**

| No | Kolom | Lebar | Keterangan |
|----|-------|-------|------------|
| 1 | No | 50px | Nomor urut |
| 2 | Waktu Scan | 160px | `created_at` format `d-m-Y H:i:s` |
| 3 | Status | 110px | Badge status |
| 4 | Nama File | auto | Nama file + ekstensi badge |
| 5 | Jenis Dokumen | - | Nama jenis dokumen |
| 6 | Vendor | - | Nama vendor |
| 7 | Aksi | 90px | Tombol **View** |

**Kolom yang Disembunyikan dari Tabel (detail di modal):**
- Nomor Dokumen
- Tanggal
- Keterangan
- FTP Path
- Waktu Proses
- Pesan

### 3.3 Tambah Modal Detail

**Komponen Modal:**
```
┌──────────────────────────────────────────────┐
│  📄 Detail Log File                    [✕]  │
├──────────────────────────────────────────────┤
│  No. Dokumen    : INV-2026-001              │
│  Waktu Scan     : 07-08-2026 14:30:22       │
│  Status         : ✅ Sukses                  │
│  Nama File      : invoice_vendor_a.pdf      │
│  Jenis Dokumen  : Invoice                   │
│  Nomor Dokumen  : INV-2026-001              │
│  Tanggal        : 07-08-2026                │
│  Vendor         : Vendor A                  │
│  Keterangan     : Invoice bulanan           │
│  FTP Path       : INVOICE/Vendor_A/file.pdf │
│  Ukuran File    : 1.2 MB                    │
│  Waktu Proses   : 1,234 ms                  │
│  Pesan          : File berhasil diproses    │
└──────────────────────────────────────────────┘
```

**Data yang ditampilkan di modal (tanpa Uraian):**
1. Waktu Scan
2. Status (badge)
3. Nama File (+ ekstensi)
4. Jenis Dokumen
5. Nomor Dokumen
6. Tanggal
7. Vendor
8. Keterangan
9. FTP Path
10. Ukuran File
11. Waktu Proses
12. Pesan

### 3.4 Update Export Excel

**Kolom Excel Setelah Perubahan (13 → 14 → 13 kolom, hapus Uraian):**

| No | Kolom | Lebar |
|----|-------|-------|
| A | No | auto |
| B | Waktu | auto |
| C | Event | auto |
| D | Nama File | 38 |
| E | Jenis Dokumen | auto |
| F | Nomor Dokumen | auto |
| G | Tanggal | auto |
| H | Vendor | auto |
| I | Keterangan | 45 |
| J | Status | auto |
| K | FTP Path | 45 |
| L | Ukuran | auto |
| M | Waktu Proses | auto |
| N | Pesan | 55 |

> Catatan: Kolom "Uraian" (sebelumnya kolom J) dihapus. Index kolom setelahnya bergeser.

---

## 4. UX Flow

### Flow Lihat Detail
```
1. User buka halaman Log File
2. Tabel muncul dengan kolom: No, Waktu Scan, Status, Nama File, Jenis Dokumen, Vendor, Aksi
3. Klik tombol 🔍 View di baris tertentu
4. Modal detail muncul menampilkan semua field (kecuali Uraian)
5. Klik ✕ atau klik di luar modal untuk menutup
```

### Flow Export Excel
```
1. Klik tombol "Export Excel"
2. File terdownload TANPA kolom Uraian
3. Kolom yang tersisa: No, Waktu, Event, Nama File, Jenis Dokumen, Nomor Dokumen, Tanggal, Vendor, Keterangan, Status, FTP Path, Ukuran, Waktu Proses, Pesan
```

---

## 5. Edge Cases

| Case | Penanganan |
|------|------------|
| Data Uraian masih ada di DB | Tidak dihapus dari DB, hanya tidak ditampilkan di view/export |
| Field nullable | Tampilkan `-` jika null |
| Mobile responsive | Modal full-width di mobile, tabel tetap scrollable |
| Search masih mencari Uraian | Hapus dari search query |

---

## 6. File yang Perlu Diupdate

| No | File | Perubahan |
|----|------|-----------|
| 1 | `resources/views/dokter/log-file/index.blade.php` | Simplify table + tambah modal detail + hapus kolom Uraian |
| 2 | `app/Exports/ScanLogsExport.php` | Hapus kolom Uraian + update index kolom |
| 3 | `app/Http/Controllers/Dokter/LogFileController.php` | Hapus `uraian` dari search filter |

---

## 7. Testing Checklist

- [ ] Tabel hanya menampilkan 7 kolom (No, Waktu Scan, Status, Nama File, Jenis Dokumen, Vendor, Aksi)
- [ ] Tombol View muncul di setiap baris
- [ ] Klik tombol View → modal detail muncul dengan data lengkap
- [ ] Modal menampilkan semua field kecuali Uraian
- [ ] Kolom Uraian tidak ada di tabel
- [ ] Kolom Uraian tidak ada di export Excel
- [ ] Search tidak mencari Uraian lagi
- [ ] Filter (tanggal, status, jenis dokumen) masih berfungsi
- [ ] Export Excel kolom sudah sesuai (tanpa Uraian)
- [ ] Pagination masih berfungsi
- [ ] Responsive di mobile

---

## 8. Estimasi Waktu

| No | Task | Estimasi |
|----|------|----------|
| 1 | Update view — simplify table + modal | 1.5 jam |
| 2 | Update export — hapus Uraian + fix index | 0.5 jam |
| 3 | Update controller — hapus dari search | 0.25 jam |
| 4 | Testing | 0.5 jam |
| **Total** | | **2.75 jam** |
