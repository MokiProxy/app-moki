# Planning Implementasi Hirarki Table

> Berdasarkan analisis dari `Laravel_Hierarchy_Table_Implementation_Planning.md`

## Ringkasan

Implementasi 4 tabel untuk menyimpan struktur organisasi bertingkat (hirarki posisi dan pegawai) menggunakan pendekatan **adjacency list** + **materialized hierarchy cache** (superior_1..superior_8) agar query hirarki berjalan cepat tanpa recursive query.

---

## Tahapan Implementasi

### Phase 1: Database Migration (4 migrations)

| Urutan | Nama Tabel | Primary Key | Catatan |
|--------|-----------|-------------|---------|
| 1 | `pegawai_satker` | `kode_satker` (string) | Master satuan kerja |
| 2 | `pegawai_master_posisi` | `position_id` (string) | Adjacency list dengan FK `superior_id` ke dirinya sendiri |
| 3 | `master_pegawai_hirarki` | `position_id` (FK) | Cache hirarki posisi, kolom `superior_1` s.d. `superior_8` |
| 4 | `pegawai_hirarki` | `id` (auto increment) | Snapshot pegawai + rantai atasan 8 level |

**Aturan migration:**
- Gunakan `Schema::create()` dan `Schema::dropIfExists()` agar rollback aman
- Foreign key constraints menggunakan `->constrained()->cascadeOnDelete()`
- Index pada kolom yang sering di-query (`position_id`, `nik`, `superior_id`, `kode_satker`)
- Timestamps wajib di semua tabel
- Tidak menggunakan soft delete

### Phase 2: Eloquent Models (4 models)

| Model | Tabel | Relasi Utama |
|-------|-------|--------------|
| `PegawaiSatker` | `pegawai_satker` | `hasMany(PegawaiHirarki)` |
| `PegawaiMasterPosisi` | `pegawai_master_posisi` | `parent()`, `children()`, `hierarchy()` |
| `MasterPegawaiHirarki` | `master_pegawai_hirarki` | `belongsTo(PegawaiMasterPosisi)` |
| `PegawaiHirarki` | `pegawai_hirarki` | `belongsTo(PegawaiMasterPosisi)`, `belongsTo(PegawaiSatker)` |

**Detail Model:**

#### 1. PegawaiSatker (`app/Models/PegawaiSatker.php`)
- `$fillable`: `kode_satker`, `nama_satker`
- `$primaryKey`: `kode_satker`
- `$incrementing`: `false`
- `$keyType`: `string`

#### 2. PegawaiMasterPosisi (`app/Models/PegawaiMasterPosisi.php`)
- `$fillable`: `position_id`, `superior_id`, `pos_title`, `last_mode_date`, `last_mode_time`
- `$primaryKey`: `position_id`
- `$incrementing`: `false`
- `$keyType`: `string`
- Relation `parent()`: `belongsTo(self::class, 'superior_id', 'position_id')`
- Relation `children()`: `hasMany(self::class, 'superior_id', 'position_id')`
- Relation `hierarchy()`: `hasOne(MasterPegawaiHirarki::class, 'position_id', 'position_id')`

#### 3. MasterPegawaiHirarki (`app/Models/MasterPegawaiHirarki.php`)
- `$fillable`: `position_id`, `superior_1` s.d. `superior_8`
- `$primaryKey`: `position_id`
- `$incrementing`: `false`
- `$keyType`: `string`
- Relation `position()`: `belongsTo(PegawaiMasterPosisi::class, 'position_id', 'position_id')`

#### 4. PegawaiHirarki (`app/Models/PegawaiHirarki.php`)
- `$fillable`: semua kolom (position_id, nik, nopeg, nama, email, jabatan0, kode_satker, superior_1..superior_8, nik1..nik8, nopeg_hier1..nopeg_hier8, nama_hier1..nama_hier8, ilinier1..ilinier8, email1..email8, jabatan1..jabatan8, kode_satker1..kode_satker8)
- Relation `posisi()`: `belongsTo(PegawaiMasterPosisi::class, 'position_id', 'position_id')`
- Relation `satker()`: `belongsTo(PegawaiSatker::class, 'kode_satker', 'kode_satker')`

### Phase 3: Factory (1 factory)

Buat `PegawaiMasterPosisiFactory` untuk generating data posisi dummy.

### Phase 4: Seeder (1 seeder dedicated + update DatabaseSeeder)

**Struktur organisasi contoh:**

```
CEO (Dedi)                                ← level 0
└── GM IT (Agus)                          ← level 1
    └── Manager Development (Toni)        ← level 2
        └── Supervisor Backend (Andi)     ← level 3
            └── Programmer Backend (Budi) ← level 4
```

Tambahkan satker dan 5 pegawai (Dedi, Agus, Toni, Andi, Budi) dengan data dummy.

**Seeder wajib idempotent** (gunakan `firstOrCreate` atau `updateOrCreate`).

### Phase 5: HierarchyService (`app/Services/HierarchyService.php`)

| Method | Input | Output | Deskripsi |
|--------|-------|--------|-----------|
| `buildPositionHierarchy()` | `PegawaiMasterPosisi $position` | `MasterPegawaiHirarki` | Build cache superior_1..superior_8 untuk suatu posisi |
| `buildEmployeeHierarchy()` | `PegawaiHirarki $employee` | `void` | Isi field superior_n..kode_satkern dari cache posisi |
| `getSuperiors(positionId)` | `string $positionId` | `Collection` | Dapatkan semua atasan dari suatu posisi (via cache) |
| `getEmployeeSuperiors(nik)` | `string $nik` | `Collection` | Dapatkan rantai atasan seorang pegawai |

**Logika `buildPositionHierarchy()`:**
1. Mulai dari posisi yang diberikan
2. Traverse ke atas melalui `superior_id` hingga 8 level (atau sampai null)
3. Simpan di `superior_1` (atasan langsung) hingga `superior_8` (atasan tertinggi)
4. Insert/update ke tabel `master_pegawai_hirarki`

**Logika `buildEmployeeHierarchy()`:**
1. Ambil `position_id` dari pegawai
2. Cari `master_pegawai_hirarki` berdasarkan position_id tsb
3. Copy field `superior_1..superior_8` dari master ke `pegawai_hirarki`
4. Untuk setiap superior_id, ambil data pegawai dari `pegawai_hirarki` atau dari sumber data pegawai
5. Isi `nikn`, `nopeg_hiern`, `nama_hiern`, `iliniern`, `emailn`, `jabatann`, `kode_satkern`

---

## Struktur Folder Final

```
database/
├── migrations/
│   ├── YYYY_MM_DD_HHMMSS_create_pegawai_satker_table.php
│   ├── YYYY_MM_DD_HHMMSS_create_pegawai_master_posisi_table.php
│   ├── YYYY_MM_DD_HHMMSS_create_master_pegawai_hirarki_table.php
│   └── YYYY_MM_DD_HHMMSS_create_pegawai_hirarki_table.php
└── seeders/
    ├── HierarchySeeder.php
    └── DatabaseSeeder.php (updated)

app/
├── Models/
│   ├── PegawaiSatker.php
│   ├── PegawaiMasterPosisi.php
│   ├── MasterPegawaiHirarki.php
│   └── PegawaiHirarki.php
├── Services/
│   └── HierarchyService.php
└── (optional) Http/Controllers/
    └── HierarchyController.php
```

---

## Urutan Pekerjaan

| Step | Task | Dependency |
|------|------|-----------|
| 1 | Buat migration `pegawai_satker` | - |
| 2 | Buat migration `pegawai_master_posisi` | - |
| 3 | Buat migration `master_pegawai_hirarki` | Step 2 (FK ke position_id) |
| 4 | Buat migration `pegawai_hirarki` | Step 1 + Step 2 (FK ke kode_satker & position_id) |
| 5 | Buat Model `PegawaiSatker` | Step 1 |
| 6 | Buat Model `PegawaiMasterPosisi` | Step 2 |
| 7 | Buat Model `MasterPegawaiHirarki` | Step 3 |
| 8 | Buat Model `PegawaiHirarki` | Step 4 |
| 9 | Buat `PegawaiMasterPosisiFactory` | Step 6 |
| 10 | Buat `HierarchySeeder` | Step 5-8 + Step 11 |
| 11 | Buat `HierarchyService` | Step 5-8 |
| 12 | Update `DatabaseSeeder` | Step 10 |
| 13 | Uji coba migrate + seed | All steps |
| 14 | Uji coba service methods | Step 11 |

---

## Catatan Penting

- **Tabel `pegawai_master_posisi`** menggunakan adjacency list (parent-child via `superior_id`). Root position memiliki `superior_id = null`.
- **Tabel `master_pegawai_hirarki`** adalah cache yang menyimpan flattened hierarchy path untuk menghindari recursive query.
- **Tabel `pegawai_hirarki`** adalah denormalized snapshot yang menggabungkan data pegawai dengan rantai atasannya untuk kebutuhan reporting/laporan cepat.
- Semua `position_id` menggunakan string, bukan integer auto-increment.
- Gunakan `database/schema` syntax yang kompatibel dengan Laravel 9/10.
- Sebaiknya diskusikan dengan tim terkait nama kolom `iliniern` apakah typo dari `inliniern` atau memang `iliniern`.
