<?php

namespace App\Services\Contracts;

use Carbon\CarbonInterface;

final readonly class ContractDocument
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $computedPlaceholders
     */
    public function __construct(
        public string $contractNumber,
        public string $renderedHtml,
        public ?string $pdfContent,
        public array $data,
        public array $computedPlaceholders,
        public CarbonInterface $generatedAt,
    ) {}

    public function hasPdfContent(): bool
    {
        return $this->pdfContent !== null && $this->pdfContent !== '';
    }

    public function documentTitle(): string
    {
        return $this->contractNumber !== '' ? $this->contractNumber : 'Ugovor';
    }

    /**
     * @return array<string, mixed>
     */
    public function company(): array
    {
        $company = data_get($this->data, 'company', []);

        return is_array($company) ? $company : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(): array
    {
        $contract = data_get($this->data, 'contract', []);

        return is_array($contract) ? $contract : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function pdfViewData(): array
    {
        return [
            'html' => $this->renderedHtml,
            'company' => $this->company(),
            'contract' => $this->contract(),
            'document_title' => $this->documentTitle(),
        ];
    }
}
