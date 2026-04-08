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
        Schema::table('arrangements', function (Blueprint $table) {
            $table->string('polisa_osiguranja')
                ->nullable()
                ->after('subagentski_aranzman');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrangements', function (Blueprint $table) {
            $table->dropColumn('polisa_osiguranja');
        });
    }
};
