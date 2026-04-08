<?php

namespace App\Support;

final class PermissionRegistry
{
    /**
     * Return the full permission catalog.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            // Dashboard
            'pregled dashboarda',

            // Audit changes
            'pregled izmjena',

            // Users
            'pregled korisnika',
            'dodavanje korisnika',
            'uređivanje korisnika',
            'brisanje korisnika',

            // Roles
            'pregled rola',
            'dodavanje rola',
            'uređivanje rola',
            'brisanje rola',

            // Arrangements
            'pregled aranžmana',
            'dodavanje aranžmana',
            'uređivanje aranžmana',
            'brisanje aranžmana',

            // Packages
            'pregled paketa',
            'dodavanje paketa',
            'uređivanje paketa',
            'brisanje paketa',

            // Clients
            'pregled klijenata',
            'dodavanje klijenata',
            'uređivanje klijenata',
            'brisanje klijenata',

            // Suppliers
            'pregled dobavljača',
            'dodavanje dobavljača',
            'uređivanje dobavljača',
            'brisanje dobavljača',

            // Reservations
            'pregled rezervacija',
            'dodavanje rezervacija',
            'uređivanje rezervacija',
            'brisanje rezervacija',

            // Contracts
            'pregled ugovora',
            'dodavanje ugovora',
            'uređivanje ugovora',
            'brisanje ugovora',
            'generisanje ugovora',
            'preuzimanje ugovora',
            'slanje ugovora',

            // Reservation installments and invoices
            'pregled rata rezervacija',
            'dodavanje rata rezervacija',
            'uređivanje rata rezervacija',
            'brisanje rata rezervacija',
            'generisanje računa',
            'generisanje predračuna',
            'generisanje računa rata',

            // Reports
            'pregled izvještaja',
            'izvoz izvještaja',

            // Company settings
            'pregled postavki kompanije',
            'uređivanje postavki kompanije',

            // Personal profile and security
            'pregled ličnog profila',
            'uređivanje ličnog profila',
            'brisanje ličnog profila',
            'pregled sigurnosnih postavki',
            'uređivanje sigurnosnih postavki',
        ];
    }

    /**
     * Return default admin permissions.
     *
     * @return list<string>
     */
    public static function adminDefault(): array
    {
        return self::all();
    }

    /**
     * Return default agent permissions.
     *
     * @return list<string>
     */
    public static function agentDefault(): array
    {
        return [
            'pregled dashboarda',
            'pregled aranžmana',
            'pregled paketa',
            'pregled klijenata',
            'dodavanje klijenata',
            'uređivanje klijenata',
            'pregled dobavljača',
            'dodavanje dobavljača',
            'uređivanje dobavljača',
            'pregled rezervacija',
            'dodavanje rezervacija',
            'uređivanje rezervacija',
            'pregled ugovora',
            'dodavanje ugovora',
            'generisanje ugovora',
            'preuzimanje ugovora',
            'slanje ugovora',
            'pregled rata rezervacija',
            'dodavanje rata rezervacija',
            'uređivanje rata rezervacija',
            'generisanje računa',
            'generisanje predračuna',
            'generisanje računa rata',
            'pregled izvještaja',
            'izvoz izvještaja',
            'pregled ličnog profila',
            'uređivanje ličnog profila',
            'brisanje ličnog profila',
            'pregled sigurnosnih postavki',
            'uređivanje sigurnosnih postavki',
        ];
    }
}
