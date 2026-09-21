<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erkap\FilterAuditLogRequest;
use App\Models\Erkap\AuditLog;
use App\Services\AuditService;

class AuditLogController extends Controller
{
    public function index(FilterAuditLogRequest $request)
    {
        $pageName = 'Audit Trail';

        $logs = AuditService::history($request);
        $filters = $request->validated();

        $actions = [
            AuditLog::ACTION_CREATE => 'Buat',
            AuditLog::ACTION_UPDATE => 'Ubah',
            AuditLog::ACTION_DELETE => 'Hapus',
        ];

        $types = AuditLog::query()
            ->distinct()
            ->pluck('auditable_type')
            ->mapWithKeys(fn ($type) => [$type => AuditLog::typeLabel($type)])
            ->sort()
            ->all();

        $users = \App\Models\User::query()->orderBy('name')->pluck('name', 'id');

        return view('erkap.audit-logs.index', compact('pageName', 'logs', 'filters', 'actions', 'types', 'users'));
    }

    public function show($id)
    {
        $pageName = 'Detail Audit Trail';

        $log = AuditLog::with(['user', 'auditable'])->findOrFail($id);
        $diff = AuditService::diff($log);

        return view('erkap.audit-logs.show', compact('pageName', 'log', 'diff'));
    }
}