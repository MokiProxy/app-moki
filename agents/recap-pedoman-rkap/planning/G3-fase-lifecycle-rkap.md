# Planning G3 — Fase Lifecycle RKAP (Inisiasi → Penyusunan → Konsolidasi → Finalisasi/Pengesahan)

> **Gap:** G3 (Kritis) — Bagian 5
> **Prioritas:** P1 — Penting
> **Status:** Belum Diimplementasikan ❌
> **Referensi:** `agents/recap-pedoman-rkap/hasil-analisis-gap.md` baris 28, 84–88, 104–106, 170.

---

## 1. Latar Belakang

Pedoman (Bagian 5) mendefinisikan alur RKAP berfase:

1. **Persiapan & Inisiasi** (top-down): aspirasi holding, arahan direksi, buku pedoman, kick-off/sosialisasi.
2. **Penyusunan** (bottom-up): Form 1–4, evaluasi fungsional.
3. **Konsolidasi & Review**: dokumen ke Dept Anggaran, konsolidasi keuangan, rapat berjenjang antar unit usaha.
4. **Finalisasi & Pengesahan**: presentasi Direksi & Dewan Komisaris, alignment PT BMI, pengesahan & distribusi.

Fakta di codebase:

- `RKAP` hanyalah record `year` (+ `status` draft/submitted/approved/rejected/revised, `company_id`).
  Lihat `app/Models/Erkap/RKAP.php`.
- `RKAPController` hanya CRUD + submit approval (`app/Http/Controllers/Erkap/RKAPController.php`).
- Tidak ada modul kick-off/sosialisasi, arahan direksi, alignment PT BMI, distribusi dokumen.

---

## 2. Tujuan

1. Menambahkan **fase lifecycle** `erkap_rkap` yang bisa dipandu step-by-step.
2. Menyediakan **agenda kick-off/sosialisasi & arahan direksi** per periode RKAP.
3. Menyediakan **titik alignment PT BMI** dan **status distribusi / pengesahan**.
4. Membuat **status dokumen terkunci** pada fase tertentu (mis. fase Finalisasi → input dilarang).

---

## 3. Desain Solusi

### 3.1 Migrasi

Migration `2026_09_26_000002_add_lifecycle_to_rkaps_table.php` pada `erkap_rkap`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `phase` | enum `['initiation','preparation','consolidation','finalization','approved','archived']` default `initiation` | fase lifecycle |
| `phase_started_at` | timestamp nullable | kapan fase dimulai |
| `kickoff_date` | date nullable | jadwal kick-off |
| `kickoff_notes` | text nullable | |
| `direction_file_path` | string nullable | lampiran arahan direksi / memo holding |
| `direction_notes` | text nullable | |
| `bmi_alignment_status` | enum `['none','in_review','aligned','rejected']` default `none` | alignment PT BMI |
| `bmi_notes` | text nullable | |
| `resolution_date` | date nullable | tanggal pengesahan |
| `distribution_status` | enum `['not_distributed','distributed']` default `not_distributed` | |

Migration `create_erkap_kickoff_attendees_table` (opsional) untuk daftar hadir kick-off:
`id, erkap_rkap_id FK, name, division_id nullable, attended boolean, timestamps`.

### 3.2 Model & Enum

- Update `App\Models\Erkap\RKAP`:
  - `$fillable` + casts (`phase`, Tanggal, dsb).
  - Konstanta fase & label helper:
    ```php
    public const PHASES = ['initiation','preparation','consolidation','finalization','approved','archived'];
    public function phaseLabel(): string
    public function canTransitionTo(string $next): bool  // urutan wajib berurutan
    public function isLockedForInput(): bool              // phase >= 'finalization'
    ```
  - relasi `kickoffAttendees()`.

### 3.3 Service

Baru `App\Services\Erkap\RKAPLifecycleService`:

- `advance(RKAP $rkap, ?array $meta): void` — validasi `canTransitionTo`, simpan `phase_started_at`, audit log.
- `markBmiAligned(RKAP $rkap, User $user, array $payload): void` — hanya role `erkap-gate-review`/`erkap-bmi-admin`.
- `distribute(RKAP $rkap, User $user): void` — set `distribution_status`, kirim notifikasi.
- `resetPhase(RKAP $rkap): void` — hanya saat draft/rejected.
- Jalankan **guard batch** pada fase: `RoutineCostController::store/update`, `InvestmentPlanController`, `WorkScheduleController`, `RevenuePlanController` → blokir input saat `isLockedForInput()` (cek rkap aktif via tahun kerja program → company target → rkap; atau deklarasi `erkap_rkap_id` aktif di scope).

### 3.4 Controller & Routes

Update `RKAPController`:

- `show(RKAP $rkap)` — timeline fase + form kick-off + alignment BMI + distribusi.
- `advance(RKAP $rkap)` (POST) — `phase → next`.
- `kickoff(RKAP $rkap, Request)` (POST) — simpan tanggal/notes + hadir.
- `direction(Request, RKAP)` (POST) — upload file arahan direksi.
- `bmi(Request, RKAP)` (POST) — only role BMI.
- `distribute(Request, RKAP)` (POST).

Routes:
```php
POST /erkap/rkap/{rkap}/advance        name=rkap.advance       permission:erkap.rkap.edit
POST /erkap/rkap/{rkap}/kickoff        name=rkap.kickoff       permission:erkap.rkap.edit
POST /erkap/rkap/{rkap}/direction      name=rkap.direction     permission:erkap.rkap.edit
POST /erkap/rkap/{rkap}/bmi            name=rkap.bmi           permission:erkap.rkap.bmi
POST /erkap/rkap/{rkap}/distribute     name=rkap.distribute    permission:erkap.rkap.edit
GET  /erkap/rkap/{rkap}                name=rkap.show          permission:erkap.rkap.view
```

### 3.5 Views & Sidebar

- `resources/views/erkap/rkap/show.blade.php` — stepper 6 fase, card kick-off, upload arahan direksi,
  status BMI, tombol distribusi, daftar hadir.
- Banner peringatan saat fase `finalization`/`approved` di index semua modul anggaran
  (partial `erkap.partials.phase-banner`).
- Sidebar `resources/views/layouts/partials/erkap/app-sidebar.blade.php` grup "Periode RKAP" tetap,
  tambah akses `show`.

### 3.6 Permissions

`RolePermissionSeeder`:
- `erkap.rkap.bmi` untuk `erkap-gate-review` (+ `erkap-bmi-admin` bila ada).
- `erkap-controller`/`erkap-admin` dapat advance fase.

### 3.7 Tests

- Unit: `RKAPLifecycleTest` — transisi fase berurutan, tolak lompat, `isLockedForInput`.
- Feature: `RKAPLifecycleFeatureTest` — kick-off → advance → input terblokir → alignment BMI → distribusi.

---

## 4. Urutan Implementasi

1. Migration lifecycle + attendees.
2. Model enum + service.
3. `show` + advance; form kick-off & arahan direksi.
4. Alignment BMI + distribusi.
5. Guard input per fase di controller anggaran.
6. Sidebar, permission, tests.

---

## 5. Kriteria Penerimaan

- [ ] Periode RKAP memiliki fase yang dapat di-advance berurutan.
- [ ] Kick-off + arahan direksi tercatat (file + note + peserta).
- [ ] Setelah masuk fase finalisasi, seluruh form anggaran tidak bisa ditambah/diubah (validasi muncul).
- [ ] Alignment PT BMI dapat diisi oleh role BMI; distribusi menandai dokumen tersebar.
- [ ] Timeline fase tampil di halaman detail RKAP.

---

## 6. Risiko & Dependensi

- **Dependensi:** G2 (role BMI/review) — role gate review dipakai alignment.
- **Risiko:** penentuan "rkap aktif" untuk guard input ambigu (tabel `erkap_rkap` bisa multi tahun).
  Pakai default tahun berjalan atau kolom `is_active` baru; dokumentasikan kebijakannya di awal.
- **Risiko:** memblokir input bisa mengganggu revisi — pastikan fase `archived` dapat di-`resetPhase`
  saat rejected.