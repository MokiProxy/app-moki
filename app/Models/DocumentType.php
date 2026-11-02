<?php

namespace App\Models;

use App\Support\FileNameSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Override;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'filename_template',
        'ftp_folder_template',
        'ftp_failed_folder',
        'vendor_search_enabled',
    ];

    protected $casts = [
        'vendor_search_enabled' => 'boolean',
    ];

    #[Override]
    protected static function booted()
    {
        static::creating(function ($documentType) {
            $documentType->slug = Str::slug($documentType->name);
        });
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class);
    }

    public function resolveFtpFolder(?string $vendorName, ?string $number, ?string $filename): string
    {
        $template = $this->attributes['ftp_folder_template'] ?? '{document_type}/{vendor}';
        $replacements = [
            '{document_type}' => FileNameSanitizer::sanitize(strtoupper($this->name)),
            '{vendor}' => strtoupper(FileNameSanitizer::sanitize($vendorName)),
            '{number}' => FileNameSanitizer::sanitize($number),
            '{filename}' => FileNameSanitizer::sanitize($filename),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function resolveFilename(?string $vendorName, ?string $number, string $ext, ?string $noPo = null): string
    {
        $template = $this->attributes['filename_template'] ?? '{vendor}_{number}.{ext}';
        $safeExt = match (strtolower($ext)) {
            'png', 'webp' => 'jpg',
            'pdf' => 'pdf',
            default => $ext,
        };
        $safeNoPo = FileNameSanitizer::sanitize($noPo);
        $replacements = [
            '{vendor}' => strtoupper(FileNameSanitizer::sanitize($vendorName)),
            '{number}' => FileNameSanitizer::sanitize($number),
            '{no_po}' => $safeNoPo,
            '{ext}' => $safeExt,
            '{filename}' => '',
        ];

        $filename = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Template lama belum memuat {no_po}. Tambahkan no PO secara otomatis
        // sebelum ekstensi agar nama file di FTP final tetap membawa no PO.
        if ($safeNoPo !== '' && !str_contains($template, '{no_po}')) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $basename = $extension !== ''
                ? substr($filename, 0, -(strlen($extension) + 1))
                : $filename;

            $filename = ($basename !== '' ? "{$basename}_" : '') . $safeNoPo
                . ($extension !== '' ? ".{$extension}" : '');
        }

        // Bersihkan pemisah ganda/trailing yang tersisa saat placeholder no PO
        // kosong, mis. template "{vendor}_{number}_{no_po}.{ext}".
        if ($safeNoPo === '' && str_contains($template, '{no_po}')) {
            $filename = preg_replace('/_{2,}/', '_', $filename) ?? $filename;
            $filename = preg_replace('/_(?=\.[^.]+$)/', '', $filename) ?? $filename;
        }

        return $filename;
    }
}
