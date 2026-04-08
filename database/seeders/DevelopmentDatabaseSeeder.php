<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentDatabaseSeeder extends Seeder
{
    /**
     * Seed development/staging data including snapshots.
     */
    public function run(): void
    {
        // Seed identity and permission catalog first.
        $this->call(RolesSeeder::class);
        $this->call(DefaultUsersSeeder::class);

        // Seed global settings and templates before domain records.
        $this->call(CompanySettingsSnapshotSeeder::class);
        $this->call(ContractTemplateSeeder::class);
        $this->call(SuppliersSnapshotSeeder::class);

        // Seed arrangements, clients, reservations, and related rows.
        $this->call(DomainSnapshotSeeder::class);
    }
}
