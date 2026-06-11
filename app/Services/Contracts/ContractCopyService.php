<?php

namespace App\Services\Contracts;

use App\Models\ContractTemplate;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ContractCopyService
{
    private const PUBLIC_COPY_REVISION = 'account-number-label-v2';

    public function __construct(
        private readonly ContractGenerationService $generationService,
    ) {}

    /**
     * Prepare the current public contract copy and extend its retention window.
     */
    public function prepareShare(Reservation $reservation, ContractTemplate $template): ContractShareResult
    {
        $this->cleanupExpired();

        $reservation->refresh();
        $this->ensureAccessSignatureHash($reservation);

        $expiresAt = Carbon::now()->addDays($this->publicAccessDays());
        $path = $this->stablePath($reservation);
        $hasReusableCopy = $this->hasReusableCopy($reservation, $path);

        if (! $hasReusableCopy) {
            $this->storeCurrentCopy($reservation, $template, $path);
        }

        try {
            $reservation->forceFill([
                'contract_pdf_path' => $path,
                'contract_expires_at' => $expiresAt,
            ])->save();
        } catch (\Throwable $exception) {
            if (! $hasReusableCopy) {
                $this->deleteCopy($path);
            }

            throw $exception;
        }

        return new ContractShareResult(
            path: $path,
            signature: $reservation->contractAccessSignature(),
            expiresAt: $expiresAt,
        );
    }

    /**
     * Refresh a still-valid public copy when its render revision is stale.
     */
    public function refreshStaleCopy(Reservation $reservation, ContractTemplate $template): void
    {
        $path = $reservation->contract_pdf_path;
        if (! $path || $this->hasCurrentCopyMetadata($path)) {
            return;
        }

        $this->storeCurrentCopy($reservation, $template, $path);
    }

    /**
     * Remove expired public contract copies without deleting reservations.
     */
    public function cleanupExpired(): int
    {
        $expiredReservations = Reservation::query()
            ->whereNotNull('contract_expires_at')
            ->where('contract_expires_at', '<=', Carbon::now())
            ->get(['id', 'contract_pdf_path', 'contract_expires_at']);

        foreach ($expiredReservations as $reservation) {
            $this->deleteCopy($reservation->contract_pdf_path);

            $reservation->forceFill([
                'contract_pdf_path' => null,
                'contract_expires_at' => null,
            ])->saveQuietly();
        }

        return $expiredReservations->count();
    }

    /**
     * Remove the current public contract copy after reservation data changes.
     */
    public function invalidate(Reservation $reservation): void
    {
        $this->deleteCopy($reservation->contract_pdf_path);

        $reservation->forceFill([
            'contract_pdf_path' => null,
            'contract_expires_at' => null,
        ])->saveQuietly();
    }

    /**
     * Check whether an unauthenticated request can open the stored PDF.
     */
    public function canOpen(Reservation $reservation, string $signature): bool
    {
        if ($signature === '' || $reservation->contract_access_signature_hash === null) {
            return false;
        }

        $providedHash = Reservation::hashContractAccessSignature($signature);
        if (! hash_equals((string) $reservation->contract_access_signature_hash, $providedHash)) {
            return false;
        }

        if ($reservation->contract_expires_at === null || Carbon::now()->greaterThan($reservation->contract_expires_at)) {
            return false;
        }

        return $reservation->contract_pdf_path !== null
            && Storage::disk('local')->exists($reservation->contract_pdf_path);
    }

    /**
     * Build the stable private storage path for one reservation contract copy.
     */
    public function stablePath(Reservation $reservation): string
    {
        return sprintf('contracts/reservations/%s/contract.pdf', $reservation->id);
    }

    private function publicAccessDays(): int
    {
        return max(1, (int) config('contracts.public_access_days', 30));
    }

    private function hasReusableCopy(Reservation $reservation, string $path): bool
    {
        return $reservation->contract_expires_at !== null
            && ! Carbon::now()->greaterThan($reservation->contract_expires_at)
            && $reservation->contract_pdf_path === $path
            && Storage::disk('local')->exists($path)
            && $this->hasCurrentCopyMetadata($path);
    }

    private function storeCurrentCopy(Reservation $reservation, ContractTemplate $template, string $path): void
    {
        $document = $this->generationService->generate($reservation, $template);
        if (! $document->hasPdfContent()) {
            throw new RuntimeException('Contract PDF content could not be generated.');
        }

        if ($reservation->contract_pdf_path && $reservation->contract_pdf_path !== $path) {
            $this->deleteCopy($reservation->contract_pdf_path);
        }

        $disk = Storage::disk('local');
        if (! $disk->put($path, (string) $document->pdfContent)) {
            throw new RuntimeException('Contract PDF could not be stored.');
        }

        $this->writeCopyMetadata($path);
    }

    private function hasCurrentCopyMetadata(string $path): bool
    {
        $disk = Storage::disk('local');
        $metadataPath = $this->metadataPath($path);
        if (! $disk->exists($metadataPath)) {
            return false;
        }

        $metadata = json_decode((string) $disk->get($metadataPath), true);

        return is_array($metadata)
            && ($metadata['revision'] ?? null) === self::PUBLIC_COPY_REVISION;
    }

    private function writeCopyMetadata(string $path): void
    {
        $metadata = json_encode([
            'revision' => self::PUBLIC_COPY_REVISION,
            'generated_at' => Carbon::now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        if (! Storage::disk('local')->put($this->metadataPath($path), $metadata)) {
            throw new RuntimeException('Contract PDF metadata could not be stored.');
        }
    }

    private function deleteCopy(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('local')->delete([
            $path,
            $this->metadataPath($path),
        ]);
    }

    private function metadataPath(string $path): string
    {
        return $path.'.meta.json';
    }

    private function ensureAccessSignatureHash(Reservation $reservation): void
    {
        if ($reservation->contract_access_signature_hash !== null) {
            return;
        }

        $reservation->forceFill([
            'contract_access_signature_hash' => Reservation::hashContractAccessSignature(
                $reservation->contractAccessSignature()
            ),
        ])->saveQuietly();
    }
}
