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
        Schema::table('reservation_clients', function (Blueprint $table) {
            $table->decimal('dodatno_na_cijenu', 10, 2)->default(0)->after('paket_id');
            $table->decimal('popust', 10, 2)->default(0)->after('dodatno_na_cijenu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_clients', function (Blueprint $table) {
            $table->dropColumn(['dodatno_na_cijenu', 'popust']);
        });
    }
};
