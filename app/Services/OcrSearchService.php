<?php

namespace App\Services;

use App\Models\DocumentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class OcrSearchService
{
    protected string $resultsPath = 'scanner/ocr-results';

    /**
     * Search for keywords in an array of data items.
     *
     * Each item should have a 'text' key containing the string to search.
     * Other keys are passed through in the result.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @param  string|array<string>  $keywords
     * @param  array<string, mixed>  $options
     */
    public function searchData(array $data, string|array $keywords, array $options = []): Collection
    {
        $keywords = array_map('strtolower', (array) $keywords);
        $contextLength = $options['context_length'] ?? 50;
        $caseSensitive = $options['case_sensitive'] ?? false;

        $results = collect();

        foreach ($data as $item) {
            $text = $item['text'] ?? '';
            $matches = $this->findMatches($text, $keywords, $contextLength, $caseSensitive);

            if ($matches->isNotEmpty()) {
                $results->push([...$item, 'matches' => $matches]);
            }
        }

        return $results;
    }

    /**
     * Search for keywords across all OCR result files on disk.
     *
     * @param  string|array<string>  $keywords
     * @param  array<string, mixed>  $options
     */
    public function search(string|array $keywords, array $options = []): Collection
    {
        $data = $this->loadAllResults();

        return $this->searchData($data, $keywords, $options);
    }

    /**
     * Search for a single keyword and return the first match.
     *
     * @return array<string, mixed>|null
     */
    public function findFirst(string $keyword, array $options = []): ?array
    {
        return $this->search($keyword, $options)->first();
    }

    /**
     * Count total matches for keywords across all items.
     *
     * @param  array<int, array<string, mixed>>|null  $data  If null, loads from disk.
     * @param  string|array<string>  $keywords
     * @param  array<string, mixed>  $options
     */
    public function countMatches(?array $data, string|array $keywords, array $options = []): int
    {
        $results = $data !== null
            ? $this->searchData($data, $keywords, $options)
            : $this->search($keywords, $options);

        return $results->sum(fn ($result) => $result['matches']->count());
    }

    /**
     * List all available OCR result files with metadata.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listResults(): Collection
    {
        $files = $this->getResultFiles();

        $results = collect();

        foreach ($files as $file) {
            $data = $this->parseResultFile($file);

            if ($data !== null) {
                $documentType = $data['document_type'] ?? null;
                $numberLabel = $this->resolveNumberLabel($data);
                $keteranganLabel = $this->resolveKeteranganLabel($data);

                $results->push([
                    'filename' => $data['filename'] ?? basename($file, '.json'),
                    'document_type' => $documentType,
                    $numberLabel => $data[$numberLabel] ?? null,
                    'vendor_name' => $data['vendor_name'] ?? null,
                    $keteranganLabel => $data[$keteranganLabel] ?? null,
                    'processed_at' => $data['processed_at'] ?? null,
                    'text_length' => strlen($data['text'] ?? ''),
                ]);
            }
        }

        return $results;
    }

    /**
     * Load all OCR result data from disk into an array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadAllResults(): array
    {
        $files = $this->getResultFiles();
        $data = [];

        foreach ($files as $file) {
            $parsed = $this->parseResultFile($file);

            if ($parsed !== null) {
                $data[] = $parsed;
            }
        }

        return $data;
    }

    /**
     * Find keyword matches in text with surrounding context.
     *
     * @param  array<string>  $keywords
     */
    protected function findMatches(string $text, array $keywords, int $contextLength, bool $caseSensitive): Collection
    {
        $matches = collect();
        $lines = explode("\n", $text);

        foreach ($lines as $lineIndex => $line) {
            $searchLine = $caseSensitive ? $line : strtolower($line);

            foreach ($keywords as $keyword) {
                $searchKeyword = $caseSensitive ? $keyword : strtolower($keyword);

                if (str_contains($searchLine, $searchKeyword)) {
                    $matches->push([
                        'keyword' => $keyword,
                        'line_number' => $lineIndex + 1,
                        'line' => trim($line),
                        'context' => $this->extractContext($text, $keyword, $contextLength, $caseSensitive),
                    ]);
                }
            }
        }

        return $matches;
    }

    /**
     * Extract surrounding context around a keyword match.
     */
    protected function extractContext(string $text, string $keyword, int $contextLength, bool $caseSensitive): string
    {
        $searchText = $caseSensitive ? $text : strtolower($text);
        $searchKeyword = $caseSensitive ? $keyword : strtolower($keyword);
        $position = strpos($searchText, $searchKeyword);

        if ($position === false) {
            return '';
        }

        $start = max(0, $position - $contextLength);
        $end = min(strlen($text), $position + strlen($keyword) + $contextLength);
        $context = substr($text, $start, $end - $start);

        if ($start > 0) {
            $context = '...'.$context;
        }

        if ($end < strlen($text)) {
            $context .= '...';
        }

        return $context;
    }

    /**
     * Get all OCR result JSON files from storage.
     *
     * @return array<string>
     */
    protected function getResultFiles(): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($this->resultsPath)) {
            return [];
        }

        return $disk->files($this->resultsPath);
    }

    /**
     * Parse a single OCR result JSON file.
     *
     * @return array<string, mixed>|null
     */
    protected function parseResultFile(string $path): ?array
    {
        $content = Storage::disk('local')->get($path);

        if ($content === null) {
            return null;
        }

        $data = json_decode($content, true);

        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    protected function resolveNumberLabel(array $data): string
    {
        $documentType = $data['document_type'] ?? null;

        if ($documentType === null) {
            return 'document_number';
        }

        $docType = DocumentType::where('name', $documentType)->first();

        return $docType?->attributes['number_label'] ?? 'document_number';
    }

    protected function resolveKeteranganLabel(array $data): string
    {
        $documentType = $data['document_type'] ?? null;

        if ($documentType === null) {
            return 'keterangan';
        }

        $docType = DocumentType::where('name', $documentType)->first();

        return $docType?->attributes['keterangan_label'] ?? 'keterangan';
    }
}
