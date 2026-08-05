# Planning: Implementasi Relasi Get Employee Manager (Superior 1)

## 1. Analisis Struktur Data

### Table `employees`
| Field | Type | Keterangan |
|-------|------|------------|
| `employee_id` | string | PK |
| `name` | string | |
| `jabatan` | string | |
| `division_id` | string | FK → divisions |
| `regional_id` | string | FK → regionals |

### Table `pegawai_hirarki`
| Field | Type | Keterangan |
|-------|------|------------|
| `employee_id` | string | FK → employees.employee_id |
| `position_id` | string | FK → pegawai_master_posisi |
| `employee_id_hier1` | string | employee_id superior level 1 |
| `employee_id_hier2` | string | employee_id superior level 2 |
| `...` | string | ...sampai level 8 |
| `nama_hier1..8` | string | Nama superior di tiap level |
| `jabatan1..8` | string | Jabatan superior di tiap level |

### Relasi yang Sudah Ada
```
Employee → User:         hasOne('employee_id' → 'employee_id')
Employee → Division:     belongsTo('division_id')
Employee → Regional:     belongsTo('regional_id')
PegawaiHirarki → PegawaiMasterPosisi: belongsTo('position_id')
PegawaiHirarki → PegawaiSatker:       belongsTo('kode_satker')
```

### Belum Ada
```
Employee → PegawaiHirarki: belum ada
Employee → Employee (superior): belum ada
```

## 2. Alur Mendapatkan Superior 1

```
Employee (EMP006)
  │ employee_id = "EMP006"
  ▼
PegawaiHirarki (where employee_id = "EMP006")
  │ employee_id_hier1 = "EMP005"
  ▼
Employee (EMP005) ← superior 1
```

**Path:** `Employee → PegawaiHirarki (via employee_id) → baca employee_id_hier1 → Employee (via employee_id)`

## 3. Pendekatan yang Direkomendasikan

### Opsi A: Method Langsung di Employee Model (Recommended)

Tambahkan method `superior1()` di `app/Models/Employee.php`:

```php
use App\Models\PegawaiHirarki;

/**
 * Get superior level 1 dari employee ini.
 */
public function superior1(): ?self
{
    $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

    if (! $hirarki || ! $hirarki->employee_id_hier1) {
        return null;
    }

    return static::where('employee_id', $hirarki->employee_id_hier1)->first();
}
```

**Kelebihan:**
- Simple, langsung dipahami
- Single query untuk get hirarki, single query untuk get employee superior
- Dapat langsung dipanggil: `$employee->superior1`

**Kekurangan:**
- Tidak Eloquent-native relationship (tidak bisa eager load)

---

### Opsi B: Method Dinamis untuk Semua Level Superior

```php
/**
 * Get superior pada level tertentu (1-8).
 */
public function superior(int $level): ?self
{
    if ($level < 1 || $level > 8) {
        return null;
    }

    $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

    if (! $hirarki) {
        return null;
    }

    $employeeId = $hirarki->{"employee_id_hier{$level}"};

    if (! $employeeId) {
        return null;
    }

    return static::where('employee_id', $employeeId)->first();
}

/**
 * Get rantai superior dari level 1 sampai level tertentu.
 */
public function superiorChain(int $maxLevel = 8): \Illuminate\Support\Collection
{
    $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

    if (! $hirarki) {
        return collect();
    }

    $superiors = collect();
    for ($i = 1; $i <= $maxLevel; $i++) {
        $employeeId = $hirarki->{"employee_id_hier{$i}"};
        if (! $employeeId) {
            break;
        }
        $superior = static::where('employee_id', $employeeId)->first();
        if ($superior) {
            $superiors->put($i, $superior);
        }
    }

    return $superiors;
}
```

---

### Opsi C: Full Eloquent Relationship Chain

**Step 1:** Tambahkan relationship `hirarki` di Employee model:
```php
public function hirarki(): HasOne
{
    return $this->hasOne(
        PegawaiHirarki::class,
        'employee_id',
        'employee_id'
    );
}
```

**Step 2:** Tambahkan relationship `superiorEmployee` di PegawaiHirarki model:
```php
public function superiorEmployee(): HasOne
{
    return $this->hasOne(
        Employee::class,
        'employee_id',
        'employee_id_hier1'
    );
}
```

**Penggunaan:**
```php
// Lazy loading
$superior = $employee->hirarki->superiorEmployee;

// Eager loading
$employee->load('hirarki.superiorEmployee');
```

**Kelebihan:**
- Eloquent-native
- Support eager loading

**Kekurangan:**
- Butuh 2 hop relationship
- Lazy loading bisa N+1 problem

---

## 4. Rekomendasi

Gunakan **gabungan Opsi A + B**:

1. Tambahkan method `superior1()` untuk use case spesifik (get manager langsung)
2. Tambahkan method `superior(int $level)` untuk fleksibilitas level
3. Tambahkan method `superiorChain(int $maxLevel)` untuk rantai lengkap
4. Tambahkan relationship `hirarki()` untuk akses data hirarki mentah

### File yang Perlu Diubah

| File | Perubahan |
|------|-----------|
| `app/Models/Employee.php` | Tambah method `superior1()`, `superior()`, `superiorChain()`, `hirarki()` |
| `app/Models/PegawaiHirarki.php` | (Opsional) Tambah `superiorEmployee()` relationship |

## 5. Contoh Penggunaan

```php
// Get superior 1 (manager langsung)
$manager = $employee->superior1();

// Get superior pada level tertentu
$vp = $employee->superior(2);
$director = $employee->superior(3);

// Get rantai lengkap sampai level 3
$chain = $employee->superiorChain(3);
// Hasil: Collection [1 => Employee(manager), 2 => Employee(vp), 3 => Employee(director)]

// Get data hirarki mentah
$hirarki = $employee->hirarki;
```

## 6. Testing Strategy

- Unit test `superior1()` dengan employee yang memiliki hirarki
- Unit test `superior1()` dengan employee yang TIDAK memiliki hirarki (return null)
- Unit test `superior(level)` untuk berbagai level
- Unit test `superiorChain()` dengan max level berbeda
- Pastikan tidak ada N+1 problem dengan eager loading test

## 7. Status

- [x] Diimplementasi di `app/Models/Employee.php`
