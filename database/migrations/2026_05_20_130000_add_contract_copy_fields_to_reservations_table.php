<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('contract_pdf_path')->nullable()->after('contract_template_id');
            $table->timestamp('contract_expires_at')->nullable()->after('contract_pdf_path')->index();
            $table->string('contract_access_signature_hash', 64)->nullable()->after('contract_expires_at');
        });

        $secret = (string) config('app.key');
        $now = Carbon::now()->toDateTimeString();

        DB::table('reservations')
            ->whereNull('contract_access_signature_hash')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function (object $reservation) use ($secret, $now): void {
                $createdAt = $reservation->created_at
                    ? Carbon::parse($reservation->created_at)->toDateTimeString()
                    : $now;
                $rawSignature = hash_hmac('sha256', sprintf('%s|%s', $reservation->id, $createdAt), $secret);

                DB::table('reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'contract_access_signature_hash' => hash('sha256', $rawSignature),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['contract_expires_at']);
            $table->dropColumn([
                'contract_pdf_path',
                'contract_expires_at',
                'contract_access_signature_hash',
            ]);
        });
    }
};
