<?php

namespace App\Services;

use App\Models\DocumentType;

class PromptBuilder
{
    /**
     * Build prompt lengkap berdasarkan document type.
     *
     * Prompt template diambil dari config, field JSON diambil dari
     * gemini_fields pada document type. Jika tidak ada custom fields,
     * gunakan default fields dari config.
     */
    public static function buildPrompt(?DocumentType $documentType): string
    {
        $template = config('services.gemini.prompt_template');

        $fields = $documentType->gemini_fields ?? config('services.gemini.default_fields');

        $fieldsJson = self::formatFieldsAsJson($fields);

        return $template."\n".$fieldsJson;
    }

    /**
     * Format fields array menjadi JSON string untuk prompt.
     *
     * Mengubah array field menjadi object JSON dengan value kosong,
     * sehingga Gemini mengerti format response yang diharapkan.
     */
    private static function formatFieldsAsJson(array $fields): string
    {
        $formatted = [];
        foreach ($fields as $field) {
            $formatted[$field] = '';
        }

        return json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Mendapatkan semua field yang diizinkan untuk document type.
     */
    public static function getAllowedFields(?DocumentType $documentType): array
    {
        return $documentType->gemini_fields ?? config('services.gemini.default_fields');
    }
}
