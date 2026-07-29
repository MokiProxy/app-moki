# Planning Implementasi Manajemen User & Role

## Ringkasan

Dua fitur utama yang akan dikembangkan:
1. **Multi-Role per User** — 1 user bisa memiliki lebih dari 1 role
2. **Permission Management per Role** — tiap role bisa dikelola permission-nya

---

## 1. Analisis Database (Spatie Permission)

### Tables Sudah Ada (dari migrasi `2026_07_29_125551_create_permission_tables.php`)

| Table | Fungsi |
|-------|--------|
| `roles` | Menyimpan role (`id`, `name`, `guard_name`, `timestamps`) |
| `permissions` | Menyimpan permission (`id`, `name`, `guard_name`, `timestamps`) |
| `model_has_roles` | Relasi polymorph: user → role (`role_id`, `model_type`, `model_id`) |
| `model_has_permissions` | Relasi polymorph: user → permission langsung (`permission_id`, `model_type`, `model_id`) |
| `role_has_permissions` | Relasi role → permission (`permission_id`, `role_id`) |

### Kondisi Saat Ini

- **Roles terisi**: `super-admin`, `approver`, `staff`, `teknisi`, `admin` (dari seeder)
- **Permissions KOSONG**: TIDAK ADA satupun permission di database
- **User bisa punya multiple roles** secara teknis (tabel `model_has_roles` mendukung), tapi UI hanya mengirim 1 role

### Relasi User → Roles (via Spatie `HasRoles` trait)

```
users ---< model_has_roles >--- roles
   id          role_id            id
               model_id (=users.id)
               model_type (=App\Models\User)
```

Karena `model_has_roles` tidak pakai UNIQUE pada `(model_id, model_type)`, satu user bisa punya banyak role.

---

## 2. Fitur Multi-Role per User

### 2.1 Yang Perlu Diubah

#### A. Controller: `app/Http/Controllers/ITAdmin/UserController.php`

| Method | Perubahan |
|--------|-----------|
| `store()` | `syncRoles([$role_name])` → `syncRoles($request->role_names)` (array) |
| `edit()` | `getRoleNames()->first()` → `getRoleNames()` (semua role) |
| `datatable()` | Badge 1 role → multiple badges untuk semua role user |
| `index()` | Tambah `$roles = Role::all()` untuk dropdown dinamis |

#### B. View: `resources/views/it-admin/users/index.blade.php`

| Elemen | Perubahan |
|--------|-----------|
| Form tambah | `<select name="role_name">` → `<select name="role_names[]" multiple>` dengan Select2 multi-select |
| Modal edit | Sama, pakai multi-select + pre-select current roles via `.val(roleNamesArray).trigger('change')` |
| DataTable | Kolom role_name render beberapa badge (bukan 1) |
| Opsi role | Dari hardcoded → loop `$roles` dari database |

#### C. View: `resources/views/layouts/partials/it-admin/app-sidebar.blade.php`

| Baris | Perubahan |
|-------|-----------|
| Ln 2 | `getRoleNames()->first()` → `getRoleNames()` untuk handle multiple roles di logic sidebar |

### 2.2 Skema UI Multi-Select

```html
<!-- Form Tambah & Edit -->
<select class="form-select select2-multiple" name="role_names[]" multiple required>
    @foreach($roles as $role)
        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
    @endforeach
</select>
```

```javascript
// Inisialisasi Select2 Multi
$('.select2-multiple').select2({
    width: '100%',
    placeholder: '-- Pilih Role --'
});

// Pre-select saat edit
$('#edit_role_names').val(res.data.role_names).trigger('change');
```

### 2.3 Skema DataTable Multi-Badge

```php
// Di UserController@datatable()
->addColumn('role_names', function ($row) {
    $roles = $row->getRoleNames();
    $badges = [
        'super-admin' => 'bg-danger',
        'admin'       => 'bg-success',
        'approver'    => 'bg-warning text-dark',
        'staff'       => 'bg-info text-dark',
        'teknisi'     => 'bg-primary',
    ];
    $html = '';
    foreach ($roles as $role) {
        $class = $badges[$role] ?? 'bg-secondary';
        $html .= '<span class="badge ' . $class . ' me-1">' . ucfirst($role) . '</span>';
    }
    return $html ?: '<span class="badge bg-secondary">None</span>';
})
->rawColumns(['role_names', 'action'])
```

---

## 3. Fitur Permission Management per Role

### 3.1 Persiapan Data: Seeder Permission

File: `database/seeders/RolePermissionSeeder.php` (sudah ada, perlu ditambah)

```php
// Buat Permission Records (saat ini KOSONG)
$permissions = [
    // Menu akses
    'ams.menu',
    'helpdesk.menu',
    'data-pegawai.menu',
    'form-it.menu',
    'sop-it.menu',

    // Ticket Helpdesk
    'tickets.create',
    'tickets.edit',
    'tickets.delete',
    'tickets.view-all',
    'tickets.assign',
    'tickets.resolve',
    'tickets.approve',
    'tickets.confirm',

    // Manajemen User & Role
    'users.manage',
    'roles.manage',
    'permissions.manage',

    // AMS (Asset Management)
    'assets.create',
    'assets.edit',
    'assets.delete',
    'assets.view',
    'assets.import',
    'transactions.create',
    'transactions.approve',
    'employees.manage',
];

foreach ($permissions as $perm) {
    Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
}
```

### 3.2 Controller: Update `RoleController`

**Method baru:**

| Method | Route | Fungsi |
|--------|-------|--------|
| `permissions($id)` | `GET /it-admin/roles/permissions/{id}` | Ambil semua permission + permission milik role |
| `syncPermissions(Request, $id)` | `POST /it-admin/roles/sync-permissions/{id}` | Simpan permission yang dicentang ke role |

**Detail method:**

```php
use Spatie\Permission\Models\Permission;

public function permissions($id)
{
    $role = Role::with('permissions')->findOrFail($id);
    $allPermissions = Permission::orderBy('name')->get();
    $rolePermissionIds = $role->permissions->pluck('id')->toArray();

    return response()->json([
        'success' => true,
        'role' => [
            'id' => $role->id,
            'name' => $role->name,
        ],
        'permissions' => $allPermissions,
        'role_permission_ids' => $rolePermissionIds,
    ]);
}

public function syncPermissions(Request $request, $id)
{
    $request->validate([
        'permissions' => 'nullable|array',
        'permissions.*' => 'exists:permissions,id',
    ]);

    $role = Role::findOrFail($id);
    $role->syncPermissions($request->permissions ?? []);

    return response()->json([
        'success' => true,
        'message' => 'Permission berhasil disinkronkan!',
    ]);
}
```

### 3.3 Route Baru

File: `routes/routers/it-admin.php`

```php
Route::prefix("roles")->name("roles.")->group(function () {
    // ... route existing ...

    Route::get("/permissions/{id}", [RoleController::class, 'permissions'])
        ->name("permissions");
    Route::post("/sync-permissions/{id}", [RoleController::class, 'syncPermissions'])
        ->name("sync-permissions");
});
```

### 3.4 View: Tambah Permission Manager di Role Page

File: `resources/views/it-admin/roles/index.blade.php`

**Tambah tombol "Permissions" di kolom aksi DataTable:**

```php
->addColumn('action', function ($row) {
    $id = $row->id;
    return '<div class="d-flex gap-1 justify-content-center">
        <button class="btn btn-sm btn-success btn-permissions" data-id="'.$id.'" title="Atur Permission">
            <i class="mdi mdi-shield-key"></i>
        </button>
        <button class="btn btn-sm btn-primary btn-edit" data-id="'.$id.'" ...>
            <i class="mdi mdi-pencil"></i>
        </button>
        ...
    </div>';
})
```

**Modal Permission Manager (baru):**

```html
<div class="modal fade" id="modalPermissions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">
                    <i class="mdi mdi-shield-key me-1"></i>
                    Atur Permission: <span id="perm_role_name"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPermissions">
                @csrf
                <input type="hidden" name="role_id" id="perm_role_id">
                <div class="modal-body">
                    <div class="row" id="permissions_container">
                        <!-- Loading... -->
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

### 3.5 JavaScript Permission Checkbox

```javascript
// Load permissions via AJAX
$(document).on('click', '.btn-permissions', function() {
    var id = $(this).data('id');
    $('#perm_role_id').val(id);

    $.get("{{ url('it-admin/roles/permissions') }}/" + id, function(res) {
        if (res.success) {
            $('#perm_role_name').text(res.role.name);
            var html = '';
            // Kelompokkan permission berdasarkan prefix (misal: tickets.*, ams.*, dll)
            var groups = {};
            res.permissions.forEach(function(p) {
                var group = p.name.split('.')[0] || 'general';
                if (!groups[group]) groups[group] = [];
                groups[group].push(p);
            });

            for (var group in groups) {
                html += '<div class="col-md-6 mb-3">';
                html += '<div class="card"><div class="card-header py-2">';
                html += '<strong class="text-uppercase small">' + group + '</strong>';
                html += '</div><div class="card-body py-2">';
                groups[group].forEach(function(p) {
                    var checked = res.role_permission_ids.includes(p.id) ? 'checked' : '';
                    html += '<div class="form-check mb-1">';
                    html += '<input class="form-check-input permission-check" type="checkbox" ';
                    html += 'name="permissions[]" value="' + p.id + '" id="perm_' + p.id + '" ' + checked + '>';
                    html += '<label class="form-check-label small" for="perm_' + p.id + '">';
                    html += p.name + '</label></div>';
                });
                html += '</div></div></div>';
            }
            $('#permissions_container').html(html);
            $('#modalPermissions').modal('show');
        }
    });
});

// Submit permission changes
$('#formPermissions').on('submit', function(e) {
    e.preventDefault();
    var id = $('#perm_role_id').val();
    $.ajax({
        url: "{{ url('it-admin/roles/sync-permissions') }}/" + id,
        type: "POST",
        data: $(this).serialize(),
        success: function(res) {
            $('#modalPermissions').modal('hide');
            Swal.fire('Berhasil!', res.message, 'success');
        },
        error: function(xhr) {
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan.';
            Swal.fire('Gagal!', msg, 'error');
        }
    });
});
```

---

## 4. Perubahan File — Ringkasan Lengkap

### File yang Diubah

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/ITAdmin/UserController.php` | Multi-role: store, edit, datatable, index |
| `app/Http/Controllers/ITAdmin/RoleController.php` | + method permissions() & syncPermissions() |
| `resources/views/it-admin/users/index.blade.php` | Multi-role: form, edit modal, datatable |
| `resources/views/it-admin/roles/index.blade.php` | + permission manager modal & tombol |
| `resources/views/layouts/partials/it-admin/app-sidebar.blade.php` | Handle multiple roles di sidebar |
| `routes/routers/it-admin.php` | + route permissions & sync-permissions |

### File Baru

| File | Isi |
|------|-----|
| Tidak ada file baru | Semua perubahan di file existing |

### File yang Perlu Diupdate (Seeder)

| File | Perubahan |
|------|-----------|
| `database/seeders/RolePermissionSeeder.php` | + Buat permission records + assign ke role |

---

## 5. Urutan Implementasi

### Phase 1: Multi-Role Support (prioritas tinggi)

| Step | File | Detail |
|------|------|--------|
| 1.1 | `UserController.php` | Ubah `store()` pakai `$request->role_names` array |
| 1.2 | `UserController.php` | Ubah `edit()` return semua role names |
| 1.3 | `UserController.php` | Ubah `datatable()` render multiple badges |
| 1.4 | `UserController.php` | Ubah `index()` kirim `$roles` ke view |
| 1.5 | `users/index.blade.php` | Form tambah: multi-select |
| 1.6 | `users/index.blade.php` | Modal edit: multi-select + pre-select |
| 1.7 | `users/index.blade.php` | DataTable: sesuaikan kolom role_names |
| 1.8 | `app-sidebar.blade.php` | Handle multiple roles |

### Phase 2: Permission Management (prioritas tinggi)

| Step | File | Detail |
|------|------|--------|
| 2.1 | `RolePermissionSeeder.php` | Tambah permission records |
| 2.2 | `RoleController.php` | Tambah method permissions() |
| 2.3 | `RoleController.php` | Tambah method syncPermissions() |
| 2.4 | `it-admin.php` routes | Tambah 2 route baru |
| 2.5 | `roles/index.blade.php` | Tambah tombol permissions di DataTable |
| 2.6 | `roles/index.blade.php` | Tambah modal permission manager |
| 2.7 | `roles/index.blade.php` | Tambah JS untuk load & simpan permission |

### Phase 3: Polish (setelah semua functional)

| Step | Detail |
|------|--------|
| 3.1 | Ganti hardcoded role options di view dengan dinamis dari DB |
| 3.2 | Update `RouteServiceProvider` atau auth redirect jika perlu |
| 3.3 | Update sidebar dan menu untuk permission-based access |

---

## 6. Catatan Penting

1. **`syncRoles()`** sudah support multiple roles dari Spatie — cukup kirim array
2. **Select2** sudah include di project — tinggal pakai `multiple` attribute
3. **Permission cache** auto-flush via `RefreshesPermissionCache` trait
4. **Middleware `role:`** sudah support pipe syntax (`role:super-admin|admin`)
5. **DataTable server-side** — multiple badges di-render dari server, bukan client-side
6. Semua permission.id dikirim sebagai array via checkboxes — `syncPermissions()` terima array ID
