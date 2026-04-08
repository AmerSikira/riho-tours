<?php

use App\Http\Controllers\Arrangements\ArrangementPackagesController;
use App\Http\Controllers\Arrangements\ArrangementsController;
use App\Http\Controllers\Arrangements\PackagesController;
use App\Http\Controllers\Changes\ChangesController;
use App\Http\Controllers\Clients\ClientsController;
use App\Http\Controllers\Contracts\ContractsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\Reservations\ReservationsController;
use App\Http\Controllers\Roles\RolesController;
use App\Http\Controllers\Settings\CompanySettingsController;
use App\Http\Controllers\Suppliers\SuppliersController;
use App\Http\Controllers\Users\UsersController;
use App\Http\Controllers\WebReservations\WebReservationsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');
Route::get('javni/ugovor/{rezervacija}/pdf', [ContractsController::class, 'publicPdf'])
    ->middleware('signed:relative')
    ->name('javni.ugovor.pdf');
Route::get('javni/finansijski-dokumenti/{rezervacija}/{tip}/pregled', [ContractsController::class, 'publicFinancialDocumentPreview'])
    ->middleware('signed:relative')
    ->name('javni.finansijski-dokumenti.pregled');
Route::get('javni/finansijski-dokumenti/{rezervacija}/{tip}/{rata}/pregled', [ContractsController::class, 'publicFinancialDocumentPreview'])
    ->middleware('signed:relative')
    ->name('javni.finansijski-dokumenti.rata.pregled');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:pregled dashboarda')
        ->name('dashboard');
    Route::get('izmjene', [ChangesController::class, 'index'])
        ->middleware('permission:pregled izmjena')
        ->name('izmjene.index');
    Route::get('izvjestaji', [ReportsController::class, 'index'])
        ->middleware('permission:pregled izvještaja')
        ->name('izvjestaji.index');
    Route::get('izvjestaji/izvoz', [ReportsController::class, 'export'])
        ->middleware('permission:izvoz izvještaja')
        ->name('izvjestaji.export');
    Route::get('aranzmani', [ArrangementsController::class, 'index'])
        ->middleware('permission:pregled aranžmana')
        ->name('aranzmani.index');
    Route::get('aranzmani/dodaj', [ArrangementsController::class, 'create'])
        ->middleware('permission:dodavanje aranžmana')
        ->name('aranzmani.create');
    Route::post('aranzmani', [ArrangementsController::class, 'store'])
        ->middleware('permission:dodavanje aranžmana')
        ->name('aranzmani.store');
    Route::get('aranzmani/{aranzman}/uredi', [ArrangementsController::class, 'edit'])
        ->middleware('permission:uređivanje aranžmana')
        ->name('aranzmani.edit');
    Route::patch('aranzmani/{aranzman}', [ArrangementsController::class, 'update'])
        ->middleware('permission:uređivanje aranžmana')
        ->name('aranzmani.update');
    Route::delete('aranzmani/{aranzman}', [ArrangementsController::class, 'destroy'])
        ->middleware('permission:brisanje aranžmana')
        ->name('aranzmani.destroy');
    Route::get('aranzmani/{aranzman}/paketi', [ArrangementPackagesController::class, 'index'])
        ->middleware('permission:pregled paketa')
        ->name('aranzmani.paketi.index');
    Route::get('aranzmani/{aranzman}/paketi/dodaj', [ArrangementPackagesController::class, 'create'])
        ->middleware('permission:dodavanje paketa')
        ->name('aranzmani.paketi.create');
    Route::post('aranzmani/{aranzman}/paketi', [ArrangementPackagesController::class, 'store'])
        ->middleware('permission:dodavanje paketa')
        ->name('aranzmani.paketi.store');
    Route::get('aranzmani/{aranzman}/paketi/{paket}/uredi', [ArrangementPackagesController::class, 'edit'])
        ->middleware('permission:uređivanje paketa')
        ->name('aranzmani.paketi.edit');
    Route::patch('aranzmani/{aranzman}/paketi/{paket}', [ArrangementPackagesController::class, 'update'])
        ->middleware('permission:uređivanje paketa')
        ->name('aranzmani.paketi.update');
    Route::delete('aranzmani/{aranzman}/paketi/{paket}', [ArrangementPackagesController::class, 'destroy'])
        ->middleware('permission:brisanje paketa')
        ->name('aranzmani.paketi.destroy');
    Route::get('paketi', [PackagesController::class, 'index'])
        ->middleware('permission:pregled paketa')
        ->name('paketi.index');
    Route::get('paketi/{paket}', [PackagesController::class, 'show'])
        ->middleware('permission:pregled paketa')
        ->name('paketi.show');
    Route::get('klijenti', [ClientsController::class, 'index'])
        ->middleware('permission:pregled klijenata')
        ->name('klijenti.index');
    Route::get('klijenti/dodaj', [ClientsController::class, 'create'])
        ->middleware('permission:dodavanje klijenata')
        ->name('klijenti.create');
    Route::post('klijenti', [ClientsController::class, 'store'])
        ->middleware('permission:dodavanje klijenata')
        ->name('klijenti.store');
    Route::get('klijenti/pretraga', [ClientsController::class, 'search'])
        ->middleware('permission:pregled klijenata')
        ->name('klijenti.search');
    Route::get('klijent/{klijent}', [ClientsController::class, 'show'])
        ->middleware('permission:pregled klijenata')
        ->name('klijent.show');
    Route::get('klijenti/{klijent}/uredi', [ClientsController::class, 'edit'])
        ->middleware('permission:uređivanje klijenata')
        ->name('klijenti.edit');
    Route::patch('klijenti/{klijent}', [ClientsController::class, 'update'])
        ->middleware('permission:uređivanje klijenata')
        ->name('klijenti.update');
    Route::delete('klijenti/{klijent}', [ClientsController::class, 'destroy'])
        ->middleware('permission:brisanje klijenata')
        ->name('klijenti.destroy');

    Route::get('dobavljaci', [SuppliersController::class, 'index'])
        ->middleware('permission:pregled dobavljača')
        ->name('dobavljaci.index');
    Route::get('dobavljaci/dodaj', [SuppliersController::class, 'create'])
        ->middleware('permission:dodavanje dobavljača')
        ->name('dobavljaci.create');
    Route::post('dobavljaci', [SuppliersController::class, 'store'])
        ->middleware('permission:dodavanje dobavljača')
        ->name('dobavljaci.store');
    Route::get('dobavljaci/{dobavljac}', [SuppliersController::class, 'show'])
        ->middleware('permission:pregled dobavljača')
        ->name('dobavljaci.show');
    Route::get('dobavljaci/{dobavljac}/uredi', [SuppliersController::class, 'edit'])
        ->middleware('permission:uređivanje dobavljača')
        ->name('dobavljaci.edit');
    Route::patch('dobavljaci/{dobavljac}', [SuppliersController::class, 'update'])
        ->middleware('permission:uređivanje dobavljača')
        ->name('dobavljaci.update');
    Route::delete('dobavljaci/{dobavljac}', [SuppliersController::class, 'destroy'])
        ->middleware('permission:brisanje dobavljača')
        ->name('dobavljaci.destroy');

    Route::get('korisnici', [UsersController::class, 'index'])
        ->middleware('permission:pregled korisnika')
        ->name('korisnici.index');
    Route::get('korisnici/dodaj', [UsersController::class, 'create'])
        ->middleware('permission:dodavanje korisnika')
        ->name('korisnici.create');
    Route::post('korisnici', [UsersController::class, 'store'])
        ->middleware('permission:dodavanje korisnika')
        ->name('korisnici.store');
    Route::get('korisnici/{korisnik}', [UsersController::class, 'show'])
        ->middleware('permission:pregled korisnika')
        ->name('korisnici.show');
    Route::get('korisnici/{korisnik}/uredi', [UsersController::class, 'edit'])
        ->middleware('permission:uređivanje korisnika')
        ->name('korisnici.edit');
    Route::patch('korisnici/{korisnik}', [UsersController::class, 'update'])
        ->middleware('permission:uređivanje korisnika')
        ->name('korisnici.update');
    Route::patch('korisnici/{korisnik}/status', [UsersController::class, 'toggleStatus'])
        ->middleware('permission:uređivanje korisnika')
        ->name('korisnici.status');
    Route::delete('korisnici/{korisnik}', [UsersController::class, 'destroy'])
        ->middleware('permission:brisanje korisnika')
        ->name('korisnici.destroy');

    Route::get('uloge', [RolesController::class, 'index'])
        ->middleware('permission:pregled rola')
        ->name('uloge.index');
    Route::get('uloge/dodaj', [RolesController::class, 'create'])
        ->middleware('permission:dodavanje rola')
        ->name('uloge.create');
    Route::post('uloge', [RolesController::class, 'store'])
        ->middleware('permission:dodavanje rola')
        ->name('uloge.store');
    Route::get('uloge/{uloga}/uredi', [RolesController::class, 'edit'])
        ->middleware('permission:uređivanje rola')
        ->name('uloge.edit');
    Route::patch('uloge/{uloga}', [RolesController::class, 'update'])
        ->middleware('permission:uređivanje rola')
        ->name('uloge.update');
    Route::delete('uloge/{uloga}', [RolesController::class, 'destroy'])
        ->middleware('permission:brisanje rola')
        ->name('uloge.destroy');

    Route::get('rezervacije', [ReservationsController::class, 'index'])
        ->middleware('permission:pregled rezervacija')
        ->name('rezervacije.index');
    Route::get('rezervacije/aranzmani/pretraga', [ReservationsController::class, 'searchArrangements'])
        ->middleware('permission:pregled rezervacija')
        ->name('rezervacije.aranzmani.search');
    Route::get('rezervacije/izvoz/putnici', [ReservationsController::class, 'exportSelectedClients'])
        ->middleware('permission:pregled rezervacija')
        ->name('rezervacije.export.clients');
    Route::get('rezervacije/dodaj', [ReservationsController::class, 'create'])
        ->middleware('permission:dodavanje rezervacija')
        ->name('rezervacije.create');
    Route::post('rezervacije', [ReservationsController::class, 'store'])
        ->middleware('permission:dodavanje rezervacija')
        ->name('rezervacije.store');
    Route::get('rezervacije/{rezervacija}/uredi', [ReservationsController::class, 'edit'])
        ->middleware('permission:uređivanje rezervacija')
        ->name('rezervacije.edit');
    Route::patch('rezervacije/{rezervacija}', [ReservationsController::class, 'update'])
        ->middleware('permission:uređivanje rezervacija')
        ->name('rezervacije.update');
    Route::delete('rezervacije/{rezervacija}', [ReservationsController::class, 'destroy'])
        ->middleware('permission:brisanje rezervacija')
        ->name('rezervacije.destroy');
    Route::get('web-rezervacije', [WebReservationsController::class, 'index'])
        ->middleware('permission:pregled rezervacija')
        ->name('web-rezervacije.index');
    Route::get('web-rezervacije/{webRezervacija}', [WebReservationsController::class, 'show'])
        ->middleware('permission:pregled rezervacija')
        ->name('web-rezervacije.show');
    Route::post('web-rezervacije/{webRezervacija}/prebaci-u-rezervacije', [WebReservationsController::class, 'convert'])
        ->middleware('permission:dodavanje rezervacija')
        ->name('web-rezervacije.convert');
    Route::get('rezervacije/{rezervacija}/ugovor/pdf', [ContractsController::class, 'pdf'])
        ->middleware('permission:preuzimanje ugovora')
        ->name('rezervacije.ugovor.pdf');
    Route::get('rezervacije/{rezervacija}/finansijski-dokumenti/{tip}/pregled', [ContractsController::class, 'financialDocumentPreview'])
        ->middleware('permission:preuzimanje ugovora')
        ->name('rezervacije.finansijski-dokumenti.pregled');
    Route::get('rezervacije/{rezervacija}/finansijski-dokumenti/{tip}/{rata}/pregled', [ContractsController::class, 'financialDocumentPreview'])
        ->middleware('permission:preuzimanje ugovora')
        ->name('rezervacije.finansijski-dokumenti.rata.pregled');
    Route::post('rezervacije/{rezervacija}/ugovor/generisi', [ContractsController::class, 'generate'])
        ->middleware('permission:generisanje ugovora')
        ->name('rezervacije.ugovor.generisi');

    Route::get('ugovori/predlosci', [ContractsController::class, 'templatesIndex'])
        ->middleware('permission:pregled ugovora')
        ->name('ugovori.predlosci.index');
    Route::post('ugovori/predlosci', [ContractsController::class, 'storeTemplate'])
        ->middleware('permission:dodavanje ugovora')
        ->name('ugovori.predlosci.store');
    Route::get('ugovori/predlosci/{predlozak}/uredi', [ContractsController::class, 'editTemplate'])
        ->middleware('permission:uređivanje ugovora')
        ->name('ugovori.predlosci.edit');
    Route::patch('ugovori/predlosci/{predlozak}', [ContractsController::class, 'updateTemplate'])
        ->middleware('permission:uređivanje ugovora')
        ->name('ugovori.predlosci.update');
    Route::post('ugovori/predlosci/pregled', [ContractsController::class, 'previewTemplate'])
        ->middleware('permission:pregled ugovora')
        ->name('ugovori.predlosci.preview');

    Route::get('postavke', [CompanySettingsController::class, 'edit'])
        ->middleware('permission:pregled postavki kompanije')
        ->name('company-settings.edit');
    Route::post('postavke', [CompanySettingsController::class, 'update'])
        ->middleware('permission:uređivanje postavki kompanije')
        ->name('company-settings.update');
});

require __DIR__.'/settings.php';
