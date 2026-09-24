# Planning G4 — Implementasi Zero Based Budgeting (ZBB)

> **Gap:** G4 (Kritis) — Bagian 1
> **Prioritas:** P1 — Penting
> **Status:** Belum Diimplementasikan ❌
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 29, 48, 108–109, 170.

---

## 1. Latar Belakang

Pedoman (Bagian 1) menganut **Zero Based Budgeting**: anggaran disusun **dari nol** —
setiap rupiah harus dijustifikasi ulang, bukan meneruskan besaran tahun lalu. Harus ada
**perbandingan terhadap prior year**, **analisis persentase kenaikan**, dan **pembenaran** tiap pos.

Fakta di codebase:

- Tidak ada engine ZBB / carry-over. `previous_year_remaining` hanya dipakai sebagai tampilan pada
  ringkasan CAPEX (`App\Http\Controllers\Erkap\BudgetCapexController::summary`, `app/Http/Controllers/Erkap/BudgetCapexController.php:104`).
- Tidak ada perbandingan tahun berjalan vs tahun lalu pada alur penyusunan OPEX/CAPEX/revenue.
- `erkap_budget_opex`/`erkap_budget_capex` adalah hasil konsolidasi (draft/submitted/approved/rejected),
  tidak menyimpan nilai prior-year untuk dianalisis.

---

## 2. Tujuan

1. Menyediakan **modul analisis ZBB**: per item anggaran (routine cost / investment plan / revenue)
   menampilkan nilai prior-year, nilai usulan, selisih (Rp & %), dan **wajib justifikasi** untuk kenaikan.
2. Tidak menghapus data tahun sebelumnya — cukup **snapshot** prior-year sebagai pembanding.
3. Menyediakan **review ZBB** oleh Departemen Anggaran / Controller sebelum konsolidasi.

---

## 3. Desain Solusi

### 3.1 Basis Data

Migration `2026_09_26_000003_create_erkap_zbb_reviews_table.php`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `erkap_rkap_id` | FK `erkap_rkap` | periode usulan |
| `division_id` | FK `divisions` | |
| `subject_type` | enum `['routine_cost','investment_plan','revenue_plan','work_program']` | jenis pos |
| `subject_id` | unsignedBigInteger | id pos (polymorphic-ish, tanpa FK) |
| `display_name` | string | nama pos (denormalisasi) |
| `prior_year_amount` | decimal(20,2) default 0 | nilai tahun lalu (snapshot) |
| `proposed_amount` | decimal(20,2) default 0 | nilai usulan tahun berjalan |
| `delta_amount` | decimal(20,2) generated atau dihitung | selisih |
| `delta_percent` | decimal(8,2) generated | persentase perubahan |
| `increase_rationale` | text nullable | **justifikasi wajib** bila kenaikan |
| `zbb_status` | enum `['pending','reviewed','approved','rejected','skipped']` default `pending` | |
| `reviewed_by` | FK users nullable | |
| `reviewed_at` | timestamp nullable | |
| `review_notes` | text nullable | |
| `timestamps` | | |
| unique `(erkap_rkap_id, subject_type, subject_id)` | | |

Migration `2026_09_26_000004_add_zbb_snapshot_columns.php` (opsional ringan) pada
`erkap_routine_costs`, `erkap_investment_plans`, `erkap_revenue_plans`:

- `prior_year_amount` decimal(20,2) nullable (snapshot otomatis saat pemrosesan).

### 3.2 Service

Baru `App\Services\Erkap\ZBBReviewService`:

```php
public static function buildReviews(RKAP $rkap, RKAP|null $previousRkap): array
// 1. Ambil item anggaran dari rkap berjalan
// 2. Ambil item dari rkap sebelumnya (mapping: division + cost element/cost center/name/code)
// 3. Hitung delta & %, buat/update baris erkap_zbb_reviews dengan rationale kosong jika kenaikan
public static function requireRationale(RKAP $rkap): void
// sebelum konsolidasi: semua baris kenaikan harus punya increase_rationale (else throw)
public static function review(RKAP $rkap, int $id, User $user, array $payload): void // status+notes
public static function autoSnapshot(): void // copy current year -> prior_year snapshot (archival job)
```

**Aturan ZBB inti:**
- `delta_percent > 0` → `increase_rationale` **wajib** (tidak kosong) sebelum approve konsolidasi.
- `delta_percent <= 0` → otomatis `skipped`/tanpa wajib justifikasi.
- `zbb_status` wajib `approved` untuk seluruh baris sebelum `erkap_budget_opex/capex` di-set `submitted`
  oleh Controller (integrasi dengan alur konsolidasi yang sudah ada).

### 3.3 Pemicu (Trigger Points)

- `RoutineCostController::store/update` dan `InvestmentPlanController::store/update`,
  `RevenuePlanController::store/update`: setelah simpan, panggil `buildReviews` untuk item terkait
  (jika RKAP aktif) — atau sediakan tombol "Jalankan Analisis ZBB" manual di index ZBB.
- Command `php artisan erkap:zbb-snapshot` → `autoSnapshot()` untuk kebutuhan prior-year tahun depan
  (jadwal via `App\Console\Kernel` / scheduler). Data asli tahun berjalan tetap utuh.

### 3.4 Controller & Routes

Baru `App\Http\Controllers\Erkap\ZBBReviewController`:

- `index(Request, ?erkap_rkap_id, ?division_id, ?zbb_status)` — grid review (filter).
- `show(ZBBReview $review)` — detail selisih + input justifikasi.
- `update(UpdateZBBReviewRequest, ZBBReview $review)` — simpan rationale/status (role Controller/dept anggaran).
- `build(Request)` — POST paksa jalankan `buildReviews`.

Routes:
```php
GET  /erkap/zbb-reviews                index   permission:erkap.zbb-reviews.view
POST /erkap/zbb-reviews/build          build   permission:erkap.zbb-reviews.create
GET  /erkap/zbb-reviews/{review}       show    permission:erkap.zbb-reviews.view
PUT  /erkap/zbb-reviews/{review}       update  permission:erkap.zbb-reviews.edit
```

### 3.5 Views & Sidebar

- `resources/views/erkap/zbb-review/index.blade.php` — tabel: pos, type, prior year, proposed,
  delta Rp/%, status, tombol review; badge merah untuk kenaikan tanpa rationale.
- `resources/views/erkap/zbb-review/show.blade.php` — form justifikasi + review.
- Sidebar: grup baru "Zero Based Budgeting" (grup "Sasaran & Anggaran" atau di bawah "Anggaran Investasi"),
  `@can('erkap.zbb-reviews.view')`.

### 3.6 Permission & Role

`RolePermissionSeeder`:
- Resource `zbb-reviews` (auto `view/create/edit/delete`).
- `erkap-controller` & `erkap-admin`: full.
- `erkap-cost-owner`: view + create (input justifikasi untuk item miliknya).
- **Gate:** `BudgetOpexConsolidationService` / `BudgetCapexController::consolidate` memanggil
  `ZBBReviewService::requireRationale()`.

### 3.7 Tests

- Unit: `ZBBReviewServiceTest` — hitung delta/%, wajib rationale, mapping prior-year.
- Feature: `ZBBReviewFeatureTest` — build → rationale kosong tolak konsolidasi → isi → review approved → konsolidasi sukses.
- Test command snapshot.

---

## 4. Urutan Implementasi

1. Migration tabel ZBB (+ snapshot columns).
2. Service pengumpul data + mapper prior-year.
3. Controller + routes + views.
4. Integrasi trigger (store/update) + gate konsolidasi.
5. Command snapshot + schedule.
6. Seeder permission + tests.

---

## 5. Kriteria Penerimaan

- [ ] Modul ZBB menampilkan prior-year vs proposed + delta % per pos.
- [ ] Setiap kenaikan > 0% tidak dapat teruskan sebelum `increase_rationale` diisi.
- [ ] Konsolidasi OPEX/CAPEX gagal (error jelas) bila masih ada kenaikan tanpa justifikasi.
- [ ] Snapshot prior-year otomatis tiap pergantian tahun (command).
- [ ] Menu & permission tersedia untuk controller dan cost owner.

---

## 6. Risiko & Dependensi

- **Dependensi:** mapping prior-year butuh identitas pos yang stabil — pastikan `code` work program /
  cost element unik (lihat catatan kualitas #5 `erkap_work_programs.code` nullable). Idealnya
  dirapikan paralel dengan perbaikan uniqueness.
- **Risiko:** performa saat banyak baris — jalankan `buildReviews` secara asinkron (job) untuk rkap besar.
- **Risiko:** ambiguitas "konsolidasi" (dashboard menghitung ulang) — ZBB sebaiknya membaca
  `erkap_budget_*` sebagai sumber truth (lihat catatan kualitas #2) agar konsisten.
- **Keputusan produk:** kenaikan harus wajib justified — konfirmasi ambang (mis. >0% vs >5%) dengan pemilik bisnis.