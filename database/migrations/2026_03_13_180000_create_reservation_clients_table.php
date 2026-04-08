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
        Schema::create('reservation_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rezervacija_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignUuid('klijent_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('paket_id')->constrained('arrangement_packages')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['rezervacija_id', 'klijent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_clients');
    }
};
