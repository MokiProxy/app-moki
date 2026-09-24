<?php

namespace App\Models\Erkap\Traits;

use App\Models\Erkap\AuditLog;
use Illuminate\Support\Facades\Auth;

trait HasAuditTrail
{
    public static function bootHasAuditTrail()
    {
        static::creating(function ($model) {
            if (! $model->created_by && Auth::check()) {
                $model->created_by = Auth::id();
            }

            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::created(function ($model) {
            $model->logAudit('create', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = array_intersect_key($model->getOriginal(), $model->getDirty());

            $model->logAudit('update', $old, $model->getDirty());
        });

        static::deleted(function ($model) {
            $model->logAudit('delete', $model->getAttributes(), null);
        });
    }

    public function logAudit(string $action, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function audits()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}