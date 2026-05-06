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
            $table->boolean('ime_na_predracunu_racunu')->default(false)->after('paket_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_clients', function (Blueprint $table) {
            $table->dropColumn('ime_na_predracunu_racunu');
        });
    }
};

