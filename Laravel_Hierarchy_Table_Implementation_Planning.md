# Laravel Implementation Planning Prompt

## Tujuan
Implementasikan struktur database Laravel berdasarkan diagram organisasi.

## Analisis

### 1. pegawai_satker
Master satuan kerja.
- PK: `kode_satker` (string)
- nama_satker

### 2. pegawai_master_posisi
Master posisi organisasi (adjacency list).
- PK: `position_id` (string)
- superior_id (nullable, FK ke position_id)
- pos_title
- last_mode_date
- last_mode_time

Relasi:
- belongsTo(parent)
- hasMany(children)

### 3. master_pegawai_hirarki
Cache jalur hirarki berdasarkan posisi.
- PK/FK: position_id
- superior_1 ... superior_8 (nullable FK ke position_id)

Digunakan agar tidak perlu recursive query.

### 4. pegawai_hirarki
Snapshot pegawai beserta rantai atasannya.

Data pegawai:
- position_id
- nik
- nopeg
- nama
- email
- jabatan0
- kode_satker

Untuk setiap level 1-8:
- superior_n
- nikn
- nopeg_hiern
- nama_hiern
- iliniern
- emailn
- jabatann
- kode_satkern

> Tabel ini merupakan materialized hierarchy / cache.

---

# Yang harus diimplementasikan

## Phase 1
- Migration
- Eloquent Model
- Factory
- Seeder
- Foreign Key
- Index
- Soft Delete hanya jika diperlukan (default: tidak)
- Timestamp

## Migration
Buat migration untuk:
1. pegawai_satker
2. pegawai_master_posisi
3. master_pegawai_hirarki
4. pegawai_hirarki

Gunakan nama tabel snake_case.

## Model

### PegawaiSatker
fillable sesuai kolom.
Relation:
- hasMany(PegawaiHirarki)

### PegawaiMasterPosisi
Relation:
- parent()
- children()
- hierarchy()

### MasterPegawaiHirarki
belongsTo(PegawaiMasterPosisi)

### PegawaiHirarki
belongsTo(PegawaiMasterPosisi)
belongsTo(PegawaiSatker)

## Constraint

- superior_id -> pegawai_master_posisi.position_id
- superior_1 ... superior_8 -> pegawai_master_posisi.position_id
- kode_satker -> pegawai_satker.kode_satker

## Seeder

Seeder contoh:

CEO
└── GM IT
    └── Manager Development
        └── Supervisor Backend
            └── Programmer Backend

Buat pula contoh pegawai:
- Dedi
- Agus
- Toni
- Andi
- Budi

Generate isi master_pegawai_hirarki dan pegawai_hirarki sesuai struktur tersebut.

## Service

Buat service `HierarchyService`.

Method:
- buildPositionHierarchy()
- buildEmployeeHierarchy()
- getSuperiors(positionId)
- getEmployeeSuperiors(nik)

## Best Practice

- Gunakan typed properties.
- Gunakan return type.
- Jangan hardcode SQL.
- Gunakan Eloquent Relationship.
- Tambahkan PHPDoc.
- Tambahkan komentar pada bagian yang kompleks.
- Pastikan migration dapat rollback.
- Pastikan seeder idempotent.
- Pisahkan logika business ke Service.

## Output yang diharapkan

AI harus menghasilkan:
- seluruh migration
- seluruh model
- seluruh relation
- factory
- seeder
- hierarchy service
- contoh penggunaan
- struktur folder
- mengikuti Laravel Best Practice.
