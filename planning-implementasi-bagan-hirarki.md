# Planning Implementasi Bagan Hirarki Pegawai

## 1. Gambaran Umum

Menambahkan tombol **View** pada setiap baris DataTable Pegawai Hirarki. Saat diklik, menampilkan modal berisi **bagan struktur organisasi** (organizational chart) yang menunjukkan rantai hirarki dari employee tersebut hingga ke level teratas.

## 2. Alur Data

```
Klik tombol View
       │
       ▼
AJAX GET /pegawai-hirarki/{id}/hierarchy
       │
       ▼
Controller ambil data PegawaiHirarki + superiors chain
       │
       ▼
Return JSON: { employee: {...}, superiors: [{...}, {...}, ...] }
       │
       ▼
JavaScript render bagan organisasi ke dalam modal
```

## 3. Struktur JSON Response

```json
{
    "success": true,
    "data": {
        "employee": {
            "position_id": "POS001",
            "pos_title": "Manager IT",
            "nama": "Budi Santoso",
            "jabatan0": "Manager",
            "email": "budi@company.com",
            "kode_satker": "SK001",
            "nama_satker": "IT Department"
        },
        "superiors": [
            {
                "level": 1,
                "position_id": "POS002",
                "pos_title": "Director IT",
                "nama": "Andi Wijaya",
                "jabatan": "Director",
                "email": "andi@company.com"
            },
            {
                "level": 2,
                "position_id": "POS003",
                "pos_title": "VP Technology",
                "nama": "Siti Rahayu",
                "jabatan": "VP",
                "email": "siti@company.com"
            }
        ]
    }
}
```

## 4. File yang Perlu Dibuat/Diubah

| No | File | Aksi | Keterangan |
|----|------|------|------------|
| 1 | `app/Http/Controllers/PegawaiHirarkiController.php` | **Edit** | Tambah method `hierarchy($id)` |
| 2 | `routes/web.php` | **Edit** | Tambah 1 route GET |
| 3 | `resources/views/components/pegawai-hirarki.blade.php` | **Edit** | Tambah tombol View, modal, CSS, JS |

## 5. Detail Implementasi

### 5.1 Route (`routes/web.php`)

Tambahkan setelah route `pegawai-hirarki/datatable`:

```php
Route::get('/pegawai-hirarki/{id}/hierarchy', [PegawaiHirarkiController::class, 'hierarchy'])->name('pegawai-hirarki.hierarchy');
```

### 5.2 Controller Method `hierarchy($id)`

**Logic:**
1. Ambil record `PegawaiHirarki` berdasarkan `$id`
2. Ambil data employee itu sendiri (posisi, nama, jabatan, email, satker)
3. Iterasi `superior_1` s.d. `superior_8`, untuk setiap non-null:
   - Cari data `PegawaiMasterPosisi` (pos_title)
   - Cari data `PegawaiHirarki` yang memiliki `position_id` sama (nama, jabatan, email)
4. Return JSON dengan employee + array superiors

**Pseudo-code:**
```php
public function hierarchy($id)
{
    $hirarki = PegawaiHirarki::with('posisi', 'satker')->find($id);

    // Build employee node
    $employee = [
        'position_id' => $hirarki->position_id,
        'pos_title'   => $hirarki->posisi->pos_title ?? '-',
        'nama'        => $hirarki->nama ?? '-',
        'jabatan0'    => $hirarki->jabatan0 ?? '-',
        'email'       => $hirarki->email ?? '-',
        'kode_satker' => $hirarki->kode_satker ?? '-',
        'nama_satker' => $hirarki->satker->nama_satker ?? '-',
    ];

    // Build superiors chain
    $superiors = [];
    for ($i = 1; $i <= 8; $i++) {
        $posId = $hirarki->{"superior_{$i}"};
        if (!$posId) break;

        $posisi = PegawaiMasterPosisi::find($posId);
        $superiorHirarki = PegawaiHirarki::where('position_id', $posId)->first();

        $superiors[] = [
            'level'      => $i,
            'position_id'=> $posId,
            'pos_title'  => $posisi->pos_title ?? '-',
            'nama'       => $superiorHirarki->nama ?? '-',
            'jabatan'    => $superiorHirarki->jabatan0 ?? '-',
            'email'      => $superiorHirarki->email ?? '-',
        ];
    }

    return response()->json([
        'success' => true,
        'data'    => ['employee' => $employee, 'superiors' => $superiors]
    ]);
}
```

### 5.3 View Changes

#### A. Tombol View di DataTable Action

Tambahkan tombol View (biru) sebelum tombol Edit:

```php
->addColumn('action', function ($row) {
    return '<ul class="list-unstyled hstack gap-1 mb-0">
                <li data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                    <button class="btn btn-sm btn-soft-primary btn-view" data-id="' . $row->id . '">
                        <i class="mdi mdi-eye-outline mdi-18px"></i>
                    </button>
                </li>
                <li>... edit button ...</li>
                <li>... delete button ...</li>
            </ul>';
})
```

#### B. Modal View Hirarki

```html
<div class="modal fade" id="modal-view-hirarki" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Struktur Hirarki</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="hierarchy-chart" class="org-chart"></div>
            </div>
        </div>
    </div>
</div>
```

#### C. CSS Org Chart (Pure CSS, tanpa library tambahan)

Menggunakan pendekatan **vertical tree** dengan CSS flexbox + connector lines:

```
┌─────────────────────┐
│  VP Technology      │  ← Level 2 (paling atas)
│  Siti Rahayu        │
│  VP                 │
└─────────┬───────────┘
          │
┌─────────┴───────────┐
│  Director IT        │  ← Level 1
│  Andi Wijaya        │
│  Director           │
└─────────┬───────────┘
          │
┌─────────┴───────────┐
│  Manager IT ★       │  ← Employee (highlight)
│  Budi Santoso       │
│  Manager            │
│  IT Department      │
└─────────────────────┘
```

**CSS Strategy:**
- `.org-chart` — container flexbox column, align-items center
- `.org-node` — card-style box (border, shadow, padding, max-width)
- `.org-node.employee` — highlight berbeda (border-primary, background biru muda)
- `.org-connector` — vertical line (width 2px, height 20px, background abu-abu)
- `.org-empty` — placeholder "Tidak ada superior" jika chain kosong

#### D. JavaScript Logic

```javascript
// Event: klik tombol View
$('#hirarki-table').on('click', '.btn-view', function() {
    var id = $(this).data('id');
    $.ajax({
        type: "GET",
        url: "{{ url('pegawai-hirarki') }}/" + id + "/hierarchy",
        dataType: "JSON",
        success: function(response) {
            if (response.success) {
                renderOrgChart(response.data);
                $('#modal-view-hirarki').modal('show');
            }
        }
    });
});

// Render org chart
function renderOrgChart(data) {
    var html = '';
    var superiors = data.superiors;
    var employee = data.employee;

    // Render superiors dari atas ke bawah (level tinggi dulu)
    for (var i = superiors.length - 1; i >= 0; i--) {
        html += renderNode(superiors[i], false);
        html += '<div class="org-connector"></div>';
    }

    // Jika tidak ada superior
    if (superiors.length === 0) {
        html += '<div class="org-node org-node-empty">Top Level Position</div>';
        html += '<div class="org-connector"></div>';
    }

    // Render employee (highlight)
    html += renderNode(employee, true);

    $('#hierarchy-chart').html(html);
}

function renderNode(node, isEmployee) {
    var cls = isEmployee ? 'org-node employee' : 'org-node';
    return '<div class="' + cls + '">' +
        '<div class="org-node-position">' + (node.pos_title || '-') + '</div>' +
        '<div class="org-node-name">' + (node.nama || '-') + '</div>' +
        '<div class="org-node-jabatan">' + (node.jabatan || node.jabatan0 || '-') + '</div>' +
        (isEmployee && node.nama_satker ? '<div class="org-node-satker"><i class="bx bx-building"></i> ' + node.nama_satker + '</div>' : '') +
        '</div>';
}
```

## 6. Urutan Pengerjaan

1. Tambah **route** GET `/pegawai-hirarki/{id}/hierarchy`
2. Tambah **method** `hierarchy($id)` di controller
3. Tambah **tombol View** di action column controller
4. Tambah **modal** + **CSS org chart** + **JavaScript** di view

## 7. CSS Detail

```css
/* Org Chart Container */
.org-chart {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 0;
    overflow-x: auto;
}

/* Node Card */
.org-node {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 20px;
    min-width: 220px;
    max-width: 280px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}

/* Employee Highlight */
.org-node.employee {
    border: 2px solid #556ee6;
    background: #f0f3ff;
}

/* Node Elements */
.org-node-position {
    font-weight: 600;
    font-size: 13px;
    color: #556ee6;
    margin-bottom: 4px;
}
.org-node-name {
    font-size: 14px;
    font-weight: 500;
    color: #343a40;
}
.org-node-jabatan {
    font-size: 12px;
    color: #6c757d;
}
.org-node-satker {
    font-size: 11px;
    color: #8c8fa3;
    margin-top: 4px;
}

/* Connector Line */
.org-connector {
    width: 2px;
    height: 24px;
    background: #dee2e6;
}

/* Empty State */
.org-node-empty {
    border-style: dashed;
    color: #adb5bd;
    font-style: italic;
}
```

## 8. Catatan

- **Tanpa library tambahan** — menggunakan pure CSS dengan flexbox
- **Data superiors** diambil dari field `superior_1..8` di record `PegawaiHirarki` (sudah di-cache oleh `HierarchyService`)
- **Employee node** di-highlight dengan border biru dan background biru muda
- **Modal** menggunakan `modal-xl` dan `modal-dialog-scrollable` untuk menampung rantai hirarki yang panjang
- Jika rantai hirarki kosong (tidak ada superior), tampilkan employee node saja sebagai top-level
