<?php

namespace App\Services;

class PromptBuilder
{
    /**
     * Build prompt dengan JSON template untuk Gemini.
     *
     * Menggunakan default fields dari config sebagai struktur JSON
     * yang diharapkan dari response Gemini.
     */
    public static function buildPrompt(): string
    {
        $template = config('services.gemini.prompt_template');
        $fields = config('services.gemini.default_fields');
        $fieldsJson = self::formatFieldsAsJson($fields);

        return $template;
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
     * Mendapatkan semua field yang diizinkan (default fields dari config).
     */
    public static function getAllowedFields(): array
    {
        return config('services.gemini.default_fields');
    }
}
