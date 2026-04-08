<?php

namespace App\Models\Concerns;

use App\Services\AuditLogService;

trait Auditable
{
    /**
     * Register model events that should be audited.
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model): void {
            app(AuditLogService::class)->log($model, 'created');
        });

        static::updated(function ($model): void {
            app(AuditLogService::class)->log($model, 'updated');
        });

        static::deleted(function ($model): void {
            app(AuditLogService::class)->log($model, 'deleted');
        });

        static::restored(function ($model): void {
            app(AuditLogService::class)->log($model, 'restored');
        });
    }

    /**
     * Extra fields that specific models can hide from audits.
     *
     * @return array<int, string>
     */
    public function auditExcludedFields(): array
    {
        return [];
    }
}
