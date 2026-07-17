# Planning: Implementasi RBAC pada Dashboard Helpdesk

## Kondisi Saat Ini

Dashboard helpdesk (`/helpdesk`) menampilkan data **seluruh tiket** tanpa memperhatikan role user yang login. Semua user melihat data yang sama.

---

## Database & Relasi

Relasi yang relevan untuk filtering tiket berdasarkan divisi:

```
tickets.requester_id → users.id
users.employee_id    → employees.id
employees.division_id → divisions.id
```

Model yang terlibat:
- `User`: kolom `role_id` (1=Superadmin, 2=Admin, 3=Atasan, 4=Teknisi), relasi `employee()` → `belongsTo(Employee)`
- `Employee`: kolom `division_id`, relasi `division()` → `belongsTo(Division)`
- `Ticket`: kolom `requester_id`, relasi `requester()` → `belongsTo(User)`

---

## Kriteria RBAC

| Role ID | Keterangan | Scope Data |
|---|---|---|
| 3 (Atasan) | Staff/Atasan | Tiket dari divisi yang sama dengan divisi user tersebut |
| Selain 3 | Superadmin, Admin, Teknisi, dll. | Semua tiket |

Filter divisi dilakukan melalui relasi: `tickets.requester.employee.division.id`

---

## File yang Akan Diubah

### 1. `app/Http/Controllers/HelpDesk/DashboardController.php`

Tambahkan private method `getTicketQuery()` yang mengembalikan `Builder` query `Ticket` dengan scope yang sesuai role:

```php
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

private function getTicketQuery(): Builder
{
    $user = auth()->user();

    if ($user->role_id === User::ROLE_ATASAN) {
        $divisionId = $user->employee->division_id;

        return Ticket::whereHas('requester.employee', function ($q) use ($divisionId) {
            $q->where('employees.division_id', $divisionId);
        });
    }

    return Ticket::query();
}
```

Kemudian ubah semua query di `index()` dan `chartData()` agar menggunakan method ini:

**`index()` — stat cards:**
```php
$query = $this->getTicketQuery();
$totalTicket = (clone $query)->count();
$openTicket = (clone $query)->where('status', 'OPEN')->count();
$inProgressTicket = (clone $query)->where('status', 'IN_PROGRESS')->count();
$closedTicket = (clone $query)->whereIn('status', ['RESOLVED', 'CLOSED'])->count();
```

**`index()` — status & category counts:**
```php
$statusCounts = (clone $query)->select('status', DB::raw('count(*) as total'))
    ->groupBy('status')->get();

$categoryCounts = (clone $query)->select('ticket_category_id', 'ticket_categories.name', DB::raw('count(*) as total'))
    ->join('ticket_categories', 'tickets.ticket_category_id', '=', 'ticket_categories.id')
    ->groupBy('ticket_category_id', 'ticket_categories.name')
    ->pluck('total', 'name');
```

**`chartData()` — line chart:**
```php
$query = $this->getTicketQuery();
$tickets = (clone $query)
    ->where('created_at', '>=', $startDate)
    ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
    ->groupBy('date')
    ->orderBy('date')
    ->get();
```

**Import yang ditambahkan:**
```php
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
```

---

## Ringkasan Perubahan

| # | File | Aksi |
|---|---|---|
| 1 | `app/Http/Controllers/HelpDesk/DashboardController.php` | Ubah — tambah method `getTicketQuery()`, refactor semua query di `index()` dan `chartData()` |

---

## Catatan

- Tidak ada perubahan route, view, atau model.
- `getTicketQuery()` mengembalikan `Builder` (bukan Collection), sehingga bisa di-`clone()` untuk multiple count query.
- Akses `auth()->user()->employee->division_id` aman karena relasi sudah terdefinisi di model. Jika employee null (user tidak punya employee), akan error — pertimbangkan guard jika diperlukan.
