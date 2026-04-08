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
        Schema::table('clients', function (Blueprint $table): void {
            $table->text('broj_dokumenta')->nullable()->change();
            $table->date('datum_rodjenja')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->text('broj_dokumenta')->nullable(false)->change();
            $table->date('datum_rodjenja')->nullable(false)->change();
        });
    }
};
