# Planning Implementasi Approval - Modul Form IT (Software Installation)

## 1. Analisis Status Quo

### Saat Ini:
- **Tidak ada persistensi database**: Form hanya menerima input dan langsung generate PDF via DomPDF. Data tidak disimpan ke database.
- **Tidak ada alur approval**: PDF di-stream langsung ke browser.
- **Relasi Hierarki**: Sudah tersedia di model `Employee`:
  - `superior1()` → Mengambil atasan level 1
  - `findSuperiorByJabatan('GM IT')` → Mengambil manager IT

### Yang Dibutuhkan:
- Simpan data pengajuan ke database.
- Buat alur approval 2 tahap (Superior1 → Manager IT).
- Tampilkan status approval di halaman pemohon.
- Update PDF untuk menampilkan tanda tangan/disetujui setelah approval selesai.

---

## 2. Desain Database

### 2.1 Tabel: `software_installations`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK, auto) | ID unik |
| `pemohon_id` | string | Employee ID pemohon (FK ke employees.employee_id) |
| `superior1_id` | string | Nullable, Employee ID superior1 |
| `manager_it_id` | string | Nullable, Employee ID manager IT |
| `softwares` | json | Array of selected software slugs |
| `keterangan` | text | Catatan tambahan |
| `status` | enum | `pending`, `process`, `approved`, `rejected` |
| `rejected_by` | string | Nullable, employee_id yang menolak |
| `rejection_reason` | text | Nullable, alasan penolakan |
| `approved_at` | timestamp | Nullable, waktu approval terakhir |
| `created_at` | timestamp | Waktu pembuatan |
| `updated_at` | timestamp | Waktu update terakhir |

### 2.2 Tabel: `approvals`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK, auto) | ID unik |
| `software_installation_id` | bigint | FK ke software_installations.id |
| `approver_id` | string | Employee ID approver |
| `level` | integer | Urutan approval (1=Superior1, 2=Manager IT) |
| `status` | enum | `pending`, `approved`, `rejected` |
| `approved_at` | timestamp | Nullable, waktu approval |
| `notes` | text | Nullable, catatan approval |

---

## 3. Model

### 3.1 Model `SoftwareInstallation`

```php
// app/Models/SoftwareInstallation.php

class SoftwareInstallation extends Model
{
    protected $fillable = [
        'pemohon_id',
        'superior1_id',
        'manager_it_id',
        'softwares',
        'keterangan',
        'status',
        'rejected_by',
        'rejection_reason',
        'approved_at',
    ];

    protected $casts = [
        'softwares' => 'array',
        'approved_at' => 'datetime',
    ];

    // Relasi ke pemohon
    public function pemohon()
    {
        return $this->belongsTo(Employee::class, 'pemohon_id', 'employee_id');
    }

    // Relasi ke superior1
    public function superior1()
    {
        return $this->belongsTo(Employee::class, 'superior1_id', 'employee_id');
    }

    // Relasi ke manager IT
    public function managerIt()
    {
        return $this->belongsTo(Employee::class, 'manager_it_id', 'employee_id');
    }

    // Relasi ke approval records
    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    // Cek apakah sudah di-approve oleh level tertentu
    public function isApprovedByLevel(int $level): bool
    {
        return $this->approvals()
            ->where('level', $level)
            ->where('status', 'approved')
            ->exists();
    }

    // Cek apakah approval bisa dilakukan oleh level tertentu
    public function canApproveLevel(int $level): bool
    {
        if ($level === 1) {
            return $this->status === 'pending';
        }
        if ($level === 2) {
            return $this->status === 'process' && $this->isApprovedByLevel(1);
        }
        return false;
    }
}
```

### 3.2 Model `Approval`

```php
// app/Models/Approval.php

class Approval extends Model
{
    protected $fillable = [
        'software_installation_id',
        'approver_id',
        'level',
        'status',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function softwareInstallation()
    {
        return $this->belongsTo(SoftwareInstallation::class);
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id', 'employee_id');
    }
}
```

---

## 4. Controller

### 4.1 `FormController` (Modified)

```php
// Method softwareInstallationCreate - SIMPAN ke database
public function softwareInstallationCreate(Request $request)
{
    $validated = $request->validate([
        'softwares' => 'required|array',
        'softwares.*' => 'string',
        'keterangan' => 'nullable|string',
    ]);

    $pemohon = Employee::with(['division', 'regional', 'hirarki'])
        ->where('employee_id', auth()->user()->employee_id)
        ->first();

    // Cari superior1
    $superior1 = $pemohon->superior1();

    // Cari manager IT
    $managerIT = Employee::whereHas('hirarki', function ($query) {
        $query->where('jabatan0', 'GM IT');
    })->first();

    // Simpan pengajuan
    $softwareInstallation = SoftwareInstallation::create([
        'pemohon_id' => $pemohon->employee_id,
        'superior1_id' => $superior1?->employee_id,
        'manager_it_id' => $managerIT?->employee_id,
        'softwares' => $validated['softwares'],
        'keterangan' => $validated['keterangan'] ?? null,
        'status' => 'pending',
    ]);

    // Buat record approval untuk superior1
    if ($superior1) {
        Approval::create([
            'software_installation_id' => $softwareInstallation->id,
            'approver_id' => $superior1->employee_id,
            'level' => 1,
            'status' => 'pending',
        ]);
    }

    // Buat record approval untuk manager IT
    if ($managerIT) {
        Approval::create([
            'software_installation_id' => $softwareInstallation->id,
            'approver_id' => $managerIT->employee_id,
            'level' => 2,
            'status' => 'pending',
        ]);
    }

    return redirect()
        ->route('form-it.forms.software-installation.show', $softwareInstallation->id)
        ->with('success', 'Pengajuan berhasil dibuat! Menunggu approval dari Superior dan Manager IT.');
}
```

### 4.2 `ApprovalController` (New)

```php
// app/Http/Controllers/FormIT/ApprovalController.php

class ApprovalController extends Controller
{
    // Dashboard approval untuk user yang login
    public function index()
    {
        $user = auth()->user();
        $employeeId = $user->employee_id;

        // Ambil pengajuan yang perlu di-approve oleh user ini
        $pendingApprovals = SoftwareInstallation::with(['pemohon', 'pemohon.division'])
            ->whereHas('approvals', function ($query) use ($employeeId) {
                $query->where('approver_id', $employeeId)
                      ->where('status', 'pending');
            })
            ->where('status', '!=', 'rejected')
            ->get();

        // Riwayat approval yang sudah dilakukan
        $historyApprovals = SoftwareInstallation::with(['pemohon', 'pemohon.division'])
            ->whereHas('approvals', function ($query) use ($employeeId) {
                $query->where('approver_id', $employeeId)
                      ->where('status', '!=', 'pending');
            })
            ->get();

        return view('form-it.approval.index', compact('pendingApprovals', 'historyApprovals'));
    }

    // Detail pengajuan untuk approval
    public function show($id)
    {
        $softwareInstallation = SoftwareInstallation::with([
            'pemohon', 'pemohon.division', 'pemohon.regional',
            'superior1', 'managerIt', 'approvals', 'approvals.approver'
        ])->findOrFail($id);

        $user = auth()->user();
        $employeeId = $user->employee_id;

        // Cek apakah user ini adalah approver
        $approval = $softwareInstallation->approvals()
            ->where('approver_id', $employeeId)
            ->first();

        return view('form-it.approval.show', compact('softwareInstallation', 'approval'));
    }

    // Proses approval/rejection
    public function process(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $employeeId = $user->employee_id;

        $softwareInstallation = SoftwareInstallation::findOrFail($id);

        // Cari approval record untuk user ini
        $approval = $softwareInstallation->approvals()
            ->where('approver_id', $employeeId)
            ->where('status', 'pending')
            ->first();

        if (!$approval) {
            return back()->with('error', 'Anda tidak memiliki akses untuk melakukan approval ini.');
        }

        // Cek apakah approval bisa dilakukan (sequential)
        if ($approval->level === 2 && !$softwareInstallation->isApprovedByLevel(1)) {
            return back()->with('error', 'Superior1 harus approve terlebih dahulu.');
        }

        DB::beginTransaction();

        try {
            if ($validated['action'] === 'approve') {
                $approval->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Update status software installation
                if ($approval->level === 1) {
                    $softwareInstallation->update(['status' => 'process']);
                } elseif ($approval->level === 2) {
                    $softwareInstallation->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                    ]);
                }
            } else {
                // Reject
                $approval->update([
                    'status' => 'rejected',
                    'notes' => $validated['notes'] ?? null,
                ]);

                $softwareInstallation->update([
                    'status' => 'rejected',
                    'rejected_by' => $employeeId,
                    'rejection_reason' => $validated['notes'] ?? null,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Approval berhasil diproses!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses approval: ' . $e->getMessage());
        }
    }
}
```

---

## 5. Routes

```php
// routes/routers/form-it.php

// Approval routes
Route::get('/approval', [FormIT\ApprovalController::class, 'index'])
    ->name('approval.index');
Route::get('/approval/{id}', [FormIT\ApprovalController::class, 'show'])
    ->name('approval.show');
Route::post('/approval/{id}/process', [FormIT\ApprovalController::class, 'process'])
    ->name('approval.process');

// Lihat PDF setelah approval
Route::get('/forms/software-installation/{id}/pdf', [FormIT\FormController::class, 'showPdf'])
    ->name('forms.software-installation.pdf');
```

---

## 6. Views (Blade)

### 6.1 Dashboard Approval (`form-it/approval/index.blade.php`)

```
Struktur:
- Extends layouts.FormIT
- Tab: "Menunggu Approval" dan "Riwayat Approval"
- Tabel dengan kolom:
  - No
  - Tanggal Pengajuan
  - Nama Pemohon
  - Departemen
  - Software yang Diminta
  - Status
  - Aksi (Tombol Approve/Reject)
```

### 6.2 Detail Approval (`form-it/approval/show.blade.php`)

```
Struktur:
- Detail pengajuan (readonly)
- Status approval (Siapa yang sudah approve, siapa yang pending)
- Tombol Approve (hanya muncul jika user adalah approver yang valid)
- Tombol Reject dengan textarea untuk alasan
```

### 6.3 Status Badge Component

```blade
// components/status-badge.blade.php
@switch($status)
    @case('pending')
        <span class="badge bg-warning text-dark">Menunggu</span>
        @break
    @case('process')
        <span class="badge bg-info">Proses Approval</span>
        @break
    @case('approved')
        <span class="badge bg-success">Disetujui</span>
        @break
    @case('rejected')
        <span class="badge bg-danger">Ditolak</span>
        @break
@endswitch
```

---

## 7. PDF Template Update (`laporan.blade.php`)

### Update Kolom Tanda Tangan:

```blade
<table class="signature">
    <tr>
        <td class="center">Diajukan Oleh</td>
        <td class="center">Diketahui Oleh</td>
        <td class="center">Disetujui Oleh</td>
    </tr>
    <tr>
        <td class="sign-space">
            @if($sign['diajukan_approved'])
                <div style="text-align: center; color: green;">
                    ✓ Disetujui<br>
                    <small>{{ $sign['diajukan_date'] }}</small>
                </div>
            @endif
        </td>
        <td class="sign-space">
            @if($sign['diketahui_approved'])
                <div style="text-align: center; color: green;">
                    ✓ Disetujui<br>
                    <small>{{ $sign['diketahui_date'] }}</small>
                </div>
            @endif
        </td>
        <td class="sign-space">
            @if($sign['disetujui_approved'])
                <div style="text-align: center; color: green;">
                    ✓ Disetujui<br>
                    <small>{{ $sign['disetujui_date'] }}</small>
                </div>
            @endif
        </td>
    </tr>
    <tr>
        <td class="center">{{ $sign["diajukan"] }}</td>
        <td class="center">{{ $sign["diketahui"]?->name ?? '' }}</td>
        <td class="center">{{ $sign["disetujui"]?->name ?? '' }}</td>
    </tr>
</table>
```

### Update Controller untuk PDF:

```php
// Method showPdf di FormController
public function showPdf($id)
{
    $softwareInstallation = SoftwareInstallation::with([
        'pemohon', 'pemohon.division', 'pemohon.regional',
        'superior1', 'managerIt', 'approvals'
    ])->findOrFail($id);

    $date = $softwareInstallation->created_at->format('d F Y');
    $softwareOptions = $this->softwareOptions;
    $selectedSoftware = $softwareInstallation->softwares;
    $keterangan = $softwareInstallation->keterangan;

    $sign = [
        'diajukan' => $softwareInstallation->pemohon->name,
        'diketahui' => $softwareInstallation->superior1,
        'disetujui' => $softwareInstallation->managerIt,
        'diajukan_approved' => true, // Selalu true karena dia yang mengajukan
        'diketahui_approved' => $softwareInstallation->isApprovedByLevel(1),
        'disetujui_approved' => $softwareInstallation->isApprovedByLevel(2),
        'diajukan_date' => $softwareInstallation->created_at->format('d M Y'),
        'diketahui_date' => $softwareInstallation->approvals->where('level', 1)->first()?->approved_at?->format('d M Y'),
        'disetujui_date' => $softwareInstallation->approvals->where('level', 2)->first()?->approved_at?->format('d M Y'),
    ];

    $pdf = Pdf::loadView('laporan', compact(
        'pemohon', 'date', 'softwareOptions', 'selectedSoftware', 'keterangan', 'sign'
    ))->setPaper('a4', 'portrait');

    $pdf->getDomPDF()->getOptions()->set('isImagickEnabled', false);

    return $pdf->stream("install-software-{$id}.pdf");
}
```

---

## 8. Alur Lengkap

```
[User Login]
    │
    ▼
[Form Software Installation]
    │ POST /form-it/forms/software-installation
    ▼
[Simpan ke Database]
    │ - software_installations (status: pending)
    │ - approvals (2 record: level 1 pending, level 2 pending)
    ▼
[Redirect ke Halaman Status]
    │ GET /form-it/forms/software-installation/{id}
    ▼
[Superior1 Login]
    │
    ▼
[Halaman Approval] → GET /form-it/approval
    │
    ▼
[Lihat Detail] → GET /form-it/approval/{id}
    │
    ▼
[Approve/Reject] → POST /form-it/approval/{id}/process
    │
    ├── [Approve Level 1]
    │       │
    │       ▼
    │   Update approvals (level 1: approved)
    │   Update software_installations (status: process)
    │       │
    │       ▼
    │   [Manager IT Login]
    │       │
    │       ▼
    │   [Halaman Approval] → Hanya lihat pengajuan dengan status 'process'
    │       │
    │       ▼
    │   [Approve Level 2]
    │       │
    │       ▼
    │   Update approvals (level 2: approved)
    │   Update software_installations (status: approved, approved_at: now)
    │       │
    │       ▼
    │   [Pemohon Bisa Download PDF]
    │       │
    │       ▼
    │   PDF menampilkan tanda tangan ✓ di kolom Diketahui & Disetujui
    │
    └── [Reject]
            │
            ▼
        Update approvals (status: rejected)
        Update software_installations (status: rejected)
        Tampilkan pesan penolakan
```

---

## 9. Langkah Implementasi

### Phase 1: Database
1. Buat migration untuk `software_installations` table
2. Buat migration untuk `approvals` table
3. Jalankan `php artisan migrate`

### Phase 2: Models
1. Buat model `SoftwareInstallation`
2. Buat model `Approval`
3. Tambahkan relasi di model `Employee` jika diperlukan

### Phase 3: Controller & Routes
1. Modifikasi `FormController` (tambahkan penyimpanan ke DB)
2. Buat `ApprovalController`
3. Tambahkan routes di `routes/routers/form-it.php`

### Phase 4: Views
1. Buat view `form-it/approval/index.blade.php` (dashboard approval)
2. Buat view `form-it/approval/show.blade.php` (detail approval)
3. Buat view `form-it/forms/software-installation-show.blade.php` (status pengajuan pemohon)
4. Update PDF template `laporan.blade.php`

### Phase 5: Testing
1. Test buat pengajuan baru
2. Test login sebagai Superior1, approve pengajuan
3. Test login sebagai Manager IT, approve pengajuan
4. Test download PDF setelah approval selesai
5. Test rejection flow

---

## 10. Catatan Tambahan

### Notifikasi (Opsional - Phase 6)
- Kirim email notifikasi saat pengajuan baru dibuat
- Kirim email notifikasi saat approve/reject
- Tambahkan notifikasi in-app (jika ada sistem notifikasi)

### Audit Trail
- Log semua aktivitas approval (siapa, kapan, apa yang dilakukan)
- Bisa ditambahkan kolom `ip_address` dan `user_agent` di tabel `approvals`

### Error Handling
- Pastikan validasi data sebelum approve/reject
- Handle kasus dimana superior1 atau manager IT tidak ditemukan
- Handle kasus concurrent approval (race condition)
