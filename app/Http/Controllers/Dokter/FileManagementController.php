<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileManagementController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'File Management';
        $path = $request->query('path', '');
        $disk = Storage::disk('ftp_final');

        if ($path !== '') {
            return $this->showFolder($disk, $path, $pageName);
        }

        return $this->showRoot($disk, $pageName);
    }

    protected function showRoot($disk, string $pageName)
    {
        $directories = $this->getDirectories($disk);

        return view('dokter.file-managements.index', compact('pageName', 'directories'));
    }

    protected function showFolder($disk, string $path, string $pageName)
    {
        $breadcrumbs = $this->buildBreadcrumbs($path);
        $directories = $this->getSubDirectories($disk, $path);
        $files = $this->getFiles($disk, $path);

        return view('dokter.file-managements.index', compact('pageName', 'breadcrumbs', 'directories', 'files', 'path'));
    }

    public function view(Request $request)
    {
        [$path, $filename] = $this->resolvePdfFile($request->query('path', ''));

        if ($request->boolean('raw')) {
            return $this->streamFile($path, 'inline', [
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]);
        }

        return view('dokter.file-managements.view', compact('path', 'filename'));
    }

    public function download(Request $request)
    {
        [$path] = $this->resolveStorageFile($request->query('path', ''));

        return $this->streamFile($path, 'attachment');
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolvePdfFile(string $path): array
    {
        [$path, $filename] = $this->resolveStorageFile($path);

        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf') {
            abort(404);
        }

        return [$path, $filename];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveStorageFile(string $path): array
    {
        if ($path === '') {
            abort(404);
        }

        $disk = Storage::disk('ftp_final');

        if (! $disk->exists($path) || $disk->directoryExists($path)) {
            abort(404);
        }

        return [$path, basename($path)];
    }

    protected function streamFile(string $path, string $disposition, array $headers = [])
    {
        $disk = Storage::disk('ftp_final');
        $filename = str_replace('"', '', basename($path));
        $stream = $disk->readStream($path);

        if ($stream === false) {
            abort(404);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, array_merge([
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ], $headers));
    }

    /**
     * @return array<int, array{path: string, name: string, file_count: int}>
     */
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

    /**
     * @return array<int, array{path: string, name: string}>
     */
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

    /**
     * @return array<int, array{name: string, path: string, size: int, extension: string}>
     */
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

    /**
     * @return array<int, array{label: string, path: string}>
     */
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
