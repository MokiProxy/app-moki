# Planning: Implementasi Relasi Get Superior/User Manager

## 1. Analisis Struktur Data

### Table `users`
| Field | Type | Keterangan |
|-------|------|------------|
| `id` | bigint | PK auto-increment |
| `employee_id` | string | FK → employees.employee_id |
| `nopeg` | string | Unique, FK ← pegawai_hirarki.nopeg |
| `name` | string | |
| `email` | string | Unique |
| `role_id` | integer | 1:Superadmin, 2:Admin, 3:Atasan |

### Table `employees`
| Field | Type | Keterangan |
|-------|------|------------|
| `employee_id` | string | PK |

### Table `pegawai_hirarki`
| Field | Type | Keterangan |
|-------|------|------------|
| `employee_id` | string | FK → employees.employee_id |
| `nopeg` | string | FK → users.nopeg |
| `position_id` | string | FK → pegawai_master_posisi.position_id |
| `employee_id_hier1` | string | employee_id superior level 1 |
| `employee_id_hier2` | string | employee_id superior level 2 |
| `...` | string | ...sampai level 8 |
| `nama_hier1..8` | string | Nama superior di tiap level |
| `email1..8` | string | Email superior di tiap level |

### Relasi yang Sudah Ada
```
User → Employee: belongsTo('employee_id' → 'employee_id')
Employee → User: hasOne('employee_id' → 'employee_id')
PegawaiHirarki → PegawaiMasterPosisi: belongsTo('position_id' → 'position_id')
PegawaiHirarki → PegawaiSatker: belongsTo('kode_satker' → 'kode_satker')
```

## 2. Alur Mendapatkan Superior 1

```
User (EMP006)
  │ employee_id = "EMP006"
  ▼
PegawaiHirarki (where employee_id = "EMP006")
  │ employee_id_hier1 = "EMP005"
  ▼
User (EMP005) ← superior 1
```

**Path:** `User → PegawaiHirarki (via employee_id) → baca employee_id_hier1 → User (via employee_id)`

## 3. Pendekatan yang Direkomendasikan

### Opsi A: Method Langsung di User Model (Recommended)

Tambahkan method `superior1()` di `app/Models/User.php`:

```php
use App\Models\PegawaiHirarki;

/**
 * Get superior level 1 dari user ini.
 */
public function superior1(): ?User
{
    $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

    if (!$hirarki || !$hirarki->employee_id_hier1) {
        return null;
    }

    return static::where('employee_id', $hirarki->employee_id_hier1)->first();
}
```

**Kelebihan:**
- Simple, langsung dipahami
- Single query untuk get hirarki, single query untuk get user superior
- Dapat langsung dipanggil: `$user->superior1`

**Kekurangan:**
- Tidak Eloquent-native relationship (tidak bisa eager load)

---

### Opsi B: Method Dinamis untuk Semua Level Superior

```php
/**
 * Get superior pada level tertentu (1-8).
 */
public function superior(int $level): ?User
{
    if ($level < 1 || $level > 8) {
        return null;
    }

    $hirarki = PegawaiHirarki::where('employee_id', $this->employee_id)->first();

    if (!$hirarki) {
        return null;
    }

    $employeeId = $hirarki->{"employee_id_hier{$level}"};

    if (!$employeeId) {
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

    if (!$hirarki) {
        return collect();
    }

    $superiors = collect();
    for ($i = 1; $i <= $maxLevel; $i++) {
        $employeeId = $hirarki->{"employee_id_hier{$i}"};
        if (!$employeeId) {
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

**Step 1:** Tambahkan relationship `pegawaiHirarki` di User model:
```php
public function pegawaiHirarki(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(
        PegawaiHirarki::class,
        'employee_id',
        'employee_id'
    );
}
```

**Step 2:** Tambahkan relationship `superiorUser` di PegawaiHirarki model:
```php
public function superiorUser(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(
        User::class,
        'employee_id',
        'employee_id_hier1'
    );
}
```

**Penggunaan:**
```php
// Lazy loading
$superior = $user->pegawaiHirarki->superiorUser;

// Eager loading (perlu custom approach)
$user->load('pegawaiHirarki.superiorUser');
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
3. Tambahkan relationship `pegawaiHirarki()` untuk akses data hirarki mentah jika diperlukan

### File yang Perlu Diubah

| File | Perubahan |
|------|-----------|
| `app/Models/User.php` | Tambah method `superior1()`, `superior()`, `pegawaiHirarki()` |
| `app/Models/PegawaiHirarki.php` | (Opsional) Tambah `superiorUser()` relationship |

## 5. Contoh Penggunaan

```php
// Get superior 1 (manager langsung)
$manager = $user->superior1();

// Get superior pada level tertentu
$vp = $user->superior(2);
$director = $user->superior(3);

// Get rantai lengkap sampai level 3
$chain = $user->superiorChain(3);
// Hasil: Collection [1 => User(manager), 2 => User(vp), 3 => User(director)]

// Get data hirarki mentah
$hirarki = $user->pegawaiHirarki;
```

## 6. Testing Strategy

- Unit test `superior1()` dengan user yang memiliki hirarki
- Unit test `superior1()` dengan user yang TIDAK memiliki hirarki (return null)
- Unit test `superior(level)` untuk berbagai level
- Unit test `superiorChain()` dengan max level berbeda
- Pastikan tidak ada N+1 problem dengan eager loading test

## 7. Status

- [x] Diimplementasi di `app/Models/User.php`
