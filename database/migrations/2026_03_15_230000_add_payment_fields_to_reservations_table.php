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
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('placanje', 20)->default('placeno')->after('status');
            $table->unsignedTinyInteger('broj_rata')->nullable()->after('placanje');
            $table->json('rate')->nullable()->after('broj_rata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['placanje', 'broj_rata', 'rate']);
        });
    }
};
