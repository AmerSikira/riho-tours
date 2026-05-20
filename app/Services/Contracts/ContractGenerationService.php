<?php

namespace App\Services\Contracts;

use App\Models\ContractTemplate;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
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
     * Generate a contract document in memory without database persistence.
     */
    public function generate(Reservation $reservation, ContractTemplate $template, bool $renderPdf = true): ContractDocument
    {
        $data = $this->dataBuilder->build($reservation);
        $computed = $this->dataBuilder->buildComputedPlaceholders($data);

        $renderedHtml = $this->renderer->render($template->html_template, $data, $computed);

        $now = Carbon::now();
        $viewData = [
            'html' => $renderedHtml,
            'company' => $data['company'] ?? [],
            'contract' => $data['contract'] ?? [],
            'document_title' => (string) data_get($data, 'contract.number', 'Ugovor'),
        ];

        return new ContractDocument(
            contractNumber: (string) data_get($data, 'contract.number', ''),
            renderedHtml: $renderedHtml,
            pdfContent: $renderPdf ? $this->renderPdf($viewData) : null,
            data: $data,
            computedPlaceholders: $computed,
            generatedAt: $now,
        );
    }

    /**
     * Render contract PDF bytes using Browsershot first, then DOMPDF fallback.
     *
     * @param  array<string, mixed>  $viewData
     */
    private function renderPdf(array $viewData): ?string
    {
        try {
            return LaravelPdf::view('contracts.generated', $viewData)
                ->driver('browsershot')
                ->format('a4')
                ->margins(10, 10, 16, 10)
                ->withBrowsershot(function (Browsershot $browsershot): void {
                    $browsershot
                        ->showBackground()
                        ->waitUntilNetworkIdle();
                })
                ->generatePdfContent();
        } catch (\Throwable $exception) {
            Log::warning('Browsershot contract PDF generation failed, trying DOMPDF fallback.', [
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            return DomPdf::loadView('contracts.generated', $viewData)
                ->setPaper('a4')
                ->output();
        } catch (\Throwable $exception) {
            Log::error('DOMPDF contract fallback failed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }
}
