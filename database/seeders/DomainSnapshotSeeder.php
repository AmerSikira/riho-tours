<?php

namespace Database\Seeders;

use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Reservation;
use App\Models\ReservationClient;
use App\Models\User;
use Illuminate\Database\Seeder;

class DomainSnapshotSeeder extends Seeder
{
    /**
     * Seed domain entities aligned with the latest schema and relationships.
     */
    public function run(): void
    {
        $actorId = User::query()->where('email', 'user1@user.com')->value('id');

        $arrangements = $this->seedArrangements($actorId);
        $clients = $this->seedClients($actorId);

        $regularTemplateId = ContractTemplate::query()
            ->active()
            ->where('subagentski_ugovor', false)
            ->value('id');

        $subagentTemplateId = ContractTemplate::query()
            ->active()
            ->where('subagentski_ugovor', true)
            ->value('id');

        $this->seedReservations(
            $arrangements,
            $clients,
            $actorId,
            $regularTemplateId,
            $subagentTemplateId,
        );
    }

    /**
     * @return array<int, array{arrangement: Arrangement, package_ids: array<int, string>}>
     */
    private function seedArrangements(?string $actorId): array
    {
        $blueprints = [
            [
                'sifra' => 'SUM-001',
                'destinacija' => 'Antalija',
                'naziv_putovanja' => 'Ljetovanje Antalija',
                'opis_putovanja' => 'Ljetni porodicni aranžman.',
                'datum_polaska' => '2027-06-10',
                'datum_povratka' => '2027-06-17',
                'trajanje_dana' => 8,
                'tip_prevoza' => 'Avion',
                'tip_smjestaja' => 'Hotel 5*',
                'napomena' => 'All inclusive opcija.',
                'is_active' => true,
                'subagentski_aranzman' => false,
                'polisa_osiguranja' => 'POL-SUM-001',
            ],
            [
                'sifra' => 'SUM-002',
                'destinacija' => 'Bodrum',
                'naziv_putovanja' => 'Bodrum More',
                'opis_putovanja' => 'More i aktivni odmor.',
                'datum_polaska' => '2027-07-01',
                'datum_povratka' => '2027-07-08',
                'trajanje_dana' => 8,
                'tip_prevoza' => 'Avion',
                'tip_smjestaja' => 'Resort',
                'napomena' => 'Pogodno za parove.',
                'is_active' => true,
                'subagentski_aranzman' => false,
                'polisa_osiguranja' => 'POL-SUM-002',
            ],
            [
                'sifra' => 'AUT-001',
                'destinacija' => 'Istanbul',
                'naziv_putovanja' => 'Istanbul City Break',
                'opis_putovanja' => 'Vikend putovanje.',
                'datum_polaska' => '2027-09-10',
                'datum_povratka' => '2027-09-14',
                'trajanje_dana' => 5,
                'tip_prevoza' => 'Avion',
                'tip_smjestaja' => 'Hotel 4*',
                'napomena' => 'Polazak iz Sarajeva.',
                'is_active' => true,
                'subagentski_aranzman' => false,
                'polisa_osiguranja' => 'POL-AUT-001',
            ],
            [
                'sifra' => 'AUT-002',
                'destinacija' => 'Barcelona',
                'naziv_putovanja' => 'Barcelona Viva',
                'opis_putovanja' => 'Kultura, hrana i arhitektura.',
                'datum_polaska' => '2027-10-03',
                'datum_povratka' => '2027-10-08',
                'trajanje_dana' => 6,
                'tip_prevoza' => 'Avion',
                'tip_smjestaja' => 'Hotel 3*',
                'napomena' => 'Fakultativni izleti.',
                'is_active' => true,
                'subagentski_aranzman' => true,
                'polisa_osiguranja' => 'POL-AUT-002',
            ],
            [
                'sifra' => 'WIN-001',
                'destinacija' => 'Jahorina',
                'naziv_putovanja' => 'Jahorina Ski Week',
                'opis_putovanja' => 'Sedmica na planini.',
                'datum_polaska' => '2028-01-12',
                'datum_povratka' => '2028-01-19',
                'trajanje_dana' => 8,
                'tip_prevoza' => 'Kombi',
                'tip_smjestaja' => 'Hotel',
                'napomena' => 'Ski skola dostupna.',
                'is_active' => true,
                'subagentski_aranzman' => true,
                'polisa_osiguranja' => 'POL-WIN-001',
            ],
        ];

        $packageBlueprints = [
            ['naziv' => 'Standard', 'opis' => 'Osnovni paket', 'cijena' => 280],
            ['naziv' => 'Comfort', 'opis' => 'Paket sa transferom', 'cijena' => 380],
            ['naziv' => 'Premium', 'opis' => 'Kompletan premium paket', 'cijena' => 540],
        ];

        $result = [];

        foreach ($blueprints as $arrangementIndex => $blueprint) {
            $arrangement = Arrangement::query()->updateOrCreate(
                ['sifra' => $blueprint['sifra']],
                [
                    ...$blueprint,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ],
            );

            $packageIds = [];

            foreach ($packageBlueprints as $packageIndex => $packageBlueprint) {
                $price = $packageBlueprint['cijena'] + ($arrangementIndex * 20) + ($packageIndex * 25);

                $package = ArrangementPackage::query()->updateOrCreate(
                    [
                        'aranzman_id' => $arrangement->id,
                        'naziv' => $packageBlueprint['naziv'],
                    ],
                    [
                        'opis' => $packageBlueprint['opis'],
                        'cijena' => $price,
                        'smjestaj_trosak' => round($price * 0.45, 2),
                        'transport_trosak' => round($price * 0.20, 2),
                        'fakultativne_stvari_trosak' => round($price * 0.10, 2),
                        'ostalo_trosak' => round($price * 0.08, 2),
                        'is_active' => true,
                        'created_by' => $actorId,
                        'updated_by' => $actorId,
                    ],
                );

                $packageIds[] = $package->id;
            }

            $result[] = [
                'arrangement' => $arrangement,
                'package_ids' => $packageIds,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, Client>
     */
    private function seedClients(?string $actorId): array
    {
        $firstNames = ['Amar', 'Emina', 'Nedim', 'Lejla', 'Faris', 'Ajla', 'Dino', 'Amina', 'Tarik', 'Selma'];
        $lastNames = ['Hodzic', 'Kovac', 'Begovic', 'Mujic', 'Becirovic', 'Halilovic'];
        $cities = ['Sarajevo', 'Kakanj', 'Zenica', 'Mostar', 'Tuzla'];

        $clients = [];

        for ($index = 0; $index < 30; $index++) {
            $firstName = $firstNames[$index % count($firstNames)];
            $lastName = $lastNames[$index % count($lastNames)];
            $city = $cities[$index % count($cities)];
            $year = 1985 + ($index % 20);
            $month = ($index % 12) + 1;
            $day = ($index % 27) + 1;

            $client = Client::query()->updateOrCreate(
                ['broj_dokumenta' => str_pad((string) (1000000000000 + $index), 13, '0', STR_PAD_LEFT)],
                [
                    'ime' => $firstName,
                    'prezime' => $lastName,
                    'datum_rodjenja' => sprintf('%04d-%02d-%02d', $year, $month, $day),
                    'adresa' => $city.' bb',
                    'city' => $city,
                    'broj_telefona' => '38761'.str_pad((string) (500000 + $index), 6, '0', STR_PAD_LEFT),
                    'email' => strtolower($firstName.'.'.$lastName.$index.'@example.com'),
                    'fotografija_putanja' => null,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ],
            );

            $clients[] = $client;
        }

        return $clients;
    }

    /**
     * @param  array<int, array{arrangement: Arrangement, package_ids: array<int, string>}>  $arrangements
     * @param  array<int, Client>  $clients
     */
    private function seedReservations(
        array $arrangements,
        array $clients,
        ?string $actorId,
        ?string $regularTemplateId,
        ?string $subagentTemplateId,
    ): void {
        $paymentModes = ['placeno', 'na_rate', 'na_odgodeno'];
        $statuses = ['na_cekanju', 'potvrdjena', 'otkazana'];

        for ($index = 1; $index <= 18; $index++) {
            $arrangementData = $arrangements[$index % count($arrangements)];
            $arrangement = $arrangementData['arrangement'];
            $packageIds = $arrangementData['package_ids'];
            $paymentMode = $paymentModes[$index % count($paymentModes)];
            $travellerCount = 1 + ($index % 3);

            $reservationClients = [];
            $clientOffset = ($index * 2) % count($clients);

            for ($clientIndex = 0; $clientIndex < $travellerCount; $clientIndex++) {
                $reservationClients[] = $clients[($clientOffset + $clientIndex) % count($clients)];
            }

            $primaryClient = $reservationClients[0];
            $fullName = collect($reservationClients)
                ->map(fn (Client $client) => trim("{$client->ime} {$client->prezime}"))
                ->implode(', ');

            $rateCount = $paymentMode === 'na_rate' ? 3 : null;
            $rateDates = $paymentMode === 'na_rate'
                ? [
                    now()->addDays(7)->toDateString(),
                    now()->addDays(14)->toDateString(),
                    now()->addDays(21)->toDateString(),
                ]
                : null;

            $templateId = $arrangement->subagentski_aranzman
                ? ($subagentTemplateId ?? $regularTemplateId)
                : ($regularTemplateId ?? $subagentTemplateId);

            $reservation = Reservation::query()->updateOrCreate(
                ['napomena' => "Seed reservation #{$index}"],
                [
                    'aranzman_id' => $arrangement->id,
                    'contract_template_id' => $templateId,
                    'klijent_id' => $primaryClient->id,
                    'ime_prezime' => $fullName,
                    'email' => $primaryClient->email,
                    'telefon' => $primaryClient->broj_telefona,
                    'broj_putnika' => $travellerCount,
                    'status' => $statuses[$index % count($statuses)],
                    'placanje' => $paymentMode,
                    'broj_rata' => $rateCount,
                    'rate' => $rateDates,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ],
            );

            $activeClientIds = [];

            foreach ($reservationClients as $clientIndex => $client) {
                $activeClientIds[] = $client->id;
                $packageId = $packageIds[$clientIndex % count($packageIds)];

                ReservationClient::query()->updateOrCreate(
                    [
                        'rezervacija_id' => $reservation->id,
                        'klijent_id' => $client->id,
                    ],
                    [
                        'paket_id' => $packageId,
                        'dodatno_na_cijenu' => (float) ($clientIndex * 20),
                        'popust' => (float) ($clientIndex === 0 ? 0 : 10),
                    ],
                );
            }

            ReservationClient::query()
                ->where('rezervacija_id', $reservation->id)
                ->whereNotIn('klijent_id', $activeClientIds)
                ->delete();
        }
    }
}
