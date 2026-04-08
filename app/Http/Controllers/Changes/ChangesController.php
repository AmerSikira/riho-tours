<?php

namespace App\Http\Controllers\Changes;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChangesController extends Controller
{
    /**
     * Display audit logs with filtering by user, location, and date range.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));
        $dateFrom = trim((string) $request->string('datum_od'));
        $dateTo = trim((string) $request->string('datum_do'));

        $logs = AuditLog::query()
            ->with(['causer'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->whereHasMorph(
                            'causer',
                            [User::class],
                            fn ($morphQuery) => $morphQuery->where('name', 'like', "%{$search}%")
                        )
                        ->orWhere('auditable_type', 'like', "%{$search}%")
                        ->orWhere('request_context->url', 'like', "%{$search}%");
                });
            })
            ->when($dateFrom !== '', function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $logs->setCollection(
            $logs->getCollection()->map(function (AuditLog $log): array {
                $userName = trim((string) ($log->causer?->name ?? 'Sistem'));
                $location = $this->resolveLocation($log);

                return [
                    'id' => $log->id,
                    'event' => $this->eventLabel($log->event),
                    'user_name' => $userName,
                    'location' => $location,
                    'changed_at' => $this->formatDateTime($log->created_at),
                    'details' => [
                        'event_key' => $log->event,
                        'auditable_type' => class_basename($log->auditable_type),
                        'auditable_id' => $log->auditable_id,
                        'causer_type' => $log->causer_type ? class_basename($log->causer_type) : null,
                        'causer_id' => $log->causer_id,
                        'old_values' => $log->old_values,
                        'new_values' => $log->new_values,
                        'request_context' => $log->request_context,
                    ],
                ];
            })
        );

        return Inertia::render('changes/index', [
            'logs' => $logs,
            'filters' => [
                'pretraga' => $search,
                'datum_od' => $dateFrom,
                'datum_do' => $dateTo,
            ],
        ]);
    }

    /**
     * Build a readable location string from request metadata.
     */
    private function resolveLocation(AuditLog $log): string
    {
        $requestContext = $log->request_context ?? [];
        $url = is_array($requestContext) ? ($requestContext['url'] ?? null) : null;

        if (is_string($url) && $url !== '') {
            $path = parse_url($url, PHP_URL_PATH);

            if (is_string($path) && $path !== '') {
                return $path;
            }
        }

        return class_basename($log->auditable_type);
    }

    /**
     * Translate event key to user-facing Bosnian label.
     */
    private function eventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Kreirano',
            'updated' => 'Ažurirano',
            'deleted' => 'Obrisano',
            'restored' => 'Vraćeno',
            default => 'Izmjena',
        };
    }

    /**
     * Format datetime for user-facing output.
     */
    private function formatDateTime($dateTime): string
    {
        if (! $dateTime) {
            return '';
        }

        return Carbon::parse($dateTime)->format('d.m.Y. H:i');
    }
}
