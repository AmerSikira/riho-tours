<?php

namespace App\Listeners;

use App\Jobs\DeleteYesterdayGeneratedContractPdfs;
use Illuminate\Auth\Events\Login;

class DispatchGeneratedContractPdfCleanup
{
    /**
     * Queue generated contract PDF cleanup after a successful login.
     */
    public function handle(Login $event): void
    {
        DeleteYesterdayGeneratedContractPdfs::dispatch();
    }
}
