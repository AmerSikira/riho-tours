# Progress

## 2026-03-13
- Pročitana pravila iz `rules.md`.
- Nije pronađen `tasks.md`; kreiran je novi backlog prema `scope.md`.
- Završena arhitektura sistema (Task 1) u `docs/01-system-architecture.md`.
- Završena analiza zahtjeva i razrada modula (Task 2) u `docs/02-requirements-analysis-and-modules.md`.
- Završen dizajn baze podataka (Task 3) u `docs/03-database-design.md`.
- Instaliran `spatie/laravel-permission` paket, migrirane permission tabele i kreirane osnovne role: `admin`, `agent`.
- Dodan seeder za default korisnike: `user1@user.com` (admin) i `user2@user.com` (agent), sa lozinkom `Amer123#!`.
- Implementiran modul `Upravljanje korisnicima`: sidebar stavka, ruta `/korisnici` sa pretragom po imenu, ruta `/korisnici/dodaj` sa formom za kreiranje korisnika i dodjelu uloge.
- Dodan status korisnika (`Aktivan` / `Neaktivan`) i login blokada za neaktivne korisnike.
- U tabelu korisnika dodane akcije preko menija na tri tačke: `Urediti`, `Aktivirati/Deaktivirati`, divider, `Obrisati`.
- Dodan modul `Dozvole`: sidebar stavka, tabela dozvola, dodavanje nove dozvole i uređivanje dozvole sa checkboxovima za role.
- Dodan seed početnih dozvola i mapiranje pristupa (`admin` sve, `agent` samo `uređivanje ličnog profila`), uz zaštitu da se rola ne može obrisati dok ima dodijeljene korisnike.
- Uveden globalni confirm prompt prije svakog `DELETE` zahtjeva u aplikaciji (i za `router.delete` i za delete forme).
- Koncept promijenjen sa `Dozvole` na `Uloge`: sada se role kreiraju/uređuju i njima se dodjeljuju dozvole putem checkboxova.
- Implementiran modul `Aranžmani` (lista, pretraga, kreiranje, uređivanje i soft delete) sa poljima prema opisu iz `scope.md/tasks.md`.
- Svaki aranžman sada podržava više paketa u odvojenoj tabeli `aranzman_paketi` sa zasebnim CRUD ekranima po aranžmanu.
- Sljedeći korak: RBAC sistem (Task 4).
- Migracija glavnih entiteta na UUID ključeve: `users`, `aranzmani`, `aranzman_paketi`, `aranzman_slike`, `rezervacije`, `klijenti`, `rezervacija_klijenti`, `settings`, uz `HasUuids` na modelima i UUID foreign ključeve u migracijama.
- Spatie pivoti za korisničke role/dozvole ažurirani da koriste UUID morph ključ (`model_uuid`) kompatibilan sa UUID `users.id`.
- Uveden soft delete kao standard za preostale delete-flow entitete: `users`, `roles`, `permissions`, `settings`, `rezervacija_klijenti`; dodani `SoftDeletes` traitovi, migracija za `deleted_at`, i ažurirana logika sync-a klijenata u rezervacijama da radi bez hard delete.
- Dodan audit trail sistem prema industrijskoj praksi: nova tabela `audit_logs`, servis i trait za automatsko logovanje `created/updated/deleted/restored` događaja (ko, šta, kada + diff starih/novih vrijednosti + request context).
- Dodana nova sekcija `Izmjene` (`/izmjene`) u sidebaru sa pretragom po korisniku/lokaciji/datumu i expandable redovima za potpune detalje audit zapisa.
- Dodani snapshot seederi za kompletan domen (`aranžmani`, `paketi`, `klijenti`, `rezervacije`, `rezervacija_klijenti`) i posebni snapshot seeder za `company settings` sa vrijednostima kopiranim iz trenutne baze.
- Dodana paginacija na `Izmjene` stranici (backend `paginate` + frontend navigacija `Prethodna/Sljedeća`) uz zadržavanje aktivnih filtera.
- Seederi prošireni na veliki dataset: višestruki aranžmani, više paketa po aranžmanu, 60+ klijenata i 40+ rezervacija sa stavkama, uz zadržan originalni snapshot unos (`hehe`) i postojeće company settings podatke.
- Redizajniran korisnički profil ekran na ruti `/korisnici/{id}` u social-style formatu (hero header, profil kartice, sekcioniran edit), uz zadržanu kompatibilnost stare rute `/korisnici/{id}/uredi`.
