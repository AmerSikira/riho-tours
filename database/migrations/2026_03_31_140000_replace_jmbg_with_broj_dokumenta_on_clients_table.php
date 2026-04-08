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
        if (Schema::hasColumn('clients', 'jmbg') && ! Schema::hasColumn('clients', 'broj_dokumenta')) {
            Schema::table('clients', function (Blueprint $table): void {
                $table->renameColumn('jmbg', 'broj_dokumenta');
            });
        }

        $this->dropUniqueIndexIfExists('clients', 'clients_jmbg_unique');
        $this->dropUniqueIndexIfExists('clients', 'clients_broj_dokumenta_unique');

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE clients MODIFY broj_dokumenta TEXT NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE clients ALTER COLUMN broj_dokumenta TYPE TEXT');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE clients MODIFY broj_dokumenta VARCHAR(13) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE clients ALTER COLUMN broj_dokumenta TYPE VARCHAR(13)');
        }

        Schema::table('clients', function (Blueprint $table): void {
            $table->unique('broj_dokumenta');
        });

        if (Schema::hasColumn('clients', 'broj_dokumenta') && ! Schema::hasColumn('clients', 'jmbg')) {
            Schema::table('clients', function (Blueprint $table): void {
                $table->renameColumn('broj_dokumenta', 'jmbg');
            });
        }
    }

    private function dropUniqueIndexIfExists(string $table, string $index): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropUnique($index);
            });
        } catch (Throwable) {
            // Ignore missing index names across DB engines.
        }
    }
};
