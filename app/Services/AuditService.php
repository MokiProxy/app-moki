<?php

namespace App\Services;

use App\Http\Requests\Erkap\FilterAuditLogRequest;
use App\Models\Erkap\AuditLog;

class AuditService
{
    public static function history(FilterAuditLogRequest $request, int $perPage = 50)
    {
        $query = AuditLog::query()->with(['user', 'auditable'])->latest();

        $filters = $request->validated();

        if (! empty($filters['type'])) {
            $query->where('auditable_type', $filters['type']);
        }

        if (! empty($filters['id'])) {
            $query->where('auditable_id', (int) $filters['id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public static function diff(AuditLog $log): array
    {
        $old = $log->old_values ?: [];
        $new = $log->new_values ?: [];

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        $rows = [];

        foreach ($keys as $key) {
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            if ($key === 'updated_at' && $oldValue !== null && $newValue !== null && $oldValue === $newValue) {
                continue;
            }

            if (! self::valuesDiffer($oldValue, $newValue)) {
                continue;
            }

            $rows[] = [
                'field' => $key,
                'old' => self::formatValue($oldValue),
                'new' => self::formatValue($newValue),
            ];
        }

        return $rows;
    }

    protected static function valuesDiffer($old, $new): bool
    {
        if (is_array($old) || is_array($new)) {
            return json_encode($old) !== json_encode($new);
        }

        return (string) $old !== (string) $new;
    }

    protected static function formatValue($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-';
        }

        return (string) $value;
    }
}