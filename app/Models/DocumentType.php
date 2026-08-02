<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Override;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'number_regex',
        'number_label',
        'keterangan_regex',
        'keterangan_label',
        'keterangan_enabled',
        'filename_template',
        'ftp_folder_template',
        'ftp_failed_folder',
        'vendor_search_enabled',
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
            '{document_type}' => strtoupper($this->name),
            '{vendor}' => strtoupper($vendorName ?? ''),
            '{number}' => $number ?? '',
            '{filename}' => $filename ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function resolveFilename(?string $vendorName, ?string $number, string $ext): string
    {
        $template = $this->attributes['filename_template'] ?? '{vendor}_{number}.{ext}';
        $safeExt = match (strtolower($ext)) {
            'png', 'webp' => 'jpg',
            'pdf' => 'pdf',
            default => $ext,
        };
        $replacements = [
            '{vendor}' => strtoupper($vendorName ?? ''),
            '{number}' => $number ?? '',
            '{ext}' => $safeExt,
            '{filename}' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
