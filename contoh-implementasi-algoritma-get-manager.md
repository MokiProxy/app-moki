# Contoh Implementasi Algoritma Get Manager

> Contoh penggunaan nyata di Laravel berdasarkan kode yang ada di project:
> - `app/Services/HierarchyService.php`
> - `app/Http/Controllers/PegawaiHirarkiController.php`
> - `database/seeders/HierarchySeeder.php`

---

## Data Contoh (dari `HierarchySeeder`)

```
POS-CEO      Abimana CEO      (CEO)
└── POS-GMIT      Bayu GMIT   (GM IT)                level 1
    └── POS-MGRDEV    Irsyad MGRDEV (Manager Dev)    level 2
        └── POS-SPVBE    Humam SPVBE (Supervisor)    level 3
            └── POS-PRGBE    Wahid PGRBE (Programmer) level 4
```

Baris `pegawai_hirarki` untuk **Wahid PGRBE** setelah di-build:

| Kolom | Nilai |
|-------|-------|
| `position_id` | `POS-PRGBE` |
| `nama` | `Wahid PGRBE` |
| `jabatan0` | `Programmer Backend` |
| `superior_1` | `POS-SPVBE` |
| `nama_hier1` | `Humam SPVBE` |
| `jabatan1` | `Supervisor Backend` |
| `email1` | `humam.spvbe@tpm-facility.com` |
| `superior_2` | `POS-MGRDEV` |
| `nama_hier2` | `Irsyad MGRDEV` |
| `superior_3` | `POS-GMIT` |
| `nama_hier3` | `Bayu GMIT` |
| `superior_4` | `POS-CEO` |
| `nama_hier4` | `Abimana CEO` |

---

## Contoh 1 — Mengambil Manager Langsung (Eloquent, tanpa JOIN)

Manager = atasan level 1 = `superior_1` + kolom ber-suffix `_1`.

```php
use App\Models\PegawaiHirarki;

$employee = PegawaiHirarki::where('nik', '12182823725')->first();

if ($employee === null) {
    // pegawai tidak ditemukan
}

$manager = [
    'position_id' => $employee->superior_1,
    'nama'        => $employee->nama_hier1,
    'nik'         => $employee->nik1,
    'nopeg'       => $employee->nopeg_hier1,
    'email'       => $employee->email1,
    'jabatan'     => $employee->jabatan1,
    'kode_satker' => $employee->kode_satker1,
];

// Hasil untuk Wahid PGRBE:
// position_id => 'POS-SPVBE'
// nama        => 'Humam SPVBE'
// jabatan     => 'Supervisor Backend'
```

Bisa juga dicari lewat identitas lain:

```php
// by employee_id
$employee = PegawaiHirarki::where('employee_id', 'EMP0005')->first();

// by nopeg
$employee = PegawaiHirarki::where('nopeg', '20260005')->first();

// by posisi
$employee = PegawaiHirarki::where('position_id', 'POS-PRGBE')->first();
```

---

## Contoh 2 — Reusable Method (Helper / Trait)

Agar bisa dipakai di mana saja, buat method helper:

```php
// app/Support/HierarchyHelper.php (opsional)
namespace App\Support;

use App\Models\PegawaiHirarki;

class HierarchyHelper
{
    /**
     * Ambil manager langsung seorang pegawai.
     *
     * @param string $identifier nilai nik / nopeg / employee_id / position_id
     * @return array|null data manager, null jika tidak ditemukan / tidak punya atasan
     */
    public static function getManager(string $identifier): ?array
    {
        $employee = PegawaiHirarki::where('nik', $identifier)
            ->orWhere('nopeg', $identifier)
            ->orWhere('employee_id', $identifier)
            ->orWhere('position_id', $identifier)
            ->first();

        if ($employee === null || $employee->superior_1 === null) {
            return null;
        }

        return [
            'position_id' => $employee->superior_1,
            'nama'        => $employee->nama_hier1,
            'nik'         => $employee->nik1,
            'nopeg'       => $employee->nopeg_hier1,
            'email'       => $employee->email1,
            'jabatan'     => $employee->jabatan1,
            'kode_satker' => $employee->kode_satker1,
        ];
    }

    /**
     * Ambil seluruh rantai atasan (level 1 s.d. 8).
     *
     * @return array<int, array>
     */
    public static function getManagerChain(string $identifier): array
    {
        $employee = PegawaiHirarki::where('nik', $identifier)
            ->orWhere('nopeg', $identifier)
            ->orWhere('employee_id', $identifier)
            ->orWhere('position_id', $identifier)
            ->first();

        if ($employee === null) {
            return [];
        }

        $chain = [];
        for ($i = 1; $i <= 8; $i++) {
            $posId = $employee->{"superior_{$i}"};
            if ($posId === null) {
                break;
            }
            $chain[] = [
                'level'       => $i,
                'position_id' => $posId,
                'nama'        => $employee->{"nama_hier{$i}"},
                'jabatan'     => $employee->{"jabatan{$i}"},
                'email'       => $employee->{"email{$i}"},
            ];
        }

        return $chain;
    }
}
```

Pemakaian:

```php
use App\Support\HierarchyHelper;

$manager = HierarchyHelper::getManager('12182823725'); // Humam SPVBE
$chain   = HierarchyHelper::getManagerChain('12182823725'); // 4 atasan
```

---

## Contoh 3 — Via `HierarchyService` (rantai posisi saja)

Service yang sudah ada hanya mengembalikan rantai **posisi** (tanpa detail nama),
cocok untuk kebutuhan struktur / org chart.

```php
use App\Services\HierarchyService;

$service = app(HierarchyService::class);

// Rantai atasan dari posisi
$superiors = $service->getSuperiors('POS-PRGBE');
// Collection<PegawaiMasterPosisi> urut: POS-SPVBE, POS-MGRDEV, POS-GMIT, POS-CEO

// Rantai atasan dari employee_id
$superiors = $service->getEmployeeSuperiors('EMP0005');

foreach ($superiors as $pos) {
    echo $pos->pos_title . PHP_EOL;
}
// Supervisor Backend
// Manager Development
// GM IT
// CEO
```

---

## Contoh 4 — Endpoint API (Controller + Route)

Tambahkan method di `PegawaiHirarkiController`:

```php
use Illuminate\Http\Request;

public function manager(Request $request, $id)
{
    $hirarki = PegawaiHirarki::find($id);

    if (is_null($hirarki)) {
        return response()->json([
            'success' => false,
            'message' => 'Pegawai Hirarki not found',
        ], 404);
    }

    if ($hirarki->superior_1 === null) {
        return response()->json([
            'success' => true,
            'message' => 'Pegawai tidak memiliki atasan',
            'data'    => null,
        ]);
    }

    return response()->json([
        'success' => true,
        'data'    => [
            'position_id' => $hirarki->superior_1,
            'nama'        => $hirarki->nama_hier1,
            'nik'         => $hirarki->nik1,
            'nopeg'       => $hirarki->nopeg_hier1,
            'email'       => $hirarki->email1,
            'jabatan'     => $hirarki->jabatan1,
        ],
    ]);
}
```

Route (contoh, di dalam grup yang sudah ada):

```php
Route::get('/pegawai-hirarki/{id}/manager', [PegawaiHirarkiController::class, 'manager'])
    ->name('pegawai-hirarki.manager');
```

Request:

```http
GET /pegawai-hirarki/5/manager
```

Response:

```json
{
  "success": true,
  "data": {
    "position_id": "POS-SPVBE",
    "nama": "Humam SPVBE",
    "nik": "12182823724",
    "nopeg": "20260004",
    "email": "humam.spvbe@tpm-facility.com",
    "jabatan": "Supervisor Backend"
  }
}
```

---

## Contoh 5 — Menggunakan di Blade View

```blade
@php
    $manager = App\Support\HierarchyHelper::getManager($pegawai->nik);
@endphp

@if ($manager)
    <p>
        Manager: {{ $manager['nama'] }}
        ({{ $manager['jabatan'] }})
    </p>
    <a href="mailto:{{ $manager['email'] }}">{{ $manager['email'] }}</a>
@else
    <p>Tidak memiliki atasan (top level)</p>
@endif
```

---

## Contoh 6 — Menampilkan Rantai Atasan (seperti endpoint `hierarchy` yang sudah ada)

Pola ini sudah dipakai di `PegawaiHirarkiController::hierarchy()` — mengulang
`superior_1..8` lalu melengkapi data posisi:

```php
public function chain($id)
{
    $hirarki = PegawaiHirarki::find($id);

    if (is_null($hirarki)) {
        return response()->json([
            'success' => false,
            'message' => 'Pegawai Hirarki not found',
        ], 404);
    }

    $chain = [];
    for ($i = 1; $i <= 8; $i++) {
        $posId = $hirarki->{"superior_{$i}"};
        if ($posId === null) {
            break;
        }

        $chain[] = [
            'level'       => $i,
            'position_id' => $posId,
            'nama'        => $hirarki->{"nama_hier{$i}"},
            'jabatan'     => $hirarki->{"jabatan{$i}"},
            'email'       => $hirarki->{"email{$i}"},
            'kode_satker' => $hirarki->{"kode_satker{$i}"},
        ];
    }

    return response()->json([
        'success' => true,
        'data'    => $chain,
    ]);
}
```

---

## Ringkasan Pola yang Dipakai

| Kebutuhan | Cara | Sumber |
|-----------|------|--------|
| Manager langsung | Baca `superior_1` + kolom `_1` pada baris yang sama | `pegawai_hirarki` |
| Rantai atasan pegawai | Loop `superior_1..8` sampai null | `pegawai_hirarki` |
| Rantai atasan posisi | Loop `superior_1..8` sampai null | `master_pegawai_hirarki` (via `HierarchyService`) |
| Data mentah atasan | JOIN ke `pegawai_master_posisi` via `superior_id` | adjacency list |

> **Catatan:** semua contoh di atas mengasumsikan snapshot sudah di-build
> (via `buildPositionHierarchy()` + `buildEmployeeHierarchy()`), sama seperti
> yang dilakukan `HierarchySeeder`. Jika pegawai baru ditambahkan, panggil
> `buildEmployeeHierarchy($employee)` seperti di `store()` dan `update()` pada
> `PegawaiHirarkiController`.
