<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SuppliersSnapshotSeeder extends Seeder
{
    /**
     * Seed supplier rows for subagent arrangement workflows.
     */
    public function run(): void
    {
        $actorId = User::query()->where('email', 'user1@user.com')->value('id');

        $suppliers = [
            [
                'company_name' => 'Golden Line Travel',
                'company_id' => '4509876543210',
                'maticni_broj_subjekta_upisa' => '000-1-Reg-22-000101',
                'pdv' => '509876543210',
                'trn' => '134560987654321',
                'banka' => 'UniCredit Bank',
                'iban' => 'BA391290079401028494',
                'swift' => 'UNCRBA22',
                'osiguravajuce_drustvo' => 'ASA Osiguranje',
                'email' => 'operations@goldenline.ba',
                'phone' => '+387 61 101 101',
                'address' => 'Zmaja od Bosne 12',
                'city' => 'Sarajevo',
                'zip' => '71000',
            ],
            [
                'company_name' => 'Adria Charter Group',
                'company_id' => '4561237800099',
                'maticni_broj_subjekta_upisa' => '000-2-Reg-18-000212',
                'pdv' => '561237800099',
                'trn' => '199870001234567',
                'banka' => 'Raiffeisen Bank',
                'iban' => 'BA391610000123456789',
                'swift' => 'RZBABA2S',
                'osiguravajuce_drustvo' => 'Triglav Osiguranje',
                'email' => 'sales@adriacharter.ba',
                'phone' => '+387 62 202 202',
                'address' => 'Maršala Tita 44',
                'city' => 'Mostar',
                'zip' => '88000',
            ],
            [
                'company_name' => 'Balkan Incoming Solutions',
                'company_id' => '4793300112233',
                'maticni_broj_subjekta_upisa' => '000-3-Reg-10-000303',
                'pdv' => '793300112233',
                'trn' => '154430987650001',
                'banka' => 'NLB Banka',
                'iban' => 'BA391540001234567890',
                'swift' => 'TBTUBA22',
                'osiguravajuce_drustvo' => 'Sarajevo Osiguranje',
                'email' => 'contact@balkanincoming.ba',
                'phone' => '+387 63 303 303',
                'address' => 'Kralja Tvrtka 8',
                'city' => 'Tuzla',
                'zip' => '75000',
            ],
        ];

        foreach ($suppliers as $payload) {
            $record = Supplier::query()->firstOrNew([
                'company_name' => $payload['company_name'],
            ]);

            if (Schema::hasColumn('suppliers', 'created_by') && ! $record->exists) {
                $payload['created_by'] = $actorId;
            }

            if (Schema::hasColumn('suppliers', 'updated_by')) {
                $payload['updated_by'] = $actorId;
            }

            $record->fill($payload);
            $record->save();
        }
    }
}
