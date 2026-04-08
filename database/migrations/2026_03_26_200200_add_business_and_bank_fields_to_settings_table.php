<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('maticni_broj_subjekta_upisa')->nullable()->after('company_id');
            $table->string('banka')->nullable()->after('trn');
            $table->string('iban')->nullable()->after('banka');
            $table->string('swift')->nullable()->after('iban');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'maticni_broj_subjekta_upisa',
                'banka',
                'iban',
                'swift',
            ]);
        });
    }
};
