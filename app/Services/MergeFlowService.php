<?php

namespace App\Services;

use App\Models\DocumentMergeGroup;
use App\Models\DocumentMergeGroupItem;
use App\Models\MergeFlow;
use App\Models\MergeFlowStep;
use App\Models\ScanLog;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MergeFlowService
{
    public function processAfterUpload(ScanLog $scanLog): void
    {
        if (! config('services.merge_flow_enabled', true)) {
            return;
        }

        $documentType = $scanLog->documentType;
        if (! $documentType) {
            return;
        }

        $step = MergeFlowStep::where('document_type_id', $documentType->id)
            ->with('mergeFlow')
            ->first();

        if (! $step || ! $step->mergeFlow->is_active) {
            return;
        }

        $vendorName = $scanLog->vendor_name;
        $documentNumber = $scanLog->document_number;

        if (! $vendorName || ! $documentNumber) {
            Log::warning('MergeFlow: vendor_name or document_number is null', [
                'scan_log_id' => $scanLog->id,
            ]);

            return;
        }

        if ($step->order === 1) {
            $this->handleRootDocument($step, $scanLog, $vendorName, $documentNumber);
        } else {
            $ocrText = $scanLog->ocr_text ?? '';
            $this->handleChildDocument($step, $scanLog, $ocrText, $vendorName, $documentNumber);
        }
    }

    protected function handleRootDocument(MergeFlowStep $step, ScanLog $scanLog, string $vendorName, string $documentNumber): void
    {
        DB::transaction(function () use ($step, $scanLog, $vendorName, $documentNumber) {
            $group = DocumentMergeGroup::firstOrCreate(
                [
                    'merge_flow_id' => $step->merge_flow_id,
                    'vendor_name' => $vendorName,
                    'root_document_number' => $documentNumber,
                ],
                ['status' => 0]
            );

            DocumentMergeGroupItem::updateOrCreate(
                ['merge_group_id' => $group->id, 'document_type_id' => $scanLog->document_type_id],
                [
                    'scan_log_id' => $scanLog->id,
                    'document_number' => $documentNumber,
                    'order' => $step->order,
                    'ftp_path' => $scanLog->ftp_path,
                ]
            );

            $this->checkAndTriggerMerge($group, $step->mergeFlow);
        });
    }

    protected function handleChildDocument(MergeFlowStep $step, ScanLog $scanLog, string $ocrText, string $vendorName, string $documentNumber): void
    {
        $linkedNumber = $this->extractLinkedNumber($step, $ocrText);
        if (! $linkedNumber) {
            Log::warning('Could not extract linked number from child document', [
                'scan_log_id' => $scanLog->id,
                'link_regex' => $step->link_regex,
            ]);

            return;
        }

        $linkedNumbers = $scanLog->linked_numbers ?? [];
        $linkedNumbers[$step->link_label ?? 'linked_number'] = $linkedNumber;
        $scanLog->update(['linked_numbers' => $linkedNumbers]);

        $parentStep = MergeFlowStep::where('merge_flow_id', $step->merge_flow_id)
            ->where('order', $step->order - 1)
            ->first();

        if (! $parentStep) {
            Log::warning('Parent step not found', ['step_id' => $step->id]);

            return;
        }

        DB::transaction(function () use ($step, $scanLog, $vendorName, $documentNumber, $linkedNumber, $parentStep) {
            $group = DocumentMergeGroup::where('merge_flow_id', $step->merge_flow_id)
                ->where('vendor_name', $vendorName)
                ->whereHas('items', function ($q) use ($linkedNumber, $parentStep) {
                    $q->where('document_number', $linkedNumber)
                        ->where('order', $parentStep->order);
                })
                ->first();

            if (! $group) {
                $group = DocumentMergeGroup::create([
                    'merge_flow_id' => $step->merge_flow_id,
                    'vendor_name' => $vendorName,
                    'root_document_number' => $linkedNumber,
                    'status' => 0,
                ]);
            }

            DocumentMergeGroupItem::updateOrCreate(
                ['merge_group_id' => $group->id, 'document_type_id' => $scanLog->document_type_id],
                [
                    'scan_log_id' => $scanLog->id,
                    'document_number' => $documentNumber,
                    'order' => $step->order,
                    'ftp_path' => $scanLog->ftp_path,
                ]
            );

            $this->checkAndTriggerMerge($group, $step->mergeFlow);
        });
    }

    protected function extractLinkedNumber(MergeFlowStep $step, string $ocrText): ?string
    {
        if (! $step->link_regex) {
            return null;
        }

        if (preg_match($step->link_regex, $ocrText, $matches)) {
            $raw = trim($matches[1]);
            $cleaned = preg_replace('/[\x00-\x1F\x7F]/', ' ', $raw);
            $cleaned = preg_replace('/\s{2,}/', ' ', $cleaned);
            $cleaned = trim($cleaned);

            return $cleaned !== '' ? $cleaned : null;
        }

        return null;
    }

    protected function checkAndTriggerMerge(DocumentMergeGroup $group, MergeFlow $flow): void
    {
        $totalSteps = $flow->steps()->count();
        $totalItems = $group->items()->count();

        if ($totalItems >= $totalSteps) {
            $group->update(['status' => 1]);
            $this->performFinalMerge($group, $flow);
        }
    }

    protected function performFinalMerge(DocumentMergeGroup $group, MergeFlow $flow): void
    {
        $ftpDisk = Storage::disk('ftp_final');
        $items = $group->items()->orderBy('order')->get();
        $tempFiles = [];
        $mergedPath = null;

        try {
            foreach ($items as $item) {
                if (! $ftpDisk->exists($item->ftp_path)) {
                    throw new Exception("File not found on FTP: {$item->ftp_path}");
                }
                $content = $ftpDisk->get($item->ftp_path);
                $tempFile = storage_path("app/private/scanner/temp/merge_flow_{$group->id}_{$item->order}.pdf");
                $this->ensureDirectoryExists(dirname($tempFile));
                file_put_contents($tempFile, $content);
                $tempFiles[] = $tempFile;
            }

            $numbers = $items->pluck('document_number')->implode('_');
            $finalFilename = "FINAL_{$group->vendor_name}_{$numbers}.pdf";

            $mergedDir = storage_path('app/private/scanner/merged_flow');
            $this->ensureDirectoryExists($mergedDir);
            $mergedPath = $mergedDir.'/'.$finalFilename;

            $merger = app(PdfMergeService::class);
            $merger->mergePdfs($tempFiles, $mergedPath);

            $finalPath = "FINAL/{$group->vendor_name}/{$finalFilename}";
            $mergedContent = file_get_contents($mergedPath);
            $ftpDisk->put($finalPath, $mergedContent);

            $group->update([
                'status' => 2,
                'final_pdf_path' => $finalPath,
                'merged_at' => now(),
            ]);

            $totalPages = $merger->getPageCount($mergedPath);
            app(ScanLogger::class)->log('final_merge_completed', 'success', [
                'merge_group_id' => $group->id,
                'vendor_name' => $group->vendor_name,
                'root_document_number' => $group->root_document_number,
                'final_pdf_path' => $finalPath,
                'total_documents' => $items->count(),
                'total_pages' => $totalPages,
                'message' => "Final merge selesai: {$items->count()} dokumen, {$totalPages} halaman",
            ]);

            Log::info('Final merge completed', [
                'group_id' => $group->id,
                'final_path' => $finalPath,
            ]);

        } catch (Exception $e) {
            Log::error('Final merge failed', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
            ]);

            app(ScanLogger::class)->log('final_merge_failed', 'failed', [
                'merge_group_id' => $group->id,
                'vendor_name' => $group->vendor_name,
                'root_document_number' => $group->root_document_number,
                'message' => "Final merge gagal: {$e->getMessage()}",
            ]);

        } finally {
            foreach ($tempFiles as $file) {
                @unlink($file);
            }
            if ($mergedPath) {
                @unlink($mergedPath);
            }
        }
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
