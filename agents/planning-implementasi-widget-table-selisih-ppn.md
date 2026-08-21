# Planning: Implementasi Interaksi Widget ke Tabel Selisih PPN

## 1. Ikhtisar

### Tujuan
Membuat interaksi klik pada widget **Selisih PPN (SPT)** dan **Selisih PPN (GL)** di dashboard EQTax sehingga tabel data di bawahnya akan menampilkan data ekualisasi yang sesuai:
- **Selisih PPN (SPT)** → Menampilkan data dengan `selisih_ppn > 0` (kurang bayar)
- **Selisih PPN (GL)** → Menampilkan data dengan `selisih_ppn < 0` (lebih bayar/restitusi)

### Kondisi Saat Ini
- Dashboard sudah memiliki 4 widget stat cards (baris 198-263 di view)
- Tabel data ekualisasi sudah ada dengan pagination (baris 344-397 di view)
- Filter form (tahun, bulan, entity, status) sudah berfungsi
- **Belum ada interaksi klik** pada widget untuk memfilter tabel

---

## 2. Struktur Database & Model

### Tabel: `eqtax_equalization_results`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | Primary key |
| `period` | varchar | Format: YYYY-MM |
| `entity` | varchar | Kode entity (TJMO, SBHO, PLTR) |
| `no_faktur_pajak` | varchar | Nomor faktur pajak |
| `nama_penjual` | varchar | Nama penjual |
| `dpp_spt` | bigInteger | DPP dari SPT |
| `dpp_gl` | double | DPP dari GL |
| `ppn_spt` | bigInteger | PPN dari SPT |
| `ppn_gl` | double | PPN dari GL |
| `selisih_ppn` | double | Selisih PPN (positif = kurang bayar, negatif = lebih bayar) |
| `status` | varchar | MATCH, TO_BE_CHECK, SPT_ONLY, GL_ONLY |
| `keterangan` | text | Keterangan tambahan |

### Model: `EQTAXEqualizationResult`
- Lokasi: `app/Models/EQTAXEqualizationResult.php`
- Table: `eqtax_equalization_results`
- Scope: `scopePeriod()`, `scopeEntity()`, `scopeStatus()`

---

## 3. File yang Akan Dimodifikasi

| # | File | Perubahan |
|---|------|-----------|
| 1 | `routes/routers/eqtax.php` | Tambah route AJAX endpoint |
| 2 | `app/Http/Controllers/EQTax/DashboardController.php` | Tambah method `getFilteredData()` |
| 3 | `resources/views/eqtax/dashboard/index.blade.php` | Tambah JavaScript click handler & update tabel via AJAX |

---

## 4. Detail Implementasi

### 4.1 Route AJAX Endpoint

**File:** `routes/routers/eqtax.php`

Tambah route baru di dalam group `eqtax`:

```php
Route::get('/dashboard/filter-selisih', [DashboardController::class, 'getFilteredData'])
    ->name('dashboard.filter-selisih');
```

**Full path URL:** `/eqtax/dashboard/filter-selisih`

**Parameter Query:**
- `type` (string): `kurang_bayar` atau `lebih_bayar`
- `year` (string, optional): Filter tahun
- `month_from` (string, optional): Filter bulan dari
- `month_to` (string, optional): Filter bulan sampai
- `entity` (string, optional): Filter entity
- `status` (string, optional): Filter status

---

### 4.2 Controller Method

**File:** `app/Http/Controllers/EQTax/DashboardController.php`

Tambah method baru `getFilteredData(Request $request)`:

```php
public function getFilteredData(Request $request)
{
    $type = $request->input('type'); // 'kurang_bayar' atau 'lebih_bayar'
    $filterYear = $request->input('year');
    $filterMonthFrom = $request->input('month_from');
    $filterMonthTo = $request->input('month_to');
    $filterEntity = $request->input('entity');
    $filterStatus = $request->input('status');

    $query = EQTAXEqualizationResult::query();

    // Filter berdasarkan tipe selisih
    if ($type === 'kurang_bayar') {
        $query->where('selisih_ppn', '>', 0);
    } elseif ($type === 'lebih_bayar') {
        $query->where('selisih_ppn', '<', 0);
    }

    // Apply filters yang sama dengan dashboard utama
    if ($filterYear) {
        $query->where('period', 'like', "{$filterYear}%");
    }

    if ($filterMonthFrom && $filterMonthTo) {
        $periodFrom = $filterYear ? "{$filterYear}-{$filterMonthFrom}" : $filterMonthFrom;
        $periodTo = $filterYear ? "{$filterYear}-{$filterMonthTo}" : $filterMonthTo;
        $query->whereBetween('period', [$periodFrom, $periodTo]);
    } elseif ($filterMonthFrom) {
        $periodFrom = $filterYear ? "{$filterYear}-{$filterMonthFrom}" : $filterMonthFrom;
        $query->where('period', '>=', $periodFrom);
    } elseif ($filterMonthTo) {
        $periodTo = $filterYear ? "{$filterYear}-{$filterMonthTo}" : $filterMonthTo;
        $query->where('period', '<=', $periodTo);
    }

    if ($filterEntity) {
        $query->where('entity', $filterEntity);
    }

    if ($filterStatus) {
        $query->where('status', $filterStatus);
    }

    $results = $query->orderByDesc('period')
                     ->orderByDesc('selisih_ppn')
                     ->paginate(20)
                     ->appends($request->query());

    return response()->json([
        'success' => true,
        'data' => $results
    ]);
}
```

**Return Format (JSON):**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "period": "2026-01",
                "entity": "TJMO",
                "no_faktur_pajak": "...",
                "nama_penjual": "...",
                "dpp_spt": 1000000,
                "dpp_gl": 950000,
                "ppn_spt": 110000,
                "ppn_gl": 104500,
                "selisih_ppn": 5500,
                "status": "TO_BE_CHECK",
                "keterangan": null
            }
        ],
        "last_page": 5,
        "per_page": 20,
        "total": 100
    }
}
```

---

### 4.3 Frontend (View + JavaScript)

**File:** `resources/views/eqtax/dashboard/index.blade.php`

#### 4.3.1 Tambah ID pada Widget Cards

Modifikasi widget cards untuk menambahkan atribut `data-type` dan class `clickable-widget`:

**Widget Selisih PPN (SPT)** (sekitar baris 231):
```html
<div class="col-md-3">
    <div class="card stat-card bg-danger-grad clickable-widget" data-type="kurang_bayar" style="cursor: pointer;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white mb-1">Selisih PPN (SPT)</h6>
                    <h4 class="text-white mb-0">Rp {{ number_format($selisihKurangBayar, 0, ',', '.') }}</h4>
                    <small class="text-white-50">{{ $countKurangBayar }} faktur</small>
                </div>
                <div class="icon-overlay">
                    <i class="fas fa-arrow-up"></i>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Widget Selisih PPN (GL)** (sekitar baris 247):
```html
<div class="col-md-3">
    <div class="card stat-card bg-info-grad clickable-widget" data-type="lebih_bayar" style="cursor: pointer;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white mb-1">Selisih PPN (GL)</h6>
                    <h4 class="text-white mb-0">Rp {{ number_format(abs($selisihLebihBayar), 0, ',', '.') }}</h4>
                    <small class="text-white-50">{{ $countLebihBayar }} faktur</small>
                </div>
                <div class="icon-overlay">
                    <i class="fas fa-arrow-down"></i>
                </div>
            </div>
        </div>
    </div>
</div>
```

#### 4.3.2 Tambah Indikator Aktif pada Widget

Tambahkan style CSS untuk indikator widget aktif:

```css
.clickable-widget.active {
    outline: 3px solid #fff;
    outline-offset: -3px;
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}

.clickable-widget.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: #fff;
    border-radius: 0 0 12px 12px;
}
```

#### 4.3.3 Tambah Judul Dinamis di atas Tabel

Tambahkan elemen untuk menampilkan judul dinamis sebelum tabel:

```html
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0" id="table-title">
        <i class="fas fa-table me-2"></i>Data Ekualisasi Terbaru
    </h5>
    <button class="btn btn-sm btn-outline-secondary d-none" id="btn-reset-widget" onclick="resetWidgetFilter()">
        <i class="fas fa-times me-1"></i>Tampilkan Semua
    </button>
</div>
```

#### 4.3.4 JavaScript Click Handler

Tambahkan di section `plugin`:

```javascript
// State untuk track widget aktif
let activeWidgetType = null;

// Event listener untuk widget click
document.querySelectorAll('.clickable-widget').forEach(widget => {
    widget.addEventListener('click', function() {
        const type = this.dataset.type;

        // Toggle: jika widget yang sama diklik lagi, reset
        if (activeWidgetType === type) {
            resetWidgetFilter();
            return;
        }

        // Set widget aktif
        activeWidgetType = type;

        // Update UI
        document.querySelectorAll('.clickable-widget').forEach(w => w.classList.remove('active'));
        this.classList.add('active');

        // Update judul
        const title = document.getElementById('table-title');
        const btnReset = document.getElementById('btn-reset-widget');
        if (type === 'kurang_bayar') {
            title.innerHTML = '<i class="fas fa-filter me-2"></i>Data Ekualisasi - Selisih PPN SPT (Kurang Bayar)';
        } else {
            title.innerHTML = '<i class="fas fa-filter me-2"></i>Data Ekualisasi - Selisih PPN GL (Lebih Bayar)';
        }
        btnReset.classList.remove('d-none');

        // Load data via AJAX
        loadFilteredData(type, 1);
    });
});

function loadFilteredData(type, page) {
    const params = new URLSearchParams({
        type: type,
        page: page,
        year: document.querySelector('select[name="year"]')?.value || '',
        month_from: document.querySelector('select[name="month_from"]')?.value || '',
        month_to: document.querySelector('select[name="month_to"]')?.value || '',
        entity: document.querySelector('select[name="entity"]')?.value || '',
        status: document.querySelector('select[name="status_filter"]')?.value || ''
    });

    const url = `{{ route('eqtax.dashboard.filter-selisih') }}?${params.toString()}`;

    // Tampilkan loading
    const tbody = document.querySelector('#equalization-table tbody');
    tbody.innerHTML = `
        <tr>
            <td colspan="10" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat data...</p>
            </td>
        </tr>
    `;

    // Sembunyikan pagination sementara
    document.querySelector('.d-flex.justify-content-center')?.classList.add('d-none');

    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                renderTable(result.data.data);
                renderPagination(result.data, type);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>Gagal memuat data. Silakan coba lagi.
                    </td>
                </tr>
            `;
        });
}

function renderTable(data) {
    const tbody = document.querySelector('#equalization-table tbody');

    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <i class="fas fa-inbox me-2"></i>Tidak ada data untuk filter yang dipilih.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = data.map(dt => {
        const selisihClass = dt.selisih_ppn > 0 ? 'selisih-negative' :
                            (dt.selisih_ppn < 0 ? 'selisih-positive' : 'selisih-zero');

        let statusBadge = '';
        switch(dt.status) {
            case 'MATCH':
                statusBadge = '<span class="status-match">Match</span>';
                break;
            case 'TO_BE_CHECK':
                statusBadge = '<span class="status-to-be-check">To Be Check</span>';
                break;
            case 'SPT_ONLY':
                statusBadge = '<span class="status-spt-only">SPT Only</span>';
                break;
            default:
                statusBadge = '<span class="status-gl-only">GL Only</span>';
        }

        return `
            <tr>
                <td>${dt.period}</td>
                <td class="fw-bold">${dt.no_faktur_pajak}</td>
                <td>${dt.nama_penjual}</td>
                <td class="text-end">Rp ${numberFormat(dt.dpp_spt)}</td>
                <td class="text-end">Rp ${numberFormat(dt.dpp_gl)}</td>
                <td class="text-end">Rp ${numberFormat(dt.ppn_spt)}</td>
                <td class="text-end">Rp ${numberFormat(dt.ppn_gl)}</td>
                <td class="text-end ${selisihClass}">Rp ${numberFormat(Math.abs(dt.selisih_ppn))}</td>
                <td class="text-center">${statusBadge}</td>
                <td>${dt.entity}</td>
            </tr>
        `;
    }).join('');
}

function renderPagination(paginationData, type) {
    const container = document.querySelector('.d-flex.justify-content-center');
    container.classList.remove('d-none');

    if (paginationData.last_page <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '<nav><ul class="pagination">';

    // Previous
    html += `<li class="page-item ${paginationData.current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadFilteredData('${type}', ${paginationData.current_page - 1}); return false;">&laquo;</a>
    </li>`;

    // Pages
    for (let i = 1; i <= paginationData.last_page; i++) {
        if (i === 1 || i === paginationData.last_page ||
            (i >= paginationData.current_page - 2 && i <= paginationData.current_page + 2)) {
            html += `<li class="page-item ${i === paginationData.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadFilteredData('${type}', ${i}); return false;">${i}</a>
            </li>`;
        } else if (i === paginationData.current_page - 3 || i === paginationData.current_page + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Next
    html += `<li class="page-item ${paginationData.current_page === paginationData.last_page ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadFilteredData('${type}', ${paginationData.current_page + 1}); return false;">&raquo;</a>
    </li>`;

    html += '</ul></nav>';
    container.innerHTML = html;
}

function numberFormat(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function resetWidgetFilter() {
    activeWidgetType = null;
    document.querySelectorAll('.clickable-widget').forEach(w => w.classList.remove('active'));
    document.getElementById('table-title').innerHTML = '<i class="fas fa-table me-2"></i>Data Ekualisasi Terbaru';
    document.getElementById('btn-reset-widget').classList.add('d-none');

    // Reload halaman untuk reset semua filter
    window.location.href = '{{ route("eqtax.index") }}';
}
```

---

## 5. Flowchart Implementasi

```
┌─────────────────────────────────────────────────────────┐
│                    User di Dashboard                     │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│         Widget "Selisih PPN (SPT)" / "Selisih PPN (GL)" │
│                     diklik user                          │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  JavaScript: Tangkap event click                        │
│  - Identifikasi type (kurang_bayar / lebih_bayar)       │
│  - Update UI: tambah class 'active' pada widget         │
│  - Update judul tabel                                   │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  JavaScript: Fetch AJAX ke endpoint                     │
│  GET /eqtax/dashboard/filter-selisih?type=kurang_bayar  │
│  + filter params (year, month, entity, status)          │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  DashboardController::getFilteredData()                 │
│  - Query EQTAXEqualizationResult                        │
│  - Filter: selisih_ppn > 0 (atau < 0)                  │
│  - Apply filter year, month, entity, status             │
│  - Return JSON dengan pagination                        │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  JavaScript: Render table dengan data baru              │
│  - Update tbody table                                   │
│  - Update pagination                                    │
│  - Tampilkan tombol "Tampilkan Semua"                   │
└─────────────────────────────────────────────────────────┘
```

---

## 6. API Endpoint Summary

| Method | Endpoint | Parameter | Response |
|--------|----------|-----------|----------|
| GET | `/eqtax/dashboard/filter-selisih` | `type`, `year`, `month_from`, `month_to`, `entity`, `status`, `page` | JSON `{success, data: {current_page, data[], last_page, per_page, total}}` |

---

## 7. UI/UX Behavior

### Saat Widget Diklik
1. Widget yang diklik mendapat efek visual (outline putih + scale)
2. Widget lainnya kehilangan efek aktif
3. Judul tabel berubah: "Data Ekualisasi - Selisih PPN SPT (Kurang Bayar)" atau "Data Ekualisasi - Selisih PPN GL (Lebih Bayar)"
4. Tombol "Tampilkan Semua" muncul di sebelah kanan judul
5. Tabel menampilkan loading spinner
6. Tabel ter-update dengan data yang sesuai filter
7. Pagination berfungsi (menggunakan AJAX, bukan reload halaman)

### Saat Tombol "Tampilkan Semua" Diklik
1. Widget kehilangan efek aktif
2. Judul tabel kembali ke "Data Ekualisasi Terbaru"
3. Halaman di-reload untuk menampilkan semua data

### Saat Widget Yang Sama Diklik Lagi
1. Efek toggle: membatalkan filter dan kembali ke tampilan awal (sama seperti klik "Tampilkan Semua")

---

## 8. Edge Cases & Error Handling

| Skenario | Penanganan |
|----------|------------|
| AJAX request gagal | Tampilkan pesan error di tabel: "Gagal memuat data. Silakan coba lagi." |
| Filter menghasilkan data kosong | Tampilkan pesan: "Tidak ada data untuk filter yang dipilih." |
| User mengklik filter form saat widget aktif | Filter form tetap berlaku, AJAX akan mengirim semua parameter |
| Pagination halaman yang sama diklik | Tidak perlu request ulang (handle di JS) |

---

## 9. Testing Checklist

- [ ] Klik widget "Selisih PPN (SPT)" → tabel menampilkan hanya data selisih_ppn > 0
- [ ] Klik widget "Selisih PPN (GL)" → tabel menampilkan hanya data selisih_ppn < 0
- [ ] Klik widget yang sama lagi → kembali ke tampilan semua data
- [ ] Klik tombol "Tampilkan Semua" → kembali ke tampilan semua data
- [ ] Filter tahun/bulan/entity/status berfungsi saat widget aktif
- [ ] Pagination AJAX berfungsi dengan benar
- [ ] Loading spinner muncul saat data dimuat
- [ ] Error handling berfungsi saat AJAX gagal
- [ ] Responsive di layar mobile
- [ ] Widget lain (Total SPT, Total GL) tidak memiliki interaksi klik

---

## 10. Estimasi Waktu

| Task | Estimasi |
|------|----------|
| Tambah route AJAX | 5 menit |
| Buat method getFilteredData() di Controller | 20 menit |
| Modifikasi view (HTML + CSS + JavaScript) | 45 menit |
| Testing & bug fixing | 30 menit |
| **Total** | **~100 menit (~1.5 jam)** |

---

## 11. Risk & Mitigasi

| Risk | Mitigation |
|------|------------|
| Query lambat untuk data besar | Gunakan index pada kolom `selisih_ppn` dan `period` |
| CSRF token issue pada AJAX | Gunakan meta tag csrf-token yang sudah ada di layout |
| Konflik dengan filter form yang ada | Pastikan AJAX mengirim parameter filter form juga |

---

## 12. Implementasi

### Tahap 1: Route
Tambah 1 route baru di `routes/routers/eqtax.php`

### Tahap 2: Controller
Tambah 1 method baru `getFilteredData()` di `DashboardController.php`

### Tahap 3: View
Modifikasi `dashboard/index.blade.php`:
- Tambah atribut `data-type` dan class `clickable-widget` pada 2 widget cards
- Tambah CSS untuk indikator widget aktif
- Tambah elemen judul dinamis dan tombol reset
- Tambah JavaScript: click handler, AJAX fetch, render table, render pagination, reset filter
