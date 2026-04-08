<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CompanySettingsSnapshotSeeder extends Seeder
{
    /**
     * Seed company settings with fields aligned to the latest schema.
     */
    public function run(): void
    {
        $actorId = User::query()->where('email', 'user1@user.com')->value('id');

        $setting = Setting::query()->firstOrNew([]);

        $payload = [
            'company_name' => 'Axiom Tours',
            'company_id' => '12345678912345',
            'maticni_broj_subjekta_upisa' => '000-0-Reg-00-000000',
            'pdv' => '1234567891234',
            'u_pdv_sistemu' => true,
            'trn' => '12345678912345',
            'broj_kase' => '1',
            'banka' => 'UniCredit Bank',
            'iban' => 'BA391290079401028494',
            'swift' => 'UNCRBA22',
            'osiguravajuce_drustvo' => 'ASA Osiguranje',
            'polisa_osiguranja' => 'POL-COMP-001',
            'email' => 'info@axiomtours.ba',
            'phone' => '+387 61 580 099',
            'address' => 'Selima ef. Merdanovica 45',
            'city' => 'Kakanj',
            'zip' => '72240',
            'logo_path' => null,
            'potpis_path' => null,
            'pecat_path' => null,
        ];

        if (Schema::hasColumn('settings', 'created_by')) {
            $payload['created_by'] = $setting->exists ? $setting->created_by : $actorId;
        }

        if (Schema::hasColumn('settings', 'updated_by')) {
            $payload['updated_by'] = $actorId;
        }

        if (! Schema::hasColumn('settings', 'broj_kase')) {
            unset($payload['broj_kase']);
        }

        if (! Schema::hasColumn('settings', 'polisa_osiguranja')) {
            unset($payload['polisa_osiguranja']);
        }

        if (! Schema::hasColumn('settings', 'u_pdv_sistemu')) {
            unset($payload['u_pdv_sistemu']);
        }

        $setting->fill($payload);
        $setting->save();
    }
}
