# Planning: Pendeteksian Jenis Dokumen via Gemini API

## Ringkasan

Menghapus mekanisme pendeteksian jenis dokumen menggunakan regex (`header_regex`) dan menggantinya dengan mengambil `document_type` langsung dari structured response Gemini API.

## Alur Sebelum vs Sesudah

### Sebelum (2x Panggilan Gemini + Regex)
```
File masuk
    │
    ▼
┌────────────────────────────────────────┐
│ STEP 1: Deteksi (MonitorScanner)       │
│ - Panggil Gemini (tanpa prompt)        │
│ - Dapat OCR text                       │
│ - Match dengan header_regex (REGEX)    │
└────────────┬───────────────────────────┘
             │
             ▼
┌────────────────────────────────────────┐
│ STEP 2: Ekstraksi (ProcessScanFile)    │
│ - Panggil Gemini LAGI (dengan prompt)  │
│ - Dapat structured JSON                │
│ - document_type bisa override          │
└────────────────────────────────────────┘
```

### Sesudah (1x Panggilan Gemini)
```
File masuk
    │
    ▶
┌────────────────────────────────────────┐
│ Panggil Gemini (1x saja)               │
│ - Dapat structured JSON:               │
│   {                                    │
│     "document_type": "INVOICE",        │
│     "document_number": "...",          │
│     ...                                │
│   }                                    │
│ - Match document_type ke database      │
│ → Selesai                              │
└────────────────────────────────────────┘
```

## File yang Diubah

| # | File | Perubahan |
|---|------|-----------|
| 1 | `app/Console/Commands/Dokter/MonitorScanner.php` | Hapus `matchHeader()`, deteksi via `document_type` dari Gemini |
| 2 | `app/Jobs/ProcessScanFile.php` | Hapus `matchHeader()`, deteksi via `document_type` dari Gemini |
| 3 | `app/Models/DocumentType.php` | Hapus `header_regex` dari fillable |
| 4 | `app/Http/Requests/StoreDocumentTypeRequest.php` | Hapus validasi `header_regex` |
| 5 | `app/Http/Requests/UpdateDocumentTypeRequest.php` | Hapus validasi `header_regex` |
| 6 | `resources/views/dokter/document-type/create.blade.php` | Hapus regex builder untuk header_regex |
| 7 | `resources/views/dokter/document-type/edit.blade.php` | Hapus regex builder untuk header_regex |
| 8 | `database/seeders/DocumentTypeSeeder.php` | Hapus `header_regex` dari seed data |
| 9 | Migration baru | Drop column `header_regex` |

## Implementasi

### 1. MonitorScanner

**Sebelum:**
```php
protected function detectDocumentType(...): ?DocumentType
{
    $result = $ocr->extractText($uploadedFile);
    $ocrText = $result['text'] ?? '';
    
    foreach ($documentTypes as $docType) {
        if ($this->matchHeader($docType, $ocrText)) {
            return $docType;
        }
    }
    return null;
}

protected function matchHeader(DocumentType $docType, string $ocrText): bool
{
    $pattern = $docType->header_regex ?? null;
    $result = @preg_match($pattern, $ocrText);
    return $result === 1;
}
```

**Sesudah:**
```php
protected function detectDocumentType(...): ?DocumentType
{
    $result = $ocr->extractText($uploadedFile);
    $ocrData = $result['ocr_data'] ?? null;
    
    if ($ocrData === null) {
        return null;
    }
    
    $documentTypeName = strtoupper($ocrData['document_type'] ?? '');
    
    if ($documentTypeName === '') {
        return null;
    }
    
    return DocumentType::whereRaw('UPPER(name) = ?', [$documentTypeName])->first();
}
```

### 2. ProcessScanFile

**Sebelum:**
```php
protected function resolveDocumentType(...): ?DocumentType
{
    if ($this->documentTypeId !== null) {
        return DocumentType::find($this->documentTypeId);
    }
    
    $result = $ocr->extractText($uploadedFile);
    $ocrText = $result['text'] ?? '';
    
    foreach ($documentTypes as $docType) {
        if ($this->matchHeader($docType, $ocrText)) {
            return $docType;
        }
    }
    return null;
}
```

**Sesudah:**
```php
protected function resolveDocumentType(...): ?DocumentType
{
    if ($this->documentTypeId !== null) {
        return DocumentType::find($this->documentTypeId);
    }
    
    $result = $ocr->extractText($uploadedFile);
    $ocrData = $result['ocr_data'] ?? null;
    
    if ($ocrData === null) {
        return null;
    }
    
    $documentTypeName = strtoupper($ocrData['document_type'] ?? '');
    
    if ($documentTypeName === '') {
        return null;
    }
    
    return DocumentType::whereRaw('UPPER(name) = ?', [$documentTypeName])->first();
}
```

### 3. Database Migration

```php
Schema::table('document_types', function (Blueprint $table) {
    $table->dropColumn('header_regex');
});
```

### 4. DocumentType Model

```php
protected $fillable = [
    'name',
    // 'header_regex',  // DIHAPUS
    'description',
    'gemini_prompt',
    'filename_template',
    'ftp_folder_template',
    'ftp_failed_folder',
    'vendor_search_enabled',
];
```

### 5. Form Requests

```php
// StoreDocumentTypeRequest
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        // 'header_regex' => ['nullable', 'string', 'max:255', new ValidRegex],  // DIHAPUS
        'description' => ['nullable', 'string', 'max:1000'],
        'gemini_prompt' => ['nullable', 'string', 'max:5000'],
        'filename_template' => ['nullable', 'string', 'max:255'],
        'ftp_folder_template' => ['nullable', 'string', 'max:255'],
        'ftp_failed_folder' => ['nullable', 'string', 'max:255'],
        'vendor_search_enabled' => ['nullable', 'boolean'],
    ];
}
```

### 6. Views

Hapus field header_regex dan regex-builder.js dari create dan edit views.

### 7. Seeder

```php
$documentTypes = [
    [
        'name' => 'SLIP PEMBUKUAN AP',
        'slug' => 'slip-pembukuan-ap',
        // 'header_regex' => '/^SLIP\s+PEMBUKUAN\s+AP$/mi',  // DIHAPUS
        'description' => 'Slip Pembukuan AP',
        // ...
    ],
    // ...
];
```

## Testing Checklist

- [ ] MonitorScanner dapat detect jenis dokumen dari Gemini response
- [ ] ProcessScanFile dapat resolve jenis dokumen dari Gemini response
- [ ] Jika Gemini return document_type yang tidak ada di database, file masuk FAILED
- [ ] Form create/edit jenis dokumen tidak ada field header_regex
- [ ] Seeder berjalan tanpa error
- [ ] Migration berjalan tanpa error
