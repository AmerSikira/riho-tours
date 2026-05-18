<?php

namespace App\Jobs;

use App\Models\GeneratedContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteYesterdayGeneratedContractPdfs implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    /**
     * Delete stored contract PDFs generated during the previous application day.
     */
    public function handle(): void
    {
        $targetDate = Carbon::now()->subDay();

        GeneratedContract::withTrashed()
            ->whereNotNull('rendered_pdf_path')
            ->whereBetween('generated_at', [
                $targetDate->copy()->startOfDay(),
                $targetDate->copy()->endOfDay(),
            ])
            ->orderBy('id')
            ->chunkById(100, function ($contracts): void {
                foreach ($contracts as $contract) {
                    $this->deleteStoredPdf($contract);
                }
            });
    }

    /**
     * Remove the physical PDF and clear the stale path from the database row.
     */
    private function deleteStoredPdf(GeneratedContract $contract): void
    {
        $path = trim((string) $contract->rendered_pdf_path);
        if ($path === '' || ! str_ends_with(strtolower($path), '.pdf')) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);

            $contract->forceFill([
                'rendered_pdf_path' => null,
            ])->saveQuietly();
        } catch (\Throwable $exception) {
            Log::warning('Failed to delete generated contract PDF during login cleanup.', [
                'generated_contract_id' => (string) $contract->id,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
