<?php

namespace App\Services\Contracts;

use App\Models\ContractTemplate;
use App\Models\GeneratedContract;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf as LaravelPdf;

class ContractGenerationService
{
    public function __construct(
        private readonly ContractDataBuilder $dataBuilder,
        private readonly ContractTemplateRenderer $renderer,
    ) {}

    /**
     * Generate HTML and PDF contract and persist full generation snapshot.
     */
    public function generate(Reservation $reservation, ContractTemplate $template, ?string $userId = null): GeneratedContract
    {
        $data = $this->dataBuilder->build($reservation);
        $computed = $this->dataBuilder->buildComputedPlaceholders($data);

        $renderedHtml = $this->renderer->render($template->html_template, $data, $computed);

        $now = Carbon::now();
        $pdfPath = $this->buildPdfPath((string) $reservation->id, (string) $template->template_key, $now);

        $pdfWasRendered = $this->renderPdf($pdfPath, [
            'html' => $renderedHtml,
            'company' => $data['company'] ?? [],
            'contract' => $data['contract'] ?? [],
            'document_title' => (string) data_get($data, 'contract.number', 'Ugovor'),
        ]);

        return GeneratedContract::query()->create([
            'reservation_id' => $reservation->id,
            'contract_template_id' => $template->id,
            'template_version' => $template->version,
            'contract_number' => data_get($data, 'contract.number'),
            'rendered_html' => $renderedHtml,
            'rendered_pdf_path' => $pdfWasRendered ? $pdfPath : null,
            'snapshot_data_json' => [
                'data' => $data,
                'computed' => $computed,
                'template' => [
                    'id' => $template->id,
                    'template_key' => $template->template_key,
                    'version' => $template->version,
                    'name' => $template->name,
                ],
            ],
            'generated_at' => $now,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Re-render a stored contract snapshot when the database row points to a missing PDF.
     */
    public function regeneratePdfIfMissing(GeneratedContract $contract, ?string $userId = null): GeneratedContract
    {
        if ($this->hasRenderedPdf($contract)) {
            return $contract;
        }

        $now = Carbon::now();
        $pdfPath = $this->buildPdfPath(
            (string) $contract->reservation_id,
            $this->snapshotTemplateKey($contract),
            $now
        );

        $pdfWasRendered = $this->renderPdf($pdfPath, $this->snapshotViewData($contract));
        if (! $pdfWasRendered) {
            return $contract;
        }

        $contract->forceFill([
            'rendered_pdf_path' => $pdfPath,
            'updated_by' => $userId,
        ])->save();

        return $contract->refresh();
    }

    /**
     * Determine whether the generated contract still has a readable stored PDF.
     */
    public function hasRenderedPdf(GeneratedContract $contract): bool
    {
        $path = trim((string) $contract->rendered_pdf_path);

        return $path !== '' && Storage::disk('public')->exists($path);
    }

    /**
     * Render contract PDF to storage using Browsershot first, then DOMPDF fallback.
     *
     * @param  array<string, mixed>  $viewData
     */
    private function renderPdf(string $pdfPath, array $viewData): bool
    {
        try {
            LaravelPdf::view('contracts.generated', $viewData)
                ->driver('browsershot')
                ->format('a4')
                ->margins(10, 10, 16, 10)
                ->withBrowsershot(function (Browsershot $browsershot): void {
                    $browsershot
                        ->showBackground()
                        ->waitUntilNetworkIdle();
                })
                ->disk('public')
                ->save($pdfPath);

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Browsershot contract PDF generation failed, trying DOMPDF fallback.', [
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            $pdfBinary = DomPdf::loadView('contracts.generated', $viewData)
                ->setPaper('a4')
                ->output();

            Storage::disk('public')->put($pdfPath, $pdfBinary);

            return true;
        } catch (\Throwable $exception) {
            Log::error('DOMPDF contract fallback failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Build the public disk path for a generated contract PDF.
     */
    private function buildPdfPath(string $reservationId, string $templateKey, Carbon $timestamp): string
    {
        return sprintf(
            'contracts/%s/contract-%s-%s.pdf',
            $reservationId,
            strtolower($templateKey),
            $timestamp->format('YmdHis')
        );
    }

    /**
     * Resolve the template key stored in a generated contract snapshot.
     */
    private function snapshotTemplateKey(GeneratedContract $contract): string
    {
        $templateKey = trim((string) data_get($contract->snapshot_data_json, 'template.template_key', 'contract'));

        return $templateKey !== '' ? $templateKey : 'contract';
    }

    /**
     * Build PDF view data from an existing generated contract snapshot.
     *
     * @return array<string, mixed>
     */
    private function snapshotViewData(GeneratedContract $contract): array
    {
        return [
            'html' => (string) ($contract->rendered_html ?? ''),
            'company' => data_get($contract->snapshot_data_json, 'data.company', []),
            'contract' => data_get($contract->snapshot_data_json, 'data.contract', []),
            'document_title' => (string) ($contract->contract_number ?: 'Ugovor'),
        ];
    }
}
