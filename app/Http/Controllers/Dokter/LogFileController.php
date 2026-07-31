<?php

namespace App\Http\Controllers\Dokter;

use App\Exports\ScanLogsExport;
use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\ScanLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LogFileController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Log File';

        $logs = $this->applyFilters(ScanLog::query(), $request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $documentTypes = DocumentType::orderBy('name')->get();
        $statuses = ['success', 'failed', 'warning', 'skipped', 'info'];

        return view('dokter.log-file.index', compact('pageName', 'logs', 'documentTypes', 'statuses'));
    }

    public function export(Request $request)
    {
        $logs = $this->applyFilters(ScanLog::query(), $request)
            ->latest()
            ->get();

        return Excel::download(new ScanLogsExport($logs), 'log-file-'.date('Y-m-d-Hi').'.xlsx');
    }

    protected function applyFilters($query, Request $request)
    {
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('document_type_id')) {
            $query->where('document_type_id', $request->document_type_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('ftp_path', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
