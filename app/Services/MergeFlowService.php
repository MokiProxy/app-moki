<?php

namespace App\Services;

use App\Models\DocumentMergeGroup;
use App\Models\DocumentMergeGroupItem;
use App\Models\MergeFlow;
use App\Models\MergeFlowStep;
use App\Models\ScanLog;
use App\Support\FileNameSanitizer;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MergeFlowService
{
    public function processAfterUpload(ScanLog $scanLog): void
    {
        if (!config('services.merge_flow_enabled', true)) {
            return;
        }

        $documentType = $scanLog->documentType;
        if (!$documentType) {
            return;
        }

        $step = MergeFlowStep::query()
            ->where('document_type_id', $documentType->id)
            ->whereHas('mergeFlow', fn($query) => $query->where('is_active', true))
            ->with('mergeFlow')
            ->first();

        if (!$step) {
            return;
        }

        $vendorName = InvoiceNumberNormalizer::normalizeVendor($scanLog->vendor_name);
        $documentNumber = trim((string) $scanLog->document_number);

        if ($vendorName === '' || $documentNumber === '') {
            Log::warning('MergeFlow: vendor_name or document_number is null', [
                'scan_log_id' => $scanLog->id,
            ]);

            return;
        }

        if (!$scanLog->ftp_path) {
            Log::warning('MergeFlow: ftp_path is null', [
                'scan_log_id' => $scanLog->id,
            ]);

            return;
        }

        $groups = $step->order === 1
            ? $this->handleRootDocument($step, $scanLog, $vendorName, $documentNumber)
            : $this->handleChildDocument($step, $scanLog, $scanLog->ocr_text ?? '', $vendorName, $documentNumber);

        // File I/O (download/merge/upload FTP) harus berjalan setelah semua
        // transaksi attachment selesai agar lock DB tidak ditahan lama.
        foreach ($this->uniqueGroups($groups) as $group) {
            $this->checkAndTriggerMerge($group, $step->mergeFlow);
        }
    }

    /**
     * @return list<DocumentMergeGroup>
     */
    protected function handleRootDocument(
        MergeFlowStep $step,
        ScanLog $scanLog,
        string $vendorName,
        string $documentNumber
    ): array {
        $groupKey = InvoiceNumberNormalizer::normalizeGroupKey($documentNumber);

        if ($groupKey === '') {
            return [];
        }

        $group = DB::transaction(function () use ($step, $scanLog, $vendorName, $groupKey) {
            $group = $this->findOrCreateGroup($step->merge_flow_id, $vendorName, $groupKey);

            // Nomor invoice adalah root/group key, bukan nomor dokumen asli
            // bila root bukan invoice (fase BA nanti).
            $this->upsertItem($group, $step, $scanLog, $groupKey);

            return $group->fresh();
        });

        return [$group];
    }

    /**
     * @return list<DocumentMergeGroup>
     */
    protected function handleChildDocument(
        MergeFlowStep $step,
        ScanLog $scanLog,
        string $ocrText,
        string $vendorName,
        string $documentNumber
    ): array {
        $linkedNumbers = $this->extractLinkedNumbers($step, $scanLog, $ocrText);

        if ($linkedNumbers === []) {
            Log::warning('Could not extract linked numbers from child document', [
                'scan_log_id' => $scanLog->id,
                'link_field' => $step->link_field,
                'link_regex' => $step->link_regex,
            ]);

            return [];
        }

        $label = $step->link_label ?? 'linked_number';
        $linked = $scanLog->linked_numbers ?? [];
        $linked[$label] = count($linkedNumbers) === 1
            ? $linkedNumbers[0]
            : $linkedNumbers;
        $scanLog->update(['linked_numbers' => $linked]);

        $parentStep = MergeFlowStep::where('merge_flow_id', $step->merge_flow_id)
            ->where('order', $step->order - 1)
            ->first();

        if (!$parentStep) {
            Log::warning('Parent step not found', ['step_id' => $step->id]);

            return [];
        }

        $groups = [];

        foreach ($linkedNumbers as $linkedNumber) {
            $groupKey = InvoiceNumberNormalizer::normalizeGroupKey($linkedNumber);

            if ($groupKey === '') {
                continue;
            }

            $groups[] = DB::transaction(function () use ($step, $scanLog, $vendorName, $documentNumber, $groupKey, $parentStep) {
                $group = $this->findGroupForChild(
                    $step->merge_flow_id,
                    $vendorName,
                    $groupKey,
                    $parentStep->order
                );

                if (!$group) {
                    $group = $this->findOrCreateGroup(
                        $step->merge_flow_id,
                        $vendorName,
                        $groupKey
                    );
                }

                $this->upsertItem($group, $step, $scanLog, $documentNumber);

                return $group->fresh();
            });
        }

        return $groups;
    }

    protected function findOrCreateGroup(
        int $mergeFlowId,
        string $vendorName,
        string $groupKey
    ): DocumentMergeGroup {
        $group = DocumentMergeGroup::where('merge_flow_id', $mergeFlowId)
            ->where('vendor_name', $vendorName)
            ->where('root_document_number', $groupKey)
            ->lockForUpdate()
            ->first();

        if ($group) {
            return $group;
        }

        return DocumentMergeGroup::create([
            'merge_flow_id' => $mergeFlowId,
            'vendor_name' => $vendorName,
            'root_document_number' => $groupKey,
            'status' => 0,
        ]);
    }

    protected function findGroupForChild(
        int $mergeFlowId,
        string $vendorName,
        string $groupKey,
        int $parentOrder
    ): ?DocumentMergeGroup {
        return DocumentMergeGroup::where('merge_flow_id', $mergeFlowId)
            ->where('vendor_name', $vendorName)
            ->where(function ($query) use ($groupKey, $parentOrder) {
                $query->where('root_document_number', $groupKey)
                    ->orWhereHas('items', function ($itemQuery) use ($groupKey, $parentOrder) {
                        $itemQuery->where('document_number', $groupKey)
                            ->where('order', $parentOrder);
                    });
            })
            ->lockForUpdate()
            ->first();
    }

    protected function upsertItem(
        DocumentMergeGroup $group,
        MergeFlowStep $step,
        ScanLog $scanLog,
        string $documentNumber
    ): void {
        $existing = DocumentMergeGroupItem::where('merge_group_id', $group->id)
            ->where('document_type_id', $scanLog->document_type_id)
            ->first();

        // Jangan ubah final PDF yang sudah selesai hanya karena dokumen
        // yang sama di-scan ulang.
        if ((int) $group->status === 2 && $existing) {
            Log::info('MergeFlow: duplicate document ignored for completed group', [
                'group_id' => $group->id,
                'scan_log_id' => $scanLog->id,
                'document_type_id' => $scanLog->document_type_id,
            ]);

            return;
        }

        DocumentMergeGroupItem::updateOrCreate(
            [
                'merge_group_id' => $group->id,
                'document_type_id' => $scanLog->document_type_id,
            ],
            [
                'scan_log_id' => $scanLog->id,
                'document_number' => $documentNumber,
                'order' => $step->order,
                'ftp_path' => $scanLog->ftp_path,
            ]
        );
    }

    /**
     * @return list<string>
     */
    protected function extractLinkedNumbers(
        MergeFlowStep $step,
        ScanLog $scanLog,
        string $ocrText
    ): array {
        $metadata = is_array($scanLog->metadata) ? $scanLog->metadata : [];
        $ocrData = is_array($metadata['ocr_data'] ?? null)
            ? $metadata['ocr_data']
            : [];

        if ($step->link_field && array_key_exists($step->link_field, $ocrData)) {
            $numbers = InvoiceNumberNormalizer::normalize($ocrData[$step->link_field]);

            if ($numbers !== []) {
                return $numbers;
            }
        }

        if ($step->link_label) {
            $linkedNumbers = is_array($scanLog->linked_numbers) ? $scanLog->linked_numbers : [];
            $numbers = InvoiceNumberNormalizer::normalize(
                $linkedNumbers[$step->link_label] ?? null
            );

            if ($numbers !== []) {
                return $numbers;
            }
        }

        if ($step->link_regex) {
            $matches = [];
            @preg_match_all($step->link_regex, $ocrText, $matches);

            $captures = $matches[1] ?? $matches[0] ?? [];

            return InvoiceNumberNormalizer::normalize($captures);
        }

        return [];
    }

    protected function checkAndTriggerMerge(DocumentMergeGroup $group, MergeFlow $flow): void
    {
        if ((int) $group->status === 2) {
            return;
        }

        $totalSteps = $flow->steps()->count();
        if ($totalSteps < 1) {
            return;
        }

        $presentOrders = $group->items()
            ->distinct()
            ->count('order');

        if ($presentOrders >= $totalSteps) {
            $group->update(['status' => 1]);
            $this->performFinalMerge($group, $flow);
        }
    }

    protected function performFinalMerge(DocumentMergeGroup $group, MergeFlow $flow): void
    {
        // TTL lock sedikit di atas timeout job (120 detik), agar retry tidak
        // tertahan lama bila worker mati di tengah merge.
        $lock = Cache::lock("merge-flow-group:{$group->id}", 180);

        if (!$lock->get()) {
            Log::info('MergeFlow: merge already in progress', ['group_id' => $group->id]);

            return;
        }

        $ftpDisk = Storage::disk('ftp_final');
        $tempFiles = [];
        $mergedPath = null;

        try {
            $group->refresh();

            if ((int) $group->status === 2) {
                return;
            }

            $group->update(['status' => 1]);

            $items = $group->items()
                ->with(['scanLog', 'documentType'])
                ->orderBy('order')
                ->get();

            if ($items->count() < $flow->steps()->count()) {
                throw new Exception('Group is not complete yet');
            }

            // Urutan halaman final: step terakhir di depan.
            $mergeItems = $items->sortByDesc('order')->values();

            foreach ($mergeItems as $item) {
                if (!$item->ftp_path || !$ftpDisk->exists($item->ftp_path)) {
                    throw new Exception("File not found on FTP: {$item->ftp_path}");
                }

                $content = $ftpDisk->get($item->ftp_path);
                if ($content === null) {
                    throw new Exception("Failed to read FTP file: {$item->ftp_path}");
                }

                $tempFile = storage_path(
                    "app/private/scanner/temp/merge_flow_{$group->id}_{$item->order}_" . uniqid() . '.pdf'
                );
                $this->ensureDirectoryExists(dirname($tempFile));
                file_put_contents($tempFile, $content);
                $tempFiles[] = $tempFile;
            }

            $numbers = FileNameSanitizer::sanitize(
                $items->pluck('document_number')->implode('_'),
                (string) $group->root_document_number
            );
            $vendorFolder = FileNameSanitizer::sanitize($group->vendor_name, 'TANPA_VENDOR');
            $finalFilename = FileNameSanitizer::sanitize(
                "FINAL_{$vendorFolder}_{$numbers}.pdf",
                "FINAL_{$group->id}.pdf"
            );

            $mergedDir = storage_path('app/private/scanner/merged_flow');
            $this->ensureDirectoryExists($mergedDir);
            $mergedPath = $mergedDir . '/' . uniqid('merge_', true) . '_' . $finalFilename;

            $merger = app(PdfMergeService::class);
            $merger->mergePdfs($tempFiles, $mergedPath);
            $pageCount = $merger->getPageCount($mergedPath);

            $mergedContent = file_get_contents($mergedPath);
            if ($mergedContent === false) {
                throw new Exception('Failed to read merged PDF output');
            }

            $finalPath = "FINAL/{$vendorFolder}/{$finalFilename}";

            if (!$ftpDisk->put($finalPath, $mergedContent)) {
                throw new Exception("Failed to upload final PDF: {$finalPath}");
            }

            $group->update([
                'status' => 2,
                'final_pdf_path' => $finalPath,
                'merged_at' => now(),
            ]);

            $firstItem = $items->first();
            $firstScanLog = $firstItem?->scanLog;
            $approvalItem = $items->first(fn(DocumentMergeGroupItem $item) => str_contains(
                strtoupper((string) ($item->documentType?->name ?? '')),
                'PERSETUJUAN'
            ));
            $pageOrder = $mergeItems
                ->map(fn(DocumentMergeGroupItem $item) => $item->documentType?->name ?? 'UNKNOWN')
                ->values()
                ->all();

            try {
                ScanLog::create([
                    'source' => 'merge_flow',
                    'event' => 'merge_final',
                    'status' => 'success',
                    'merge_status' => 'merged',
                    'filename' => $finalFilename,
                    'extension' => 'pdf',
                    'document_type_id' => $firstItem?->document_type_id,
                    'document_type_name' => 'FINAL',
                    'document_number' => $group->root_document_number,
                    'tanggal' => $firstScanLog?->tanggal ?? now()->format('d M y'),
                    'vendor_name' => $group->vendor_name,
                    'keterangan' => "Merge final dari {$items->count()} dokumen",
                    'ftp_path' => $finalPath,
                    'file_size' => $ftpDisk->size($finalPath) ?: 0,
                    'total_pages' => $pageCount,
                    'metadata' => [
                        'merge_group_id' => $group->id,
                        'merge_flow_id' => $group->merge_flow_id,
                        'group_key' => $group->root_document_number,
                        'approval_document_number' => $approvalItem?->document_number,
                        'item_count' => $items->count(),
                        'item_ids' => $items->pluck('id')->toArray(),
                        'page_order' => $pageOrder,
                        'total_pages' => $pageCount,
                    ],
                ]);
            } catch (Throwable $e) {
                // Final PDF sudah berhasil; kegagalan audit log tidak boleh
                // membuat group dianggap gagal merge.
                Log::error('Failed to create final merge scan log', [
                    'group_id' => $group->id,
                    'final_path' => $finalPath,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Final merge completed', [
                'group_id' => $group->id,
                'final_path' => $finalPath,
                'items' => $items->pluck('document_number')->all(),
                'page_order' => $pageOrder,
                'total_pages' => $pageCount,
            ]);
        } catch (Throwable $e) {
            $group->update(['status' => 3]);

            Log::error('Final merge failed', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            foreach ($tempFiles as $file) {
                @unlink($file);
            }

            if ($mergedPath) {
                @unlink($mergedPath);
            }

            $lock->release();
        }
    }

    /**
     * @param  list<DocumentMergeGroup>  $groups
     * @return list<DocumentMergeGroup>
     */
    protected function uniqueGroups(array $groups): array
    {
        $unique = [];

        foreach ($groups as $group) {
            $unique[$group->id] = $group;
        }

        return array_values($unique);
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
