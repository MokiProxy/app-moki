# Planning: Implementasi Widget Dashboard Helpdesk

## Kondisi Saat Ini

Dashboard helpdesk (`/helpdesk`) saat ini masih berupa **placeholder**:

- **Controller** (`app/Http/Controllers/HelpDesk/DashboardController.php`): Hanya me-return view tanpa data apa pun.
- **View** (`resources/views/helpdesk/dashboard/index.blade.php`): 4 stat card dengan nilai hardcoded `"0"`, Chart.js di-load via CDN tetapi tidak ada chart yang di-render.
- **Route** (`routes/web.php:55`): `GET /helpdesk` sudah terdaftar.

---

## Widget yang Akan Diimplementasi

| Widget | Tipe | Penjelasan |
|---|---|---|
| Ticket Trend | Line Chart | Jumlah tiket berdasarkan waktu, dengan filter 7 hari, 30 hari, 3 bulan, 1 tahun |
| Ticket by Status | Donut Chart | Jumlah tiket per status (`OPEN`, `ASSIGNED`, `IN_PROGRESS`, `PENDING`, `RESOLVED`, `CLOSED`, `REJECTED`) |
| Ticket by Category | Pie Chart | Jumlah tiket per kategori (`ticket_categories.name`) |

---

## File yang Akan Diubah/Ditambah

### 1. `app/Http/Controllers/HelpDesk/DashboardController.php`

Ubah `index()` method untuk meng-query data statistik dan chart:

```
Method: index(Request $request)
```

**Data untuk stat cards (dikirim langsung ke view):**

- `$totalTicket` — `Ticket::count()`
- `$openTicket` — `Ticket::where('status', 'OPEN')->count()`
- `$inProgressTicket` — `Ticket::where('status', 'IN_PROGRESS')->count()`
- `$closedTicket` — `Ticket::whereIn('status', ['RESOLVED', 'CLOSED'])->count()`

**Data untuk chart (dikirim ke view via `compact()`):**

- `$statusCounts` — `Ticket::select('status', DB::raw('count(*) as total'))->groupBy('status')->get()`
- `$categoryCounts` — `Ticket::select('ticket_category_id', DB::raw('count(*) as total'))->join('ticket_categories', 'tickets.ticket_category_id', '=', 'ticket_categories.id')->groupBy('ticket_category_id', 'ticket_categories.name')->pluck('total', 'name')`

**Import yang ditambahkan:**
- `use App\Models\Ticket;`
- `use Illuminate\Support\Facades\DB;`

---

### 2. `routes/web.php`

Tambah 1 route AJAX di dalam group `helpdesk` (setelah line 55):

```php
Route::get('dashboard/chart-data', [HelpDeskDashboardController::class, 'chartData'])->name('dashboard.chart-data');
```

**Method `chartData()` di Controller:**

Menerima query parameter `filter` (nilai: `7d`, `30d`, `3m`, `1y`) dan mengembalikan JSON data untuk line chart:

```php
public function chartData(Request $request)
{
    $filter = $request->get('filter', '7d');
    $now = Carbon::now();

    switch ($filter) {
        case '7d':  $startDate = $now->copy()->subDays(7);  $format = 'd M'; break;
        case '30d': $startDate = $now->copy()->subDays(30); $format = 'd M'; break;
        case '3m':  $startDate = $now->copy()->subMonths(3); $format = 'M Y'; break;
        case '1y':  $startDate = $now->copy()->subYear();   $format = 'M Y'; break;
    }

    $tickets = Ticket::where('created_at', '>=', $startDate)
        ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    return response()->json([
        'labels' => $tickets->pluck('date')->map(fn($d) => Carbon::parse($d)->format($format)),
        'data'   => $tickets->pluck('total'),
    ]);
}
```

---

### 3. `resources/views/helpdesk/dashboard/index.blade.php`

#### 3a. Stat Cards (section `content`)

Ganti nilai hardcoded `"0"` dengan variabel dari controller:

- Total Tiket → `{{ $totalTicket }}`
- Tiket Open → `{{ $openTicket }}`
- Tiket In Progress → `{{ $inProgressTicket }}`
- Tiket Closed → `{{ $closedTicket }}`

#### 3b. Widget Row (tambahkan setelah stat cards)

```
Row layout (3 kolom):
┌──────────────────────────────┬──────────────┬──────────────┐
│  Ticket Trend (Line Chart)   │ Ticket by    │ Ticket by    │
│  col-lg-8                    │ Status       │ Category     │
│  + filter buttons            │ (Donut)      │ (Pie)        │
│                              │ col-lg-2     │ col-lg-2     │
└──────────────────────────────┴──────────────┴──────────────┘
```

**HTML structure:**

```html
<!-- Row Chart -->
<div class="row g-4 mb-4">

    <!-- Ticket Trend Line Chart -->
    <div class="col-lg-8">
        <div class="card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="fa-solid fa-chart-line text-primary me-2"></i>Ticket Trend
                </h6>
                <div class="btn-group btn-group-sm" id="trendFilter">
                    <button class="btn btn-outline-primary active" data-filter="7d">7 Hari</button>
                    <button class="btn btn-outline-primary" data-filter="30d">30 Hari</button>
                    <button class="btn btn-outline-primary" data-filter="3m">3 Bulan</button>
                    <button class="btn btn-outline-primary" data-filter="1y">1 Tahun</button>
                </div>
            </div>
            <div style="height: 320px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Ticket by Status Donut -->
    <div class="col-lg-2">
        <div class="card p-4 h-100">
            <h6 class="fw-bold mb-3 text-center">Ticket by Status</h6>
            <div style="height: 250px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Ticket by Category Pie -->
    <div class="col-lg-2">
        <div class="card p-4 h-100">
            <h6 class="fw-bold mb-3 text-center">Ticket by Category</h6>
            <div style="height: 250px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

</div>
```

#### 3c. JavaScript (section `js`)

```javascript
// 1. Ticket Trend Line Chart — dimuat via AJAX
let trendChart;

function loadTrendChart(filter = '7d') {
    fetch(`{{ route('helpdesk.dashboard.chart-data') }}?filter=${filter}`)
        .then(res => res.json())
        .then(json => {
            const ctx = document.getElementById('trendChart').getContext('2d');
            if (trendChart) trendChart.destroy();
            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: json.labels,
                    datasets: [{
                        label: 'Jumlah Tiket',
                        data: json.data,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79,70,229,0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#4f46e5'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [2,2] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
}

// Filter button click handler
document.querySelectorAll('#trendFilter .btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#trendFilter .btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        loadTrendChart(this.dataset.filter);
    });
});

// Load default
loadTrendChart('7d');

// 2. Ticket by Status — Donut Chart (data dari controller)
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($statusCounts->pluck('status')) !!},
        datasets: [{
            data: {!! json_encode($statusCounts->pluck('total')) !!},
            backgroundColor: [
                '#3b82f6', // OPEN — blue
                '#8b5cf6', // ASSIGNED — purple
                '#f59e0b', // IN_PROGRESS — amber
                '#6b7280', // PENDING — gray
                '#10b981', // RESOLVED — green
                '#1e293b', // CLOSED — dark
                '#ef4444', // REJECTED — red
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } } },
        cutout: '60%'
    }
});

// 3. Ticket by Category — Pie Chart (data dari controller)
new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
        labels: {!! json_encode($categoryCounts->keys()) !!},
        datasets: [{
            data: {!! json_encode($categoryCounts->values()) !!},
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } } }
    }
});
```

---

## Ringkasan Perubahan

| # | File | Aksi |
|---|---|---|
| 1 | `app/Http/Controllers/HelpDesk/DashboardController.php` | Ubah — tambah query statistik + `chartData()` method |
| 2 | `routes/web.php` | Ubah — tambah 1 route GET `dashboard/chart-data` |
| 3 | `resources/views/helpdesk/dashboard/index.blade.php` | Ubah — stat cards dinamis + tambah 3 widget chart + JS |

---

## Referensi

- Pola controller → lihat `app/Http/Controllers/DashboardController.php` (AMS dashboard)
- Pola chart → lihat `resources/views/components/dashboard.blade.php` (line 162-206)
- Data model → `app/Models/Ticket.php` (fillable fields, relationships)
- Status tiket → `OPEN`, `ASSIGNED`, `IN_PROGRESS`, `PENDING`, `RESOLVED`, `CLOSED`, `REJECTED`

---

## Urutan Implementasi

1. Ubah `DashboardController.php` — tambah query data statis + method `chartData()`
2. Tambah route di `web.php`
3. Ubah `index.blade.php` — stat cards dinamis + HTML chart containers + JavaScript
