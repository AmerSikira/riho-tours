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
        Schema::table('arrangement_packages', function (Blueprint $table) {
            $table->decimal('smjestaj_trosak', 10, 2)->default(0)->after('cijena');
            $table->decimal('transport_trosak', 10, 2)->default(0)->after('smjestaj_trosak');
            $table->decimal('fakultativne_stvari_trosak', 10, 2)->default(0)->after('transport_trosak');
            $table->decimal('ostalo_trosak', 10, 2)->default(0)->after('fakultativne_stvari_trosak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrangement_packages', function (Blueprint $table) {
            $table->dropColumn([
                'smjestaj_trosak',
                'transport_trosak',
                'fakultativne_stvari_trosak',
                'ostalo_trosak',
            ]);
        });
    }
};
