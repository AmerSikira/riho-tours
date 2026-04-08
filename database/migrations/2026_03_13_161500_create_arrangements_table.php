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
        Schema::create('arrangements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sifra')->unique();
            $table->string('destinacija');
            $table->string('naziv_putovanja');
            $table->text('opis_putovanja');
            $table->date('datum_polaska');
            $table->date('datum_povratka');
            $table->unsignedInteger('trajanje_dana');
            $table->string('tip_prevoza');
            $table->string('tip_smjestaja');
            $table->text('napomena')->nullable();
            $table->boolean('is_active')->default(true);

            // Track who created and last updated the arrangement.
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrangements');
    }
};
