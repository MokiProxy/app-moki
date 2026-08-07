<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\AuditorAccessLink;
use App\Models\DocumentMergeGroup;
use App\Models\FileValidation;
use App\Models\ScanLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuditorFileController extends Controller
{
    protected AuditorAccessLink $link;

    public function index(Request $request, string $token)
    {
        $this->link = $this->resolveLink($token);

        $path = $request->query('path', '');
        $disk = Storage::disk('ftp_final');

        if ($path !== '') {
            return $this->showFolder($disk, $path);
        }

        return $this->showRoot($disk);
    }

    public function view(Request $request, string $token)
    {
        $this->link = $this->resolveLink($token);

        [$path, $filename] = $this->resolvePdfFile($request->query('path', ''));

        if ($request->boolean('raw')) {
            return $this->streamFile($path, 'inline', [
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]);
        }

        return view('auditor.view', compact('path', 'filename', 'token'));
    }

    public function download(Request $request, string $token)
    {
        abort(403, 'Auditor tidak memiliki akses untuk mengunduh file.');
    }

    protected function resolveLink(string $token): AuditorAccessLink
    {
        $link = AuditorAccessLink::where('token', $token)->first();

        if (! $link) {
            abort(404);
        }

        if (! $link->is_active) {
            abort(403, 'Link akses ini sudah tidak aktif.');
        }

        $link->update(['last_accessed_at' => now()]);

        return $link;
    }

    protected function getAllowedFtpPaths(): array
    {
        $allowedYears = $this->link->allowed_years ?? [];

        if (empty($allowedYears)) {
            return [];
        }

        // 1. Ambil path dari scan_logs langsung
        $allLogs = ScanLog::whereNotNull('ftp_path')
            ->whereNotNull('tanggal')
            ->select('ftp_path', 'tanggal')
            ->get();

        $allowedPaths = [];

        foreach ($allLogs as $log) {
            $year = $this->extractYearFromTanggal($log->tanggal);

            if ($year !== null && in_array($year, $allowedYears)) {
                $allowedPaths[] = $log->ftp_path;
            }
        }

        // 2. Ambil final_pdf_path dari document_merge_groups yang sudah selesai
        //    File FINAL tidak punya scan_logs sendiri, tapi item-nya punya scan_log dengan tanggal
        $mergeGroups = DocumentMergeGroup::where('status', 2)
            ->whereNotNull('final_pdf_path')
            ->with('items.scanLog')
            ->get();

        foreach ($mergeGroups as $group) {
            foreach ($group->items as $item) {
                if ($item->scanLog && $item->scanLog->tanggal) {
                    $year = $this->extractYearFromTanggal($item->scanLog->tanggal);

                    if ($year !== null && in_array($year, $allowedYears)) {
                        $allowedPaths[] = $group->final_pdf_path;
                        break; // Satu item cocok sudah cukup untuk group ini
                    }
                }
            }
        }

        return array_unique($allowedPaths);
    }

    protected function extractYearFromTanggal(?string $tanggal): ?int
    {
        if (! $tanggal || trim($tanggal) === '') {
            return null;
        }

        $tanggal = trim($tanggal);
        $yearSuffix = substr($tanggal, -2);

        if (! is_numeric($yearSuffix)) {
            return null;
        }

        return 2000 + (int) $yearSuffix;
    }

    protected function showRoot($disk)
    {
        $directories = $this->getDirectories($disk);
        $link = $this->link;

        return view('auditor.index', compact('directories', 'link'));
    }

    protected function showFolder($disk, string $path)
    {
        $breadcrumbs = $this->buildBreadcrumbs($path);
        $directories = $this->getSubDirectories($disk, $path);
        $files = $this->getFiles($disk, $path);

        $allowedPaths = $this->getAllowedFtpPaths();

        $files = array_filter($files, function ($file) use ($allowedPaths) {
            return in_array($file['path'], $allowedPaths);
        });
        $files = array_values($files);

        $validatedFiles = FileValidation::whereIn('file_path', collect($files)->pluck('path')->toArray())
            ->get()
            ->keyBy('file_path');

        foreach ($files as &$file) {
            $validation = $validatedFiles->get($file['path']);
            $file['is_validated'] = $validation?->is_validated ?? false;
            $file['validated_by'] = $validation?->validated_by ?? null;
            $file['validated_at'] = $validation?->validated_at ?? null;
        }

        $link = $this->link;

        return view('auditor.index', compact('breadcrumbs', 'directories', 'files', 'path', 'link'));
    }

    protected function resolvePdfFile(string $path): array
    {
        [$path, $filename] = $this->resolveStorageFile($path);

        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf') {
            abort(404);
        }

        return [$path, $filename];
    }

    protected function resolveStorageFile(string $path): array
    {
        if ($path === '') {
            abort(404);
        }

        $disk = Storage::disk('ftp_final');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $parentDir = dirname($path);
        if ($parentDir === '.' || $parentDir === '\\') {
            $parentDir = '';
        }

        $files = $disk->files($parentDir);

        if (! in_array($path, $files)) {
            abort(404);
        }

        return [$path, basename($path)];
    }

    protected function streamFile(string $path, string $disposition, array $headers = [])
    {
        $disk = Storage::disk('ftp_final');
        $filename = str_replace('"', '', basename($path));
        $contents = $disk->get($path);

        if ($contents === false || $contents === null) {
            abort(404);
        }

        return response($contents, 200, array_merge([
            'Content-Type' => $disk->mimeType($path) ?: 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ], $headers));
    }

    protected function getDirectories($disk): array
    {
        $directories = [];

        try {
            $dirs = $disk->directories();
        } catch (\Exception $e) {
            Log::error('Failed to list FTP directories', ['error' => $e->getMessage()]);
            return [];
        }

        foreach ($dirs as $dir) {
            $name = basename($dir);
            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }
            try {
                $fileCount = count($disk->files($dir));
            } catch (\Exception $e) {
                $fileCount = 0;
            }
            $directories[] = [
                'path' => $dir,
                'name' => $name,
                'file_count' => $fileCount,
            ];
        }

        usort($directories, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $directories;
    }

    protected function getSubDirectories($disk, string $parentPath): array
    {
        $directories = [];

        try {
            $dirs = $disk->directories($parentPath);
        } catch (\Exception $e) {
            return [];
        }

        foreach ($dirs as $dir) {
            $name = basename($dir);
            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }
            $directories[] = [
                'path' => $dir,
                'name' => $name,
            ];
        }

        usort($directories, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $directories;
    }

    protected function getFiles($disk, string $dirPath): array
    {
        $files = [];

        try {
            $fileList = $disk->files($dirPath);
        } catch (\Exception $e) {
            return [];
        }

        foreach ($fileList as $filePath) {
            $name = basename($filePath);
            try {
                $size = $disk->size($filePath);
            } catch (\Exception $e) {
                $size = 0;
            }
            $files[] = [
                'name' => $name,
                'path' => $filePath,
                'size' => $size,
                'extension' => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
            ];
        }

        usort($files, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $files;
    }

    protected function buildBreadcrumbs(string $path): array
    {
        $parts = explode('/', $path);
        $breadcrumbs = [];
        $current = '';

        foreach ($parts as $part) {
            $current = $current === '' ? $part : $current.'/'.$part;
            $breadcrumbs[] = [
                'label' => $part,
                'path' => $current,
            ];
        }

        return $breadcrumbs;
    }
}
