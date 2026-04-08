<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLogService
{
    /**
     * Fields that should never be written to audit payloads.
     *
     * @var list<string>
     */
    private const BASE_EXCLUDED_FIELDS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Persist one audit trail row.
     */
    public function log(Model $model, string $event): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        [$oldValues, $newValues] = $this->resolveValueDiff($model, $event);

        if ($event === 'updated' && $oldValues === [] && $newValues === []) {
            return;
        }

        $causer = auth()->user();
        $request = request();
        $causerId = $causer?->getKey();

        AuditLog::query()->create([
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => (string) $model->getKey(),
            'causer_type' => $causer ? $causer::class : null,
            'causer_id' => $causer ? (string) $causerId : null,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'request_context' => $this->requestContext($request),
            'created_by' => is_string($causerId) ? $causerId : null,
            'updated_by' => is_string($causerId) ? $causerId : null,
        ]);
    }

    /**
     * Build old/new value payload based on event type.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function resolveValueDiff(Model $model, string $event): array
    {
        $excludedFields = array_unique(array_merge(
            self::BASE_EXCLUDED_FIELDS,
            method_exists($model, 'auditExcludedFields') ? $model->auditExcludedFields() : []
        ));

        if ($event === 'created' || $event === 'restored') {
            return [[], $this->sanitizeValues($model->getAttributes(), $excludedFields)];
        }

        if ($event === 'deleted') {
            return [$this->sanitizeValues($model->getOriginal(), $excludedFields), []];
        }

        $changedFields = array_keys($model->getChanges());
        $changedFields = array_values(array_diff($changedFields, $excludedFields));

        return [
            $this->sanitizeValues(Arr::only($model->getOriginal(), $changedFields), $excludedFields),
            $this->sanitizeValues(Arr::only($model->getAttributes(), $changedFields), $excludedFields),
        ];
    }

    /**
     * Keep only audit-safe scalar values.
     *
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $excludedFields
     * @return array<string, mixed>
     */
    private function sanitizeValues(array $values, array $excludedFields): array
    {
        $sanitizedValues = [];

        foreach ($values as $field => $value) {
            if (in_array($field, $excludedFields, true)) {
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $sanitizedValues[$field] = $value->format(DATE_ATOM);
                continue;
            }

            $sanitizedValues[$field] = is_scalar($value) || $value === null
                ? $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $sanitizedValues;
    }

    /**
     * Build request metadata for forensic traceability.
     *
     * @return array<string, mixed>|null
     */
    private function requestContext($request): ?array
    {
        if (! $request) {
            return null;
        }

        return [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
