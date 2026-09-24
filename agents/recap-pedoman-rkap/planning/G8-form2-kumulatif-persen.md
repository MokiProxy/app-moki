# Planning G8 — Form 2: Jadwal Rencana Kerja dalam Persentase Kumulatif (%)

> **Gap:** G8 (Parsial) — Bagian 4 (Form 2)
> **Prioritas:** P1 — Penting
> **Status:** Parsial ⚠️
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 33, 76, 131, 166.

---

## 1. Latar Belakang

Pedoman **Form 2** menuntut target **persentase kumulatif Jan–Des**: di bulan k, tampil akumulasi
rencana s/d bulan k terhadap target tahunan (%). Fakta saat ini:

- `erkap_work_schedules` menyimpan `year_plan` + `jan_plan..dec_plan` sebagai **angka rencana**
  (migration `2026_09_22_094439_create_work_schedules_table.php`).
- Index view `work-schedule` hanya menampilkan **list** tanpa kolom bulanan / kumulatif.
  Lihat `resources/views/erkap/work-schedule/index.blade.php` (perlu verifikasi).
- Konsep **% kumulatif** tidak ada di model maupun view.

---

## 2. Tujuan

1. Menampilkan **persentase kumulatif Jan–Des** pada Work Schedule (rencana kumulatif % tiap bulan).
2. (Opsional lanjutan) membandingkan **realisasi kumulatif %** berbasis `ProgramRealization`/
   `BudgetRealization` bila ada.
3. Menambahkan kolom kumulatif % pada **ekspor Form 2** bila tersedia.

---

## 3. Desain Solusi

### 3.1 Komputasi di Model (Tanpa Migrasi Data)

`App\Models\Erkap\WorkSchedule` tambahkan:

```php
public const MONTH_COLUMNS = ['jan_plan','feb_plan','mar_plan','apr_plan','may_plan','jun_plan',
                              'jul_plan','aug_plan','sep_plan','oct_plan','nov_plan','dec_plan'];

public function monthlyCumulativePercents(): array
{
    $total = (float) $this->year_plan;
    $running = 0.0;
    $result = [];
    foreach (self::MONTH_COLUMNS as $m) {
        $running += (float) ($this->$m ?? 0);
        $result[$m] = $total > 0
            ? round(($running / $total) * 100, 2)
            : 0;
    }
    return $result;
}

public function cumulativePercentAt(string $month): float // single bulan
```

> Desain: nilai tetap disimpan sebagai **angka bulanan** (sumber kebenaran), % kumulatif
> **dikomputasi** saat ditampilkan → tidak ada duplikasi/inkonsistensi data.
> Alternatif simpan kolom `jan_cum..dec_cum` bila dipakai akses cepat — **tidak direkomendasikan**
> karena duplikasi; pakai accessor.

### 3.2 View Index Work Schedule

Update `resources/views/erkap/work-schedule/index.blade.php`:

- Tabel kolom: Program Kerja, Aktivitas, Tahun target, dan **12 kolom % kumulatif (Jan–Des)**
  (header `Jan %` … `Des %`), diisi `$schedule->monthlyCumulativePercents()`.
- Sebelum tambah kolom bulanan (opsional kecil: kolom angka plan) untuk konteks.
- Tambah partial helper `erkap/partials/monthly-percent-row` agar dipakai ulang di detail.

### 3.3 Ekspor (bila ada export Form 2 / work schedule)

- Periksa apakah ada `WorkScheduleExport`/`Form2TemplateExport`; jika ya, tambahkan deret
  `cum_jan..cum_dec`. Jika tidak ada export Form 2, **tambahkan export sederhana**
  (pola `App\Exports\Erkap\WorkScheduleCumulativeExport`) berisi: Program, Aktivitas,
  12 kolom angka rencana, 12 kolom % kumulatif.

### 3.4 Realisasi Kumulatif (%) — fase lanjutan (P2)

- `App\Models\Erkap\WorkSchedule` relasi `realizations` bila ada kolom bulanan realisasi
  (`ProgramRealization` saat ini milik `WorkProgram` — `work_programs.realizations()`).
- Susun `actualCumulativePercents()`: ambil realisasi per bulan dari `ProgramRealization`
  (kolom `month`) yang menunjuk `work_program_id`, hitung % kumulatif terhadap `year_plan`.
- Diagram/bar mini per baris (chart simple SVG) dibanding rencana vs realisasi.

### 3.5 Form Input

- Form `work-schedule/create` & `edit` saat ini memakai input angka per bulan. Tambahkan **live
  preview** baris "% Kumulatif" yang dihitung client-side (JS kecil) — bukan mengubah cara simpan.

---

## 4. Urutan Implementasi

1. Accessor model `monthlyCumulativePercents()` (+ test unit).
2. Index view Work Schedule (kolom 12 × % kumulatif).
3. Export Form 2 (jika belum ada) + kolom %
4. (Opsional) realisasi kumulatif & grafik.
5. Live preview di form.
6. Tests update `tests/Unit/Erkap/WorkScheduleTest.php` + `WorkScheduleFeatureTest.php`.

---

## 5. Kriteria Penerimaan

- [ ] Index Work Schedule menampilkan % kumulatif tiap bulan; Des = 100% bila total = year_plan.
- [ ] Menghitung pembagian-oleh-nol aman (`year_plan=0` → 0%).
- [ ] Export Form 2 menyertakan kolom % kumulatif.
- [ ] Test unit menyamakan: plan `[1..12]` masing-masing 10 (total 120), cum Jan=8.33% … Des=100%.
- [ ] `year_plan` validasi berjalan (Σ bulanan = tahunan) tetap berlaku.

---

## 6. Risiko & Dependensi

- **Risiko:** interpretasi "kumulatif (%)" bisa berbasis realisasi vs rencana — klarifikasi dengan
  pemilik bisnis; default implementasi = % dari rencana tahunan (`year_plan`).
- **Dependensi:** realisasi bulanan memakai `ProgramRealization` — kolom bulan perlu diverifikasi
  (apakah menyimpan bulan numeric). Bila tidak ada, realisasi kumulatif jadi **opsional** (P2).
- **Risiko performa:** 12 kolom × banyak baris — gunakan eager load & batasi kolom render bila tabel besar.