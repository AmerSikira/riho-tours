<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProductionDatabaseSeeder extends Seeder
{
    /**
     * Seed production-safe data only.
     */
    public function run(): void
    {
        $this->purgeProductionData();

        $this->call(RolesSeeder::class);
        $this->seedPrimaryAdminUser();
    }

    /**
     * Remove all data except permission catalog and users table.
     */
    private function purgeProductionData(): void
    {
        $permissionTables = array_values(config('permission.table_names', []));
        $tablesToKeep = array_merge(['migrations', 'users'], $permissionTables);
        $tables = Schema::getTableListing();

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                if (in_array($table, $tablesToKeep, true)) {
                    continue;
                }

                DB::table($table)->truncate();
            }

            DB::table('users')
                ->where('email', '!=', 'user1@user.com')
                ->delete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Ensure the single production admin account exists and is active.
     */
    private function seedPrimaryAdminUser(): void
    {
        $adminUser = User::withTrashed()->firstOrNew(['email' => 'user1@user.com']);
        $adminUser->name = 'Admin User';
        $adminUser->password = Hash::make('Reservax123#!');
        $adminUser->email_verified_at = now();
        $adminUser->is_active = true;
        $adminUser->save();

        if ($adminUser->trashed()) {
            $adminUser->restore();
        }

        $modelMorphKey = (string) config('permission.column_names.model_morph_key', 'model_id');

        DB::table(config('permission.table_names.model_has_roles'))
            ->where('model_type', User::class)
            ->where($modelMorphKey, '!=', $adminUser->id)
            ->delete();

        DB::table(config('permission.table_names.model_has_permissions'))
            ->where('model_type', User::class)
            ->where($modelMorphKey, '!=', $adminUser->id)
            ->delete();

        $adminUser->syncRoles(['admin']);
    }
}
