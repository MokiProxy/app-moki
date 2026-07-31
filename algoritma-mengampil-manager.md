# Algoritma Mengambil Manager dari Seorang Pegawai

> Analisis tabel `pegawai_hirarki` dan cara mengambil atasan (manager) dari seorang pegawai.
> Referensi implementasi: `app/Services/HierarchyService.php`

---

## 1. Struktur Tabel yang Terkait

### `pegawai_master_posisi` — adjacency list posisi
| Kolom | Keterangan |
|-------|------------|
| `position_id` | PK, id posisi (string) |
| `superior_id` | FK ke `position_id` sendiri — **atasan langsung dari posisi** (null jika root) |
| `pos_title` | Nama jabatan |

### `master_pegawai_hirarki` — cache rantai atasan per posisi
| Kolom | Keterangan |
|-------|------------|
| `position_id` | PK/FK ke `pegawai_master_posisi` |
| `superior_1` | Atasan langsung (level 1) |
| `superior_2` | Atasan dari atasan (level 2) |
| ... | ... |
| `superior_8` | Atasan tertinggi sampai level 8 |

### `pegawai_hirarki` — snapshot pegawai + rantai atasan
| Kolom | Keterangan |
|-------|------------|
| `employee_id` / `nik` / `nopeg` | Identitas pegawai |
| `position_id` | Posisi pegawai |
| `nama`, `jabatan0`, `email`, `kode_satker` | Data pegawai |
| `superior_1`, `nama_hier1`, `nik1`, `nopeg_hier1`, `email1`, `jabatan1`, `kode_satker1` | **Data manager langsung** (level 1) |
| `superior_2`, `nama_hier2`, ... `kode_satker2` | Data atasan level 2 |
| ... | ... |
| `superior_8`, `nama_hier8`, ... `kode_satker8` | Data atasan level 8 |

> Kunci utama: tabel `pegawai_hirarki` bersifat **denormalized snapshot**.
> Semua data atasan sudah di-copy ke dalam baris yang sama — **tidak perlu JOIN/recursive query**.

---

## 2. Algoritma Mengambil Manager (Atasan Langsung)

Manager = **atasan level 1** dari pegawai.

### Langkah
1. Cari baris pegawai di `pegawai_hirarki` berdasarkan identitas
   (biasanya `nik`, `nopeg`, atau `employee_id`).
2. Ambil kolom `superior_1` dari baris tersebut → berisi `position_id` manager.
3. Ambil detail manager dari kolom ber-suffix `_1` pada baris yang sama:
   `nama_hier1`, `nik1`, `nopeg_hier1`, `email1`, `jabatan1`, `kode_satker1`.
4. Jika `superior_1` bernilai **null** → pegawai tidak punya atasan (root/CEO).

### Pseudocode
```
function getManager(identifier):
    employee = SELECT * FROM pegawai_hirarki
               WHERE nik = identifier        // atau nopeg / employee_id
    if employee is null:
        return null                           // pegawai tidak ditemukan

    if employee.superior_1 is null:
        return null                           // tidak punya atasan (root)

    return {
        position_id: employee.superior_1,     // posisi manager
        nik:         employee.nik1,
        nopeg:       employee.nopeg_hier1,
        nama:        employee.nama_hier1,
        email:       employee.email1,
        jabatan:     employee.jabatan1,
        kode_satker: employee.kode_satker1
    }
```

### Kompleksitas
- **Waktu:** O(1) — hanya 1 query SELECT, tanpa JOIN, tanpa rekursi.
- **Syarat:** baris `pegawai_hirarki` pegawai sudah ter-build (materialized) sebelumnya.

---

## 3. Algoritma Mengambil Seluruh Rantai Atasan

### Cara A — dari snapshot `pegawai_hirarki`
Iterasi kolom `superior_1` s.d. `superior_8` (berhenti saat null).

```
function getSuperiorChain(identifier):
    employee = SELECT * FROM pegawai_hirarki
               WHERE nik = identifier
    chain = []
    for i = 1 to 8:
        if employee.superior_i is null:
            break
        chain.append(employee.superior_i)
    return chain
```

### Cara B — dari cache posisi `master_pegawai_hirarki`
Jika hanya punya `position_id` (tanpa data pegawai), gunakan cache posisi.

```
function getSuperiorsByPosition(positionId):
    cache = SELECT * FROM master_pegawai_hirarki
            WHERE position_id = positionId
    if cache is null:
        return []
    superiors = []
    for i = 1 to 8:
        if cache.superior_i is not null:
            superiors.append(cache.superior_i)
    return superiors
```

> Implementasi aktual ada di `HierarchyService::getSuperiors()` dan
> `HierarchyService::getEmployeeSuperiors()` (mengembalikan `Collection` posisi,
> diurutkan sesuai urutan rantai via `FIELD(position_id, ...)`).

---

## 4. Algoritma Pembentukan Cache (Agar Cara di Atas Bisa Dipakai)

Snapshot di `pegawai_hirarki` & `master_pegawai_hirarki` harus dibangun dulu
dari adjacency list `pegawai_master_posisi`.

### 4a. Build cache posisi — `buildPositionHierarchy()`
```
function buildPositionHierarchy(position):
    superiors = []
    current = position
    for i = 1 to 8:
        if current.superior_id is null:
            break                        // sudah mencapai root
        superiors.superior_i = current.superior_id
        current = SELECT * FROM pegawai_master_posisi
                  WHERE position_id = current.superior_id
        if current is null:
            break                        // data posisi tidak lengkap
    UPSERT master_pegawai_hirarki (position_id = position.position_id, superiors)
```

### 4b. Build snapshot pegawai — `buildEmployeeHierarchy()`
```
function buildEmployeeHierarchy(employee):
    cache = SELECT * FROM master_pegawai_hirarki
            WHERE position_id = employee.position_id
    if cache is null:
        return                           // cache posisi belum ada

    data = {}
    for i = 1 to 8:
        superiorId = cache.superior_i
        if superiorId is null:
            continue
        data.superior_i = superiorId

        superior = SELECT * FROM pegawai_hirarki
                   WHERE position_id = superiorId   // ambil snapshot atasan
        if superior is not null:
            data.nik_i         = superior.nik
            data.nopeg_hier_i  = superior.nopeg
            data.nama_hier_i   = superior.nama
            data.email_i       = superior.email
            data.jabatan_i     = superior.jabatan0
            data.kode_satker_i = superior.kode_satker
    UPDATE pegawai_hirarki SET data WHERE id = employee.id
```

---

## 5. Ringkasan / Cara Memilih

| Kebutuhan | Tabel yang Dipakai | Kolom |
|-----------|--------------------|-------|
| Manager langsung seorang pegawai | `pegawai_hirarki` | `superior_1`, `nama_hier1`, `nik1`, `email1`, `jabatan1` |
| Rantai atasan seorang pegawai | `pegawai_hirarki` | `superior_1..8` (+ `nama_hier_n` dst.) |
| Rantai atasan sebuah posisi (tanpa data pegawai) | `master_pegawai_hirarki` | `superior_1..8` |
| Atasan langsung sebuah posisi (data mentah) | `pegawai_master_posisi` | `superior_id` |

**Alur umum:**
1. `pegawai_master_posisi` = sumber kebenaran (adjacency list).
2. `master_pegawai_hirarki` = cache rantai posisi (dibuat dari adjacency list).
3. `pegawai_hirarki` = snapshot pegawai + rantai atasan lengkap dengan data orangnya.

Untuk mengambil manager, **selalu baca `superior_1` + kolom `_1` dari tabel
`pegawai_hirarki`** — paling cepat (O(1), tanpa JOIN/rekursi).
