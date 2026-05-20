<?php

namespace App\Services\Contracts;

use App\Models\ContractTemplate;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ContractCopyService
{
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
        $disk = Storage::disk('local');
        $hasReusableCopy = $reservation->contract_expires_at !== null
            && ! Carbon::now()->greaterThan($reservation->contract_expires_at)
            && $reservation->contract_pdf_path === $path
            && $disk->exists($path);

        if (! $hasReusableCopy) {
            $document = $this->generationService->generate($reservation, $template);
            if (! $document->hasPdfContent()) {
                throw new RuntimeException('Contract PDF content could not be generated.');
            }

            if ($reservation->contract_pdf_path && $reservation->contract_pdf_path !== $path) {
                $disk->delete($reservation->contract_pdf_path);
            }

            if (! $disk->put($path, (string) $document->pdfContent)) {
                throw new RuntimeException('Contract PDF could not be stored.');
            }
        }

        try {
            $reservation->forceFill([
                'contract_pdf_path' => $path,
                'contract_expires_at' => $expiresAt,
            ])->save();
        } catch (\Throwable $exception) {
            if (! $hasReusableCopy) {
                $disk->delete($path);
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
     * Remove expired public contract copies without deleting reservations.
     */
    public function cleanupExpired(): int
    {
        $expiredReservations = Reservation::query()
            ->whereNotNull('contract_expires_at')
            ->where('contract_expires_at', '<=', Carbon::now())
            ->get(['id', 'contract_pdf_path', 'contract_expires_at']);

        $disk = Storage::disk('local');
        foreach ($expiredReservations as $reservation) {
            if ($reservation->contract_pdf_path) {
                $disk->delete($reservation->contract_pdf_path);
            }

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
        if ($reservation->contract_pdf_path) {
            Storage::disk('local')->delete($reservation->contract_pdf_path);
        }

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
