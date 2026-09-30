# Planning — Mengeluarkan Pemrosesan PT BMI dari Aplikasi E-RKAP

> Dokumen ini adalah **planning** hasil analisis codebase & struktur database modul E-RKAP.
> **Status: SELESAI DIIMPLEMENTASI** (26 Sep 2026). Tujuannya: bagian **PT BMI** tetap ada
> secara konseptual di alur RKAP, tetapi **tidak lagi diproses di dalam aplikasi** —
> diproses langsung/offline di luar aplikasi.
>
> **Deviasi penting**: DB aktual adalah **PostgreSQL** (`DB_CONNECTION=pgsql`), bukan MySQL.
> Migration dibuat dengan DDL Postgres (varchar + CHECK constraint) pada file baru
> `2026_09_26_000019_remove_bmi_from_erkap.php` (nomor `000005` dari planning sudah dipakai).

---

## 1. Ringkasan

Di codebase saat ini, PT BMI terlibat di **2 titik** dalam aplikasi:

| # | Titik | Bentuk keterlibatan |
|---|-------|---------------------|
| 1 | **Alur Periode RKAP** | Kolom & fitur `Alignment dengan PT BMI` (`bmi_alignment_status`, `bmi_notes`), diisi oleh role `erkap-gate-review` / `erkap-bmi-admin` saat fase `Finalisasi & Pengesahan`. |
| 2 | **Alur Rencana Investasi** | Stage gate ke-5 `gate_review_bmi` (reviewer `erkap-gate-review`) dan approval level 4 (`erkap-gate-review`) pada matriks `investment_plan`. |

Perubahan yang direncanakan:

1. Hapus seluruh fitur **Alignment PT BMI** dari aplikasi (field, permission, route,
   controller method, service, view).
2. Ubah alur investasi: **hapus stage gate `gate_review_bmi`** dan **hapus approval level 4**
   (`erkap-gate-review`). Alur menjadi: `PPK → Manajemen Aset → Direksi Keuangan`
   (3 level approval, 4 stage gate).
3. Hapus role `erkap-gate-review`, `erkap-bmi-admin`, dan permission `erkap.rkap.bmi`.
4. Hapus artefak PT BMI pada seed, factory, dan test.
5. Tambah **migration baru** untuk menghapus kolom DB `bmi_alignment_status` & `bmi_notes`
   serta opsi enum `gate_review_bmi` pada tabel stage gate.
6. (Opsional) Sinkronkan catatan alur di `agents/*.md` agar menyebut bahwa PT BMI
   diproses di luar aplikasi.

> Alur **RKAP (Periode) & dokumen lain** (Program Kerja, Biaya Rutin, Form 1) **tidak berubah**.

---

## 2. Temuan Analisis (Inventaris Perubahan)

### 2.1 Kode — `app/`

| File | Baris | Isi saat ini |
|------|-------|--------------|
| `app/Models/Erkap/RKAP.php` | 20–34 | `bmi_alignment_status`, `bmi_notes` di `$fillable` |
| | 55–60 | `BMI_STATUS_LABELS` |
| | 119–132 | `bmiStatusLabel()`, `bmiStatusClass()` |
| `app/Services/Erkap/RKAPLifecycleService.php` | 14 | `BMI_ROLES = ['erkap-gate-review','erkap-bmi-admin']` |
| | 16–21 | `META_FIELDS` memuat `bmi_notes` |
| | 63–68 | `resetPhase()` mereset `bmi_alignment_status`/`bmi_notes` |
| | 74–93 | `markBmiAligned()`, `assertBmiRole()` |
| `app/Services/Erkap/InvestmentGateReviewService.php` | 14–20 | `STAGES` memuat `gate_review_bmi` (order 5) |
| `app/Services/ApprovalService.php` | 32–37 | Matriks `investment_plan` level 4 = `erkap-gate-review` |
| `app/Http/Controllers/Erkap/RKAPController.php` | 8 | `use UpdateRKAPBmiRequest` |
| | 147–158 | method `bmi()` |
| `app/Http/Requests/Erkap/UpdateRKAPBmiRequest.php` | seluruh file | request khusus BMI (authorize `erkap.rkap.bmi`) |
| `app/Models/Erkap/InvestmentStageGate.php` | 54–64 | `stageLabel()` mapping `gate_review_bmi` |
| `app/Models/Erkap/Approval.php` | 65–81 | `roleLabel()` mapping `erkap-gate-review` |

### 2.2 Database — `database/migrations/`

| File | Baris | Isi saat ini |
|------|-------|--------------|
| `2026_09_26_000004_add_lifecycle_to_rkaps_table.php` | 20–22 | tambah kolom `bmi_alignment_status`, `bmi_notes` (**sudah applied** — hanya Jadi dasar migration baru) |
| `2026_09_26_000001_create_investment_stage_gates_table.php` | 14 | enum `stage` memuat `'gate_review_bmi'` (**sudah applied**) |

> Tidak ada `doctrine/dbal` di `composer.json` → **`->change()` untuk enum TIDAK tersedia**.
> Alter enum harus memakai **raw SQL** (`DB::statement`).

### 2.3 Seed & Factory

| File | Baris | Isi saat ini |
|------|-------|--------------|
| `database/seeders/RolePermissionSeeder.php` | 53–54 | role `erkap-gate-review`, `erkap-bmi-admin` |
| | 245 | permission `erkap.rkap.bmi` |
| | 290–291 | variabel role gate-review/bmi-admin |
| | 475–494 | blok `givePermissionTo` untuk keduanya (termasuk `erkap.rkap.bmi`) |
| `database/seeders/Erkap/Support/RkapSimulasi.php` | 335, 337 | `approverRoles()` memuat gate-review PT BMI & BMI Admin |
| | 357–358 | payload `initialiseRkap()` memuat `bmi_alignment_status`/`bmi_notes` |
| | 680 | komentar "Tahap 6 ... BMI ..." |
| | 703–708 | blok `markBmiAligned()` oleh `erkap-bmi-admin` |
| `database/seeders/EmployeeSeeder.php` | 44, 46–47 | employee "Dev Erkap Gate Review PT BMI", "Dev Erkap BMI Admin" (duplikat) — **berada di blok perubahan yang belum di-commit** |
| `database/factories/Erkap/InvestmentStageGateFactory.php` | 61–68 | state `gateReview()` → stage `gate_review_bmi`, role `erkap-gate-review` |

> `database/seeders/EmployeeSeeder.php` & `UserSeeder.php` sedang ada **perubahan belum di-commit**
> (user test E-RKAP). Usaha harus dilanjutkan: hapus entri BMI di blok yang sama.
> `UserSeeder.php` saat ini **tidak** men-assign role `erkap-gate-review`/`erkap-bmi-admin` ke user (minus `erkap-admin` yang sudah dihapus) — perlu dicek ulang saat implementasi.

### 2.4 Route & View

| File | Baris | Isi saat ini |
|------|-------|--------------|
| `routes/routers/erkap.php` | 210 | `POST /{rkap}/bmi` (middleware `erkap.rkap.bmi`) |
| `resources/views/erkap/rkap/index.blade.php` | 46, 60–62 | kolom "Alignment PT BMI" + badge |
| `resources/views/erkap/rkap/show.blade.php` | 272–310 | kartu "Alignment dengan PT BMI" + form simpan |
| | 341 | teks konfirmasi reset: "Seluruh catatan alignment dihapus" |
| `resources/views/erkap/partials/phase-banner.blade.php` | 20 | badge `bmiStatusClass()`/`bmiStatusLabel()` |

View `erkap/investment-gate/*` **tidak menyebut BMI eksplisit** (memakai `stageLabel()`),
jadi hanya perlu menyesuaikan jika label "Gate Review PT BMI" hilang dari model.

### 2.5 Test

| File | Keterangan |
|------|------------|
| `tests/Unit/Erkap/RKAPLifecycleTest.php` | buat role gate-review/bmi-admin; test `resetPhase` (baris 114–123); `test_mark_bmi_aligned_*` (145–179) |
| `tests/Unit/Erkap/InvestmentGateReviewServiceTest.php` | ekspektasi 5 stage (62, 67, 78); approver 4 level (127); review gate 5 (139, 171) |
| `tests/Feature/Erkap/InvestmentStageGateFeatureTest.php` | buat role gate-review (35, 69, 83); loop stage (201) |
| `tests/Feature/Erkap/RKAPLifecycleFeatureTest.php` | permission `erkap.rkap.bmi` (40, 48–49); test `test_bmi_update_*` (138–164) |
| `tests/Feature/Erkap/ErkapRkapSimulasiSeederTest.php` | baris 51 `assertSame('aligned', $rkap->bmi_alignment_status)` |

### 2.6 Dokumen di `agents/`

Tutorial & alur yang menyebut PT BMI (contoh: `agents/tutorial-pengisian-erkap.md`,
`agents/tutorial-pengisian-erkap-end-user.md`, `agents/alur-approval-erkap.md`,
`agents/recap-pedoman-rkap/*`). Ini **boleh dikoreksi** agar konsisten setelah implementasi
(lihat bagian 8), tapi **bukan syarat wajib** untuk perubahan aplikasi.

---

## 3. Keputusan Desain

1. **RKAP (Periode)**: hapus fitur alignment PT BMI sepenuhnya. Tidak ada penggantian —
   proses PT BMI dipindah ke luar aplikasi.
2. **Investasi**: hapus stage gate `gate_review_bmi` **dan** approval level 4. Alur baru:

   ```
   Rencana Investasi (submit)
     → Gate 1 Proposal Investasi        (PPK)
     → Gate 2 Kajian Kelayakan / CBA    (PPK)
     → Gate 3 Dept. Manajemen Aset      (erkap-manajemen-aset)
     → Gate 4 Direksi Keuangan          (erkap-direksi-keuangan)
     → Approved
   ```
   Matriks approval `investment_plan`: L1 `erkap-ppk` → L2 `erkap-manajemen-aset` → L3 `erkap-direksi-keuangan`.

3. Logika `InvestmentGateReviewService` (aggregate, `approveMatchingApproval`, auto-approve)
   **tetap berfungsi** dengan 4 stage/3 level — tidak perlu perubahan logika.
4. **Role & permission dihapus**: `erkap-gate-review`, `erkap-bmi-admin`, `erkap.rkap.bmi`.
   Di aplikasi tidak ada lagi referensi ke role ini.
5. **Migration baru (tidak mengubah migration lama)**: hapus kolom & opsi enum.
6. **Data lama**: baris stage gate `gate_review_bmi` yang masih ada dibersihkan saat migration;
   nilai role lama di tabel `roles`/`permissions` dibersihkan opsional via tinker/artisan
   (lihat bagian 6).

---

## 4. Rencana Perubahan per File

### 4.1 Model & Service
1. `app/Models/Erkap/RKAP.php`
   - Hapus `bmi_alignment_status`, `bmi_notes` dari `$fillable` (baris 30–31).
   - Hapus `BMI_STATUS_LABELS` (55–60).
   - Hapus `bmiStatusLabel()` & `bmiStatusClass()` (119–132).
2. `app/Services/Erkap/RKAPLifecycleService.php`
   - Hapus `BMI_ROLES` (14).
   - Hapus `bmi_notes` dari `META_FIELDS` (16–21).
   - `resetPhase()`: hapus 2 baris reset BMI (66–67). Tidak menghapus `distribution_status`.
   - Hapus `markBmiAligned()` (74–84) & `assertBmiRole()` (86–93).
3. `app/Services/Erkap/InvestmentGateReviewService.php`
   - Hapus entri `gate_review_bmi` dari `STAGES` (19). Stage tersisa 4 (`order` 1–4).
4. `app/Services/ApprovalService.php`
   - Hapus level 4 pada matriks `investment_plan` (36). Tersisa 3 level.

### 4.2 Controller & Request & Route
5. `app/Http/Controllers/Erkap/RKAPController.php`
   - Hapus `use UpdateRKAPBmiRequest` (8); hapus method `bmi()` (147–158).
6. `app/Http/Requests/Erkap/UpdateRKAPBmiRequest.php` — **hapus file**.
7. `routes/routers/erkap.php` — hapus route baris 210.

### 4.3 Model label lain
8. `app/Models/Erkap/InvestmentStageGate.php` — hapus mapping `gate_review_bmi` di `stageLabel()`.
9. `app/Models/Erkap/Approval.php` — hapus mapping `'erkap-gate-review' => 'Gate Review PT BMI'`.

### 4.4 View
10. `resources/views/erkap/rkap/index.blade.php`
    - Hapus kolom `<th>Alignment PT BMI</th>` (46) dan `<td>...bmiStatus...` (60–62).
    - Ubah `colspan="7"` → `colspan="6"` (91).
11. `resources/views/erkap/rkap/show.blade.php`
    - Hapus kartu "Alignment dengan PT BMI" (272–310).
    - Ubah teks konfirmasi reset (341) agar tidak menyebut "catatan alignment".
12. `resources/views/erkap/partials/phase-banner.blade.php` — hapus baris badge BMI (20).

### 4.5 Seed & Factory
13. `database/seeders/RolePermissionSeeder.php`
    - Hapus role `'erkap-gate-review'`, `'erkap-bmi-admin'` (53–54).
    - Hapus permission `['name' => 'erkap.rkap.bmi', ...]` (245).
    - Hapus `$erkapGateReview = ...` & `$erkapBmiAdmin = ...` (290–291).
    - Hapus blok `givePermissionTo` keduanya (475–494).
    - Catatan: `$superAdmin->syncPermissions($allPermissions)` otomatis tidak menyertakan
      `erkap.rkap.bmi` karena sudah dihapus dari daftar.
14. `database/seeders/Erkap/Support/RkapSimulasi.php`
    - Hapus `'erkap-gate-review'` & `'erkap-bmi-admin'` dari `approverRoles()` (335, 337).
    - Hapus `'bmi_alignment_status' => 'none'` & `'bmi_notes' => null` di `initialiseRkap()` (357–358).
    - Hapus blok `fillAs('erkap-bmi-admin', ... markBmiAligned ...)` di `finaliseRkap()` (703–708).
    - Perbarui komentar "Tahap 6 ... BMI ..." (680).
    - **Tidak perlu ubah** loop stage investasi — otomatis ikut 4 stage.
15. `database/seeders/EmployeeSeeder.php` (blok belum di-commit)
    - Hapus baris employee Gate Review PT BMI (44) dan BMI Admin (46–47, termasuk duplikat).
16. `database/factories/Erkap/InvestmentStageGateFactory.php`
    - Hapus state `gateReview()` (61–68) agar tidak menghasilkan nilai enum yang sudah dihapus.

---

## 5. Rencana Migration Database (file BARI) — langkah ini wajib pada implementasi

Buat file migration baru, mis. `database/migrations/2026_09_26_000005_remove_bmi_from_erkap.php`:

```php
public function up(): void
{
    Schema::table('erkap_rkap', function (Blueprint $table) {
        $table->dropColumn(['bmi_alignment_status', 'bmi_notes']);
    });

    // Bersihkan data stage gate lama PT BMI.
    DB::table('erkap_investment_stage_gates')->where('stage', 'gate_review_bmi')->delete();

    // MySQL: enum tidak bisa diubah via ->change() tanpa doctrine/dbal => raw SQL.
    DB::statement("ALTER TABLE erkap_investment_stage_gates
        MODIFY stage ENUM('proposal','cba','aset','direksi_keuangan')
        COLLATE ... CHARACTER SET ... NOT NULL"); // sesuaikan schema default DB
}

public function down(): void
{
    DB::statement("ALTER TABLE erkap_investment_stage_gates
        MODIFY stage ENUM('proposal','cba','aset','direksi_keuangan','gate_review_bmi') NOT NULL");

    Schema::table('erkap_rkap', function (Blueprint $table) {
        $table->enum('bmi_alignment_status', ['none','in_review','aligned','rejected'])->default('none');
        $table->text('bmi_notes')->nullable();
    });
}
```

> **Catatan saat implementasi**: sesuaikan `COLLATE`/`CHARACTER SET` dengan kolom original
> (cek via `SHOW CREATE TABLE erkap_investment_stage_gates`), dan tambahkan `DEFAULT` bila ada.
> Alternatif yang lebih sederhana bila ingin menghindari raw SQL: **biarkan opsi enum** `gate_review_bmi`
> di DB dan hanya hentikan pembuatan stage tsb di aplikasi. Namun keputusan yang direkomendasikan
> di sini adalah menghapus opsi enum agar DB juga konsisten dengan aplikasi.

---

## 6. Pembersihan Data Lama (opsional, pasca deploy)

Role & permission yang sudah terlanjur ada di DB (hasil seed lama) tidak terhapus otomatis
oleh `RolePermissionSeeder` (karena memakai `firstOrCreate` dan `givePermissionTo`). Untuk
membersihkan tabel `roles`, `permissions`, `role_has_permissions`, `model_has_roles`:

```bash
php artisan tinker
# 1. Hapus role
Spatie\Permission\Models\Role::whereIn('name', ['erkap-gate-review', 'erkap-bmi-admin'])->delete();
# 2. Hapus permission
Spatie\Permission\Models\Permission::where('name', 'erkap.rkap.bmi')->delete();
```

Opsional: beri **artisan command** baru mis. `php artisan erkap:purge-bmi-roles` bila diinginkan.

---

## 7. Penyesuaian Test

1. `tests/Unit/Erkap/RKAPLifecycleTest.php`
   - Buang role `erkap-gate-review` & `erkap-bmi-admin` dari helper `setUp` (baris 26).
   - Ubah assertion `resetPhase`: hapus cek `bmi_alignment_status`/`bmi_notes` (114–123).
   - Hapus test `test_mark_bmi_aligned_*` (145–179) dan helper terkait.
2. `tests/Unit/Erkap/InvestmentGateReviewServiceTest.php`
   - Ekspektasi stage jadi `['proposal','cba','aset','direksi_keuangan']` (62, 78).
   - Hapus assertion reviewer `gate_review_bmi` (67).
   - Approver matrix jadi 3 level (127): `ppk, manajemen-aset, direksi-keuangan`.
   - Hapus step review `gate_review_bmi` (139, 171) — pindahkan urutan ke 4 stage.
3. `tests/Feature/Erkap/InvestmentStageGateFeatureTest.php`
   - Buang `erkap-gate-review` dari daftar role (35, 69, 83).
   - Update loop stage (201) menjadi 4 stage.
4. `tests/Feature/Erkap/RKAPLifecycleFeatureTest.php`
   - Buang `erkap-gate-review`/`erkap-bmi-admin` & permission `erkap.rkap.bmi` (33, 40, 48–49).
   - Hapus test `test_bmi_update_*` (138–164).
5. `tests/Feature/Erkap/ErkapRkapSimulasiSeederTest.php`
   - Hapus/adjust assertion `bmi_alignment_status` (51).
6. Pastikan tidak ada lagi referensi `bmi_*`, `gate_review_bmi`, `erkap-gate-review`,
   `erkap-bmi-admin`, `erkap.rkap.bmi` di `tests/` (verifikasi via grep).

---

## 8. Penyesuaian Dokumentasi (opsional agar konsisten)

Setelah implementasi kode, sinkronkan catatan agar menyebut PT BMI **diproses di luar aplikasi**:
- `agents/alur-approval-erkap.md` — tabel matriks investasi (hilangkan level Gate Review PT BMI;
  tulis catatan "proses PT BMI dilakukan di luar aplikasi").
- `agents/tutorial-pengisian-erkap.md` & `agents/tutorial-pengisian-erkap-end-user.md` — bagian
  investasi & finalisasi: ganti "disetujui Gate Review PT BMI / alignment PT BMI di aplikasi"
  dengan catatan bahwa penyesuaian PT BMI dilakukan manual/offline.
- `agents/planning-seeder-simulasi-alur.md`, `agents/recap-pedoman-rkap/*` — update matriks
  & referensi role gate-review/bmi-admin.

---

## 9. Verifikasi — **DONE** (26 Sep 2026)

1. **Grep bersih**: `app/`, `routes/`, `database/`, `resources/views/erkap`, `tests/` tidak
   menyisakan `bmi_*`, `gate_review_bmi`, `erkap-gate-review`, `erkap-bmi-admin`, `erkap.rkap.bmi`
   (kecuali migration lama & `down()` migration baru yang disengaja).
2. **Migration**: `php artisan migrate` sukses; constraint `erkap_investment_stage_gates_stage_check`
   kini hanya `proposal,cba,aset,direksi_keuangan`; `erkap_rkap` tidak punya kolom `bmi_*`.
3. **Test**: `php vendor/bin/phpunit tests/Unit/Erkap tests/Feature/Erkap` → **116 tests OK**
   (291 assertions).
4. **Seeder simulasi**: logika investasi otomatis mengikuti 4 stage / 3 level (tanpa user BMI).
5. **Manual UI**: kartu/kolom "Alignment PT BMI" & badge BMI dihapus (index, show, phase-banner);
   Konfirmasi reset fase tidak lagi menyebut "catatan alignment".
6. **Role/Permission di DB** (opsional post-deploy): lihat bagian 6 bila perlu membersihkan
   role/permission lama yang sudah terlanjur ter-seed.

---

## 10. Risiko & Catatan

- **Enum MySQL**: os pe langkah paling rawan — pastikan charset/collation mengikuti kolom asal,
  dan data `gate_review_bmi` dibersihkan **sebelum** alter. Sandbox lalu run di DB dev.
- **Data lama di production**: RKAP yang sudah punya nilai `bmi_alignment_status` akan "kehilangan"
  kolomnya (tidak migrasi nilainya). Konfirmasi ke pemilik proses bahwa hilangnya status BMI lama
  dapat diterima (karena proses dipindah offline).
- **Role lama yang ter-assign ke user**: setelah role dihapus, user tersebut kehilangan role itu.
  Pastikan tidak ada user yang hanya mengandalkan role ini untuk login akses (mereka biasanya
  punya role lain).
- **`InvestmentGateReviewService`**: logika `aggregateStatus`, `approveMatchingApproval`,
  auto-approve saat gate terakhir approved tetap berlaku; tidak perlu modifikasi tambahan.
- Perubahan `EmployeeSeeder`/`UserSeeder` yang sudah ada di working tree (belum di-commit)
  beririsan langsung dengan penghapusan user BMI — lakukan pada blok yang sama agar tidak bentrok.

---

## 11. Urutan Pengerjaan (Checklist) — **SELESAI**

1. ✅ Model & Service: `RKAP.php`, `RKAPLifecycleService.php`, `InvestmentGateReviewService.php`,
   `ApprovalService.php`.
2. ✅ Controller/Request/Route: `RKAPController.php`, hapus `UpdateRKAPBmiRequest.php`,
   hapus route `bmi`.
3. ✅ Label: `InvestmentStageGate.php`, `Approval.php`.
4. ✅ View: `rkap/index`, `rkap/show`, `partials/phase-banner`.
5. ✅ Seed & Factory: `RolePermissionSeeder.php`, `RkapSimulasi.php`, `EmployeeSeeder.php`,
   `InvestmentStageGateFactory.php`.
6. ✅ Buat migration baru (`2026_09_26_000019_remove_bmi_from_erkap.php`).
7. ✅ Perbarui tests (bagian 7).
8. ✅ Jalankan verifikasi (bagian 9) — 116 test hijau.