<?php

namespace App\Services\Contracts;

use App\Models\ContractTemplate;
use App\Models\GeneratedContract;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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
        $pdfPath = sprintf(
            'contracts/%s/contract-%s-%s.pdf',
            $reservation->id,
            strtolower((string) $template->template_key),
            $now->format('YmdHis')
        );

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
            Log::warning('Browsershot contract PDF generation failed, falling back to HTML contract rendering.', [
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }
}
