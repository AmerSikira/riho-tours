<?php

namespace App\Services\Contracts;

use Carbon\CarbonInterface;

final readonly class ContractShareResult
{
    public function __construct(
        public string $path,
        public string $signature,
        public CarbonInterface $expiresAt,
    ) {}
}
