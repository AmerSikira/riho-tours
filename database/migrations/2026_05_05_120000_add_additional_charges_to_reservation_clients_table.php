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
            $table->decimal('boravisna_taksa', 10, 2)->default(0)->after('popust');
            $table->decimal('osiguranje', 10, 2)->default(0)->after('boravisna_taksa');
            $table->decimal('doplata_jednokrevetna_soba', 10, 2)->default(0)->after('osiguranje');
            $table->decimal('doplata_dodatno_sjediste', 10, 2)->default(0)->after('doplata_jednokrevetna_soba');
            $table->decimal('doplata_sjediste_po_zelji', 10, 2)->default(0)->after('doplata_dodatno_sjediste');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_clients', function (Blueprint $table) {
            $table->dropColumn([
                'boravisna_taksa',
                'osiguranje',
                'doplata_jednokrevetna_soba',
                'doplata_dodatno_sjediste',
                'doplata_sjediste_po_zelji',
            ]);
        });
    }
};
