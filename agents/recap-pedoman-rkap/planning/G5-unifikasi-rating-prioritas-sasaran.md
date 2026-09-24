# Planning G5 — Unifikasi Nilai Rating / Prioritas Sasaran

> **Gap:** G5 (Parsial/Konsistensi) — Bagian 2 & 4 (Form 1)
> **Prioritas:** P0 — Wajib
> **Status:** Mismatch 4 sumber ⚠️
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 30, 55, 111–119, 145, 163.

---

## 1. Latar Belakang

Nilai rating sasaran **tidak konsisten** di 4 sumber:

| Sumber | Nilai | Lokasi |
|---|---|---|
| **Pedoman** | `AAA, AA, A, BBB, BB` | bagian_2 |
| **Seeder** | `AAA, AA, A, B, BB` (tidak ada `BBB`, memakai `B`) | `database/seeders/ErkapRatingCriteriaSeeder.php:17-23` |
| **Model** | `['A+', 'A']` | `app/Models/Erkap/WorkProgram.php:83` (`booted()`) |
| **Controller** | `['AAA','AA','A']` | `app/Http/Controllers/Erkap/WorkProgramController.php:20` |
| **Template Excel Form 1** | `A+, A, BB, B, C, A-Adjusted, BBB, CCC, D` | `app/Exports/Erkap/Form1TemplateExport.php:22-26` |

Dampak konkret:
- Input "BBB" dari template Excel → `Form1ImportExportService.php:168-173` lookup
  `RatingCriteria::where('rating', 'BBB')` → `null` → import gagal.
- Program rating `A+` (default factory) lolos `booted()` padahal tidak ada di seeder.
- Program di bawah `A` (mis. `B`/`BBB`) terblokir oleh `booted()` & `ALLOWED_RATINGS`.

Selain itu **tidak ada field "Prioritas"** terpisah; prioritas disamakan dengan rating, dan label
kolom di index `department-target` memakai kata "Rating".

---

## 2. Tujuan

1. **Satu sumber kebenaran** nilai rating = pedoman: `AAA, AA, A, BBB, BB`.
2. Sinkronkan: seeder, model, controller, template & import Form 1.
3. Tambahkan **urutan prioritas** eksplisit (`priority`) agar kolom "Prioritas" bermakna sorting,
   bukan sekadar label rating.
4. Migrasi data: konversi `B → BBB` dan `A+'→ A` pada data lama.

---

## 3. Desain Solusi

### 3.1 Satu Sumber Nilai (Enum / config)

Buat class enum `App\Enums\ErkapRatingLevel` (atau `app/Enum`) — pastikan sesuai konvensi folder
`app/Enums` yang ada:

```php
enum ErkapRatingLevel: string
{
    case AAA = 'AAA';
    case AA  = 'AA';
    case A   = 'A';
    case BBB = 'BBB';
    case BB  = 'BB';

    public function label(): string
    {
        return match($this) {
            self::AAA => 'Sangat Penting (AAA)',
            self::AA  => 'Sangat Penting (AA)',
            self::A   => 'Sangat Penting (A)',
            self::BBB => 'Penting (BBB)',
            self::BB  => 'Cukup Penting (BB)',
        };
    }

    public function order(): int { /* 1..5 urutan prioritas */ }
    public static function values(): array { ... }
    public static function allowedForWorkProgram(): array { return [self::AAA, self::AA, self::A]; }
}
```

Semua konsumen memakai enum ini (bukan array hardcoded).

### 3.2 Perbaikan per Sumber

1. **Seeder** `ErkapRatingCriteriaSeeder::run()` → pakai `ErkapRatingLevel::values()`:
   `AAA, AA, A, BBB, BB` + deskripsi sesuai pedoman. Data handling: `updateOrCreate(['rating'])`.
2. **Model** `app/Models/Erkap/WorkProgram.php:83` → `$validRatings = ErkapRatingLevel::allowedForWorkProgram();`
   (nilai berubah: `['AAA','AA','A']`).
3. **Controller** `WorkProgramController::ALLOWED_RATINGS` → hapus konstanta, ganti dengan
   `ErkapRatingLevel::allowedForWorkProgram()` (hapus `A+`).
4. **Template Form 1** `Form1TemplateExport::VALIDATIONS[4]` → dataset dropdown persis nilai enum
   (dan contoh baris default ganti `'A+'` → `'A'`).
5. **Import Form 1** `Form1ImportExportService` tidak berubah logikanya, tapi lookup kini berhasil
   untuk `AAA/AA/A/BBB/BB`. Tambahkan **validasi ramah**: bila rating tidak dikenal, pesan error
   menampilkan daftar nilai valid dari enum.

### 3.3 Kolom Prioritas

Migration `2026_09_26_000005_add_priority_to_department_targets_table.php`:

- `erkap_department_targets.priority` smallint nullable `after('erkap_rating_criteria_id')` — urutan
  prioritas sasaran (1 = tertinggi) per divisi.

Update `App\Models\Erkap\DepartmentTarget`:
- tambah `priority` ke `$fillable`;
- helper `priorityLabel(): string` (mengambil `ratingCriteria.rating` + urutan);
- index view `department-target` menampilkan kolom **"Prioritas"** (bukan "Rating"):
  - tampilkan `ratingCriteria.rating` + `#priority` (mis. `A (1)`).

`StoreDepartmentTargetRequest`/`UpdateDepartmentTargetRequest` (`app/Http/Requests`): validasi
`priority` nullable integer `min:1` + uniqueness per `division_id` bila diisi (rule `unique` dengan
where division). Sorting index default `orderBy('priority')`.

### 3.4 Migrasi Data

Migration data (Postgres, pattern lambat tetapi aman):

```sql
UPDATE erkap_rating_criterias SET rating='BBB' WHERE rating='B';
UPDATE erkap_department_targets t
  SET erkap_rating_criteria_id = r.id
  FROM erkap_rating_criterias r
  WHERE r.rating='BBB' AND EXISTS (
    SELECT 1 FROM erkap_rating_criterias old
    WHERE old.id = t.erkap_rating_criteria_id AND old.rating IN ('A+','B')
  );
-- hapus baris rating A+/B jika masih tersisa (atau konversi lalu delete)
```

Lakukan dengan menjalankan ulang seeder `DatabaseSeeder` pada staging + script SQL eksplisit untuk
produksi. Catat risiko factory default `A+` (cek `database/factories`). Factory diizinkan, cukup
model guard memakai enum.

### 3.5 Audit/perilaku lain yang menyentuh rating

Grep & align seluruh tempat hardcoded rating: `rating > 'A'`, `A+`, `'B'`:
- `app/Models/Erkap/WorkProgram.php` (`booted`)
- `app/Http/Controllers/Erkap/WorkProgramController.php`
- `tests/Unit/Erkap/WorkProgramBusinessRulesTest.php` (sudah `['AAA','AA','A']` — samakan source)
- `app/Exports/Erkap/*` bila ada label rating
- `app/Imports/Erkap/Form1Import.php` bila ada kolom rating
- Seeder lain yg menyuntik `A+`.

---

## 4. Urutan Implementasi

1. Enum `ErkapRatingLevel`.
2. Update seeder + jalankan migration data (konversi B→BBB, buang A+).
3. Update model, controller, template, import.
4. Kolom `priority` + request + view label "Prioritas".
5. User acceptance test Form 1 import dengan nilai BBB.
6. Update semua test terkait rating.

---

## 5. Kriteria Penerimaan

- [ ] `RatingCriteria` berisi persis `AAA, AA, A, BBB, BB` (satu sumber=enum).
- [ ] Import Form 1 dengan rating `BBB` berhasil; dengan rating tidak dikenal memberi pesan daftar valid.
- [ ] Program kerja hanya bisa dibuat untuk rating `AAA/AA/A`.
- [ ] `A+` tidak ada di manapun (DB, controller, template, test).
- [ ] Kolom "Prioritas" tampil pada index `department-target` dengan urutan sort.
- [ ] Seluruh test ERKAP berkaitan rating lulus.

---

## 6. Risiko & Dependensi

- **Risiko:** data produksi sudah `B` dijadikan `BBB`, tapi `BBB < A` → program kerja terkait **akan
  terblokir** oleh `booted()` setelah perbaikan. Komunikasikan ke pemilik bisnis; alternatif: pertahankan
  status program yang sudah ada (validasi hanya pada pembuatan).
- **Risiko:** skenario "prioritas" belum diminta di Form 1 Excel — pastikan kolom Prioritas opsional
  di template, tidak memaksa import gagal.
- **Dependensi:** G8/G9 menggunakan `priority`/ordering — urutan implementasi G5 sebelum G9.