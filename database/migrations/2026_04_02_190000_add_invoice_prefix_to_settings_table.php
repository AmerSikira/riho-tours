<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('invoice_prefix')->nullable()->default('WEB')->after('company_name');
        });

        DB::table('settings')
            ->whereNull('invoice_prefix')
            ->update(['invoice_prefix' => 'WEB']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn('invoice_prefix');
        });
    }
};
