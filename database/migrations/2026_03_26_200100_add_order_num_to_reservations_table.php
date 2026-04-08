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
        Schema::create('reservation_order_sequences', function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('order_num')->nullable()->after('id');
            $table->unique('order_num');
        });

        $reservationIds = DB::table('reservations')
            ->whereNull('order_num')
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id');

        foreach ($reservationIds as $reservationId) {
            $orderNum = (int) DB::table('reservation_order_sequences')->insertGetId([]);

            DB::table('reservations')
                ->where('id', $reservationId)
                ->update(['order_num' => $orderNum]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_order_num_unique');
            $table->dropColumn('order_num');
        });

        Schema::dropIfExists('reservation_order_sequences');
    }
};
