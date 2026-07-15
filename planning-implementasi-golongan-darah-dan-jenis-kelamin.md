# Planning Implementasi Golongan Darah & Jenis Kelamin

## Ringkasan

Menambahkan 2 tabel master sederhana (`golongan_darah` dan `jenis_kelamin`) yang berelasi dengan tabel `employees`, serta menambahkan kolom foreign key di tabel `employees`.

---

## Spesifikasi Tabel

### 1. `golongan_darah`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint (auto increment, PK) | |
| nama | string(10) | Golongan darah (A, B, AB, O) |
| created_at | timestamp | |
| updated_at | timestamp | |

### 2. `jenis_kelamin`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint (auto increment, PK) | |
| nama | string(10) | Jenis kelamin (Laki-laki, Perempuan) |
| created_at | timestamp | |
| updated_at | timestamp | |

### 3. Modifikasi `employees`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| golongan_darah_id | bigint | FK ke `golongan_darah.id`, nullable |
| jenis_kelamin_id | bigint | FK ke `jenis_kelamin.id`, nullable |

---

## Struktur Relasi

```
golongan_darah (1) ──< (N) employees
jenis_kelamin (1) ──< (N) employees
```

---

## Yang Harus Diimplementasikan

| No | Item | Keterangan |
|----|------|------------|
| 1 | Migration `create_golongan_darah_table` | Buat tabel baru |
| 2 | Migration `create_jenis_kelamin_table` | Buat tabel baru |
| 3 | Migration `add_golongan_darah_and_jenis_kelamin_to_employees` | Tambah FK kolom di employees |
| 4 | Model `GolonganDarah` | Relasi `hasMany(Employee)` |
| 5 | Model `JenisKelamin` | Relasi `hasMany(Employee)` |
| 6 | Update Model `Employee` | Tambah `$fillable` + relasi `belongsTo` |
| 7 | Seeder `GolonganDarahSeeder` | Idempotent (A, B, AB, O) |
| 8 | Seeder `JenisKelaminSeeder` | Idempotent (Laki-laki, Perempuan) |
| 9 | Update `DatabaseSeeder` | Panggil seeder baru |

---

## Relasi Eloquent

### GolonganDarah
```php
public function employees(): HasMany
{
    return $this->hasMany(Employee::class, 'golongan_darah_id');
}
```

### JenisKelamin
```php
public function employees(): HasMany
{
    return $this->hasMany(Employee::class, 'jenis_kelamin_id');
}
```

### Employee (tambah)
```php
protected $fillable = [
    // ... existing ...
    'golongan_darah_id',
    'jenis_kelamin_id',
];

public function golonganDarah(): BelongsTo
{
    return $this->belongsTo(GolonganDarah::class, 'golongan_darah_id');
}

public function jenisKelamin(): BelongsTo
{
    return $this->belongsTo(JenisKelamin::class, 'jenis_kelamin_id');
}
```

---

## Migration Detail

### Migration 1: `create_golongan_darah_table`
```php
Schema::create('golongan_darah', function (Blueprint $table) {
    $table->id();
    $table->string('nama', 10);
    $table->timestamps();
});
```

### Migration 2: `create_jenis_kelamin_table`
```php
Schema::create('jenis_kelamin', function (Blueprint $table) {
    $table->id();
    $table->string('nama', 10);
    $table->timestamps();
});
```

### Migration 3: `add_golongan_darah_and_jenis_kelamin_to_employees`
```php
Schema::table('employees', function (Blueprint $table) {
    $table->foreignId('golongan_darah_id')
        ->nullable()
        ->constrained('golongan_darah')
        ->cascadeOnDelete();
    $table->foreignId('jenis_kelamin_id')
        ->nullable()
        ->constrained('jenis_kelamin')
        ->cascadeOnDelete();
});
```

---

## Seeder

### Golongan Darah
```php
['A', 'B', 'AB', 'O']
```

### Jenis Kelamin
```php
['Laki-laki', 'Perempuan']
```

Menggunakan `firstOrCreate` untuk idempotent.

---

## Urutan Pengerjaan

| Step | Task | Dependency |
|------|------|-----------|
| 1 | Migration `create_golongan_darah_table` | - |
| 2 | Migration `create_jenis_kelamin_table` | - |
| 3 | Migration `add_*_to_employees` | Step 1 & 2 |
| 4 | Model `GolonganDarah` | Step 1 |
| 5 | Model `JenisKelamin` | Step 2 |
| 6 | Update Model `Employee` | Step 3 |
| 7 | Seeder `GolonganDarahSeeder` | Step 4 |
| 8 | Seeder `JenisKelaminSeeder` | Step 5 |
| 9 | Update `DatabaseSeeder` | Step 7 & 8 |
| 10 | Uji coba migrate + seed | All |
