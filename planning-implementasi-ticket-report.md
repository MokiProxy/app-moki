# Planning: Implementasi Generate Laporan Tiket (PDF & Excel)

## Kondisi Saat Ini

- **ReportController** (`app/Http/Controllers/HelpDesk/ReportController.php`): Sudah ada method `index()` dan `datatable()`, tetapi belum ada method untuk generate PDF/Excel.
- **View** (`resources/views/helpdesk/reports/index.blade.php`): Sudah ada DataTable yang menampilkan tiket, tetapi belum ada tombol generate.
- **Route** (`routes/web.php:85-86`): `Route::resource("reports", ...)` dan `datatable` sudah terdaftar.
- **Packages**: `barryvdh/laravel-dompdf` (PDF) dan `maatwebsite/excel` (Excel) sudah terinstall di `composer.json`.

---

## Yang Akan Diimplementasi

### 1. Route

```php
Route::get('reports/generate-pdf', [HelpDeskReportController::class, 'generatePdf'])->name('reports.generate-pdf');
Route::get('reports/generate-excel', [HelpDeskReportController::class, 'generateExcel'])->name('reports.generate-excel');
```

### 2. Controller — Method `generatePdf()`

```php
public function generatePdf()
{
    $tickets = Ticket::with(['requester.employee.division', 'assignedTo', 'ticketCategory', 'ticketPriority'])
        ->latest()
        ->get();

    $pdf = Pdf::loadView('helpdesk.reports.pdf', compact('tickets'));
    $pdf->setOption('isRemoteEnabled', true);
    return $pdf->download('laporan-tiket-' . date('Y-m-d') . '.pdf');
}
```

**Import yang ditambahkan:**
```php
use Barryvdh\DomPDF\Facade\Pdf;
```

### 3. Controller — Method `generateExcel()`

```php
public function generateExcel()
{
    $tickets = Ticket::with(['requester.employee.division', 'assignedTo', 'ticketCategory', 'ticketPriority'])
        ->latest()
        ->get();

    return Excel::download(new TicketExport($tickets), 'laporan-tiket-' . date('Y-m-d') . '.xlsx');
}
```

**Import yang ditambahkan:**
```php
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TicketExport;
```

### 4. Export Class — `app/Exports/TicketExport.php` (Baru)

```php
class TicketExport implements FromCollection, WithHeadings, WithMapping
{
    protected $tickets;

    public function __construct($tickets)
    {
        $this->tickets = $tickets;
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return ['No', 'Nomor Tiket', 'Pemohon', 'Divisi', 'Judul', 'Kategori', 'Prioritas', 'Teknisi', 'Batas Waktu', 'Status', 'Rating'];
    }

    public function map($ticket): array
    {
        return [
            $ticket->getKey(),
            $ticket->ticket_number,
            $ticket->requester->employee->name ?? '-',
            $ticket->requester->employee->division->name ?? '-',
            $ticket->title,
            $ticket->ticketCategory->name ?? '-',
            $ticket->ticketPriority->name ?? '-',
            $ticket->assignedTo->name ?? 'Belum Ditugaskan',
            $ticket->due_time,
            $ticket->status,
            $ticket->rating ?? '-',
        ];
    }
}
```

### 5. PDF View — `resources/views/helpdesk/reports/pdf.blade.php` (Baru)

Template PDF dengan header "Laporan Tiket Helpdesk", tanggal generate, dan table berisi:
- Nomor Tiket, Pemohon, Divisi, Judul, Kategori, Prioritas, Teknisi, Batas Waktu, Status, Rating

### 6. View — `resources/views/helpdesk/reports/index.blade.php`

Tambahkan 2 tombol di header section:

```html
<div class="flex-shrink-0 d-flex gap-1">
    <a href="{{ route('helpdesk.reports.generate-pdf') }}" class="btn btn-danger" target="_blank">
        <i class="mdi mdi-file-pdf-box me-1"></i> Generate PDF
    </a>
    <a href="{{ route('helpdesk.reports.generate-excel') }}" class="btn btn-success">
        <i class="mdi mdi-file-excel me-1"></i> Generate Excel
    </a>
    <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
</div>
```

---

## File yang Berubah/Ditambah

| # | File | Aksi |
|---|---|---|
| 1 | `routes/web.php` | Ubah — tambah 2 route GET (generate-pdf, generate-excel) |
| 2 | `app/Http/Controllers/HelpDesk/ReportController.php` | Ubah — tambah `generatePdf()` dan `generateExcel()` |
| 3 | `app/Exports/TicketExport.php` | **Baru** — class export Excel |
| 4 | `resources/views/helpdesk/reports/pdf.blade.php` | **Baru** — template PDF |
| 5 | `resources/views/helpdesk/reports/index.blade.php` | Ubah — tambah 2 tombol generate |

---

## Data yang Di-Laporan

Semua tiket (termasuk yang sudah soft delete tidak ditampilkan). Kolom:

| Kolom | Sumber |
|---|---|
| Nomor Tiket | `tickets.ticket_number` |
| Pemohon | `requester.employee.name` |
| Divisi | `requester.employee.division.name` |
| Judul | `tickets.title` |
| Kategori | `ticket_categories.name` |
| Prioritas | `ticket_priorities.name` |
| Teknisi | `users.name` (assignedTo) |
| Batas Waktu | `tickets.due_time` |
| Status | `tickets.status` |
| Rating | `tickets.rating` |
