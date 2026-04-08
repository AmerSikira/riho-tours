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
        Schema::create('web_reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('aranzman_id')->nullable()->constrained('arrangements')->nullOnDelete();
            $table->foreignUuid('paket_id')->nullable()->constrained('arrangement_packages')->nullOnDelete();
            $table->foreignUuid('converted_reservation_id')->nullable()->constrained('reservations')->nullOnDelete();

            $table->string('ime')->nullable();
            $table->string('prezime')->nullable();
            $table->string('email')->nullable();
            $table->string('broj_telefona')->nullable();
            $table->string('adresa')->nullable();
            $table->unsignedInteger('broj_putnika')->default(1);
            $table->text('napomena')->nullable();
            $table->string('source_domain')->nullable();
            $table->string('source_url')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 30)->default('novo');
            $table->timestamp('converted_at')->nullable();

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
        Schema::dropIfExists('web_reservations');
    }
};
