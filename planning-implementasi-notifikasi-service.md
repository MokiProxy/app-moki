# Planning Implementasi WhatsApp Notification Service

## 1. Ringkasan Analisis Codebase

### Framework & Tech Stack
- **Framework:** Laravel 8.75 (PHP ^7.3|^8.0)
- **Database:** PostgreSQL
- **HTTP Client:** GuzzleHttp\Client (`guzzlehttp/guzzle ^7.0.1`)
- **API Provider:** Fonnte (`https://api.fonnte.com/send`)

### Struktur Service yang Sudah Ada
- `app/Services/TicketHistoryService.php` — Plain PHP class, constructor-injected ke controller
- `app/Services/HierarchyService.php` — Plain PHP class, helper untuk hierarchy organisasi
- Pattern: **No interface/base class**, relies on Laravel auto-resolution

### Integrasi Fonnte yang Sudah Ada
- **Helper:** `app/Helpers/fonnte.php` → fungsi global `fonnte_api()` untuk ambil API key
- **Model:** `app/Models/Whatsapp.php` → tabel `whatsapp` (key-value: `key='fonnte_api'`, `value=<token>`)
- **Controller:** `TransactionController::sendWhatsappNotification()` → hardcoded kirim ke Manager MSI
- **View:** `resources/views/components/fonnte.blade.php` → halaman settings API key

### Masalah pada Implementasi Saat Ini
1. Logic pengiriman WhatsApp **tertanam langsung di controller** (`TransactionController:159-211`)
2. Tidak ada **reusable service** — setiap kali butuh kirim WA, harus copy-paste logic
3. Nomor tujuan dan pesan **hardcoded** (hanya ke Manager MSI)
4. Helper `fonnte_api()` punya **inconsistency**: controller save key `'fonnte_api_key'` tapi helper read `'fonnte_api'`

---

## 2. Goal

Membuat **`WhatsAppNotificationService`** yang:
- Bisa dipanggil dari **mana saja** (controller, event, job, dll)
- **Custom nomor tujuan** dan **custom pesan** — fleksibel
- Mengikuti **pattern service yang sudah ada** di codebase
- **Menggunakan kembali** komponen yang sudah ada (helper, model, Guzzle)
- Memudahkan penambahan notifikasi di kondisi-kondisi tertentu ke depannya

---

## 3. Rancangan Service

### 3.1. File yang Akan Dibuat
```
app/Services/WhatsAppNotificationService.php
```

### 3.2. Struktur Class

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected Client $client;
    protected ?string $token;

    public function __construct()
    {
        $this->client = new Client(['verify' => false, 'timeout' => 10]);
        $this->token = \fonnte_api();
    }

    /**
     * Kirim pesan WhatsApp via Fonnte API.
     *
     * @param string $target  Nomor HP tujuan (format 08xxx atau 62xxx)
     * @param string $message  Isi pesan (mendukung *bold*, _italic_ WhatsApp formatting)
     * @return array  ['success' => bool, 'message' => string]
     */
    public function send(string $target, string $message): array
    {
        // 1. Validasi token
        // 2. Format nomor HP (08 -> 62)
        // 3. Kirim via Guzzle ke Fonnte API
        // 4. Log hasil
        // 5. Return response
    }

    /**
     * Format nomor HP Indonesia ke format internasional.
     * 08xxx -> 62xxx
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        if (substr($phone, 0, 1) === '0') {
            return '62' . substr($phone, 1);
        }
        return $phone;
    }
}
```

### 3.3. Detail Method `send()`

| Parameter | Type | Deskripsi |
|---|---|---|
| `$target` | `string` | Nomor HP tujuan. Bisa `08xxx` atau `62xxx` — otomatis diformat |
| `$message` | `string` | Isi pesan. Mendukung WhatsApp markdown (`*bold*`, `_italic_`) |
| **Return** | `array` | `['success' => bool, 'message' => string]` |

**Alur method `send()`:**
1. Cek apakah API token tersedia → jika tidak, log error & return `['success' => false, ...]`
2. Format nomor HP via `formatPhoneNumber()`
3. POST ke Fonnte API (`https://api.fonnte.com/send`) dengan Guzzle
4. Log成功/kegagalan
5. Return hasil

### 3.4. Error Handling
- **Token tidak ada:** Log error, return false (tidak throw exception)
- **HTTP request gagal:** Try-catch Guzzle exception, log error, return false
- **Nomor invalid:** Biarkan Fonnte API yang return error, log response
- **Semua error di-log** via `Log::error()` untuk debugging

---

## 4. Cara Penggunaan (Setelah Implementasi)

### 4.1. Via Constructor Injection (Recommended untuk Controller)

```php
use App\Services\WhatsAppNotificationService;

class SomeController extends Controller
{
    protected $waService;

    public function __construct(WhatsAppNotificationService $waService)
    {
        $this->waService = $waService;
    }

    public function someAction()
    {
        $result = $this->waService->send('08123456789', 'Halo, ini pesan notifikasi');
        
        if ($result['success']) {
            // handle success
        }
    }
}
```

### 4.2. Via Helper / Static (untuk quick use)

```php
// Bisa juga dipanggil langsung tanpa injection
$waService = new \App\Services\WhatsAppNotificationService();
$waService->send('08123456789', 'Pesan notifikasi');
```

### 4.3. Contoh Penggunaan di Berbagai Kondisi

```php
// Saat tiket baru dibuat
$waService->send($teknisi->hp, "Tiket baru #{$ticket->ticket_number} perlu ditangani");

// Saat aset dikembalikan
$waService->send($manager->hp, "Aset {$asset->brand} telah dikembalikan oleh {$employee->name}");

// Custom pesan dengan formatting WhatsApp
$waService->send($target, "*JUDUL*\n---------------\nDetail pesan di sini...");
```

---

## 5. Refactor: Pindahkan Logic yang Sudah Ada

Setelah service dibuat, **`TransactionController::sendWhatsappNotification()`** (baris 159-211) harus di-refactor:

### Sebelum (hardcoded di controller):
```php
private function sendWhatsappNotification($transaction)
{
    // 50+ baris logic: ambil token, cari manager, format nomor, kirim via Guzzle
}
```

### Sesudah (panggil service):
```php
private function sendWhatsappNotification($transaction)
{
    $manager = Employee::whereRaw('LOWER(jabatan) = ?', ['manajer msi'])->first();

    if (!$manager || empty($manager->hp)) {
        Log::warning("WA Gagal: Manager MSI tidak ditemukan atau nomor HP kosong.");
        return;
    }

    $type = ($transaction->type == 'OUT') ? 'Pemberian/Pinjam' : 'Pengembalian';
    $message = "*PERMINTAAN PERSETUJUAN ASET*\n"
             . "--------------------------\n"
             . "Yth. Pak " . $manager->name . ",\n\n"
             . "Terdapat pengajuan *" . $type . "* baru:\n"
             . "No. BAST: *" . $transaction->order_number . "*\n"
             . "Karyawan: " . optional($transaction->employee)->name . "\n\n"
             . "Mohon segera login ke sistem AMS untuk memproses persetujuan.\n"
             . "Terima kasih.";

    $this->waService->send($manager->hp, $message);
}
```

Controller perlu:
1. Tambah property `$waService`
2. Inject `WhatsAppNotificationService` di constructor
3. Ganti isi method `sendWhatsappNotification()` seperti di atas

---

## 6. Rencana Implementasi (Step-by-Step)

| # | Langkah | File | Estimasi |
|---|---|---|---|
| 1 | Buat file `WhatsAppNotificationService.php` | `app/Services/WhatsAppNotificationService.php` | Baru |
| 2 | Refactor `TransactionController` — inject service & gunakan service | `app/Http/Controllers/TransactionController.php` | Edit |
| 3 | (Opsional) Fix inconsistency key API Fonnte | `app/Helpers/fonnte.php` | Edit |
| 4 | Test kirim notifikasi manual dari controller/tinker | — | Verifikasi |

---

## 7. Catatan Tambahan

### Konsistensi API Key (Issues yang Perlu Diperhatikan)
- `WhatsappController` menyimpan key sebagai `'fonnte_api_key'`
- Helper `fonnte_api()` membaca key `'fonnte_api'`
- **Perlu dipilih salah satu** dan disesuaikan agar konsisten
- Service akan menggunakan helper `fonnte_api()` yang sudah ada

### Dependensi
- **GuzzleHttp\Client** sudah terinstall (`composer.json`)
- **Helper `fonnte_api()`** sudah ter-autoload (`composer.json` → `autoload.files`)
- **Model `Whatsapp`** sudah ada
- **Tidak perlu migration baru** — tidak ada tabel baru

### Keamanan
- API token hanya diambil dari database, **tidak di-hardcode**
- Service menggunakan **try-catch** untuk semua HTTP request
- Nomor HP dan pesan di-log untuk debugging (bisa disesuaikan level log-nya)
