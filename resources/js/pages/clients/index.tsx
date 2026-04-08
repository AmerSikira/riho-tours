import { Head, Link, router } from '@inertiajs/react';
import { MoreHorizontal, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import PaginationControls from '@/components/pagination-controls';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type Klijent = {
    id: number;
    ime: string;
    prezime: string;
    broj_dokumenta: string;
    datum_rodjenja: string | null;
    adresa: string;
    broj_telefona: string;
    email: string | null;
    fotografija_url: string | null;
};

type Props = {
    klijenti: {
        data: Klijent[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        pretraga?: string;
    };
    status?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Klijenti',
        href: '/klijenti',
    },
];

export default function ClientsIndex({ klijenti: clients, filters, status }: Props) {
    const [searchValue, setSearchValue] = useState(filters.pretraga ?? '');

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/klijenti',
            {
                pretraga: searchValue.trim() || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleDelete = (client: Klijent) => {
        if (!window.confirm('Da li ste sigurni da želite obrisati ovog klijenta?')) {
            return;
        }

        router.delete(`/klijenti/${client.id}`);
    };

    const goToPage = (page: number) => {
        router.get(
            '/klijenti',
            {
                pretraga: searchValue.trim() || undefined,
                page,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Klijenti" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Klijenti</h1>
                        <p className="text-sm text-muted-foreground">
                            Svi klijenti koji su dodani kroz rezervacije.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href="/klijenti/dodaj">
                            <Plus className="mr-2 size-4" />
                            Dodajte klijenta
                        </Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSearchSubmit}
                    className="flex w-full max-w-md items-center gap-2"
                >
                    <Input
                        value={searchValue}
                        onChange={(event) => setSearchValue(event.target.value)}
                        placeholder="Pretraži po imenu, prezimenu ili Broj dokumenta"
                        aria-label="Pretraži po imenu, prezimenu ili Broj dokumenta"
                    />
                    <Button type="submit" variant="secondary">
                        <Search className="mr-2 size-4" />
                        Pretraži
                    </Button>
                </form>

                {status && (
                    <div className="rounded-md border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                        {status}
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Klijent</th>
                                <th className="px-4 py-3 font-medium">Broj dokumenta</th>
                                <th className="px-4 py-3 font-medium">Datum rođenja</th>
                                <th className="px-4 py-3 font-medium">Kontakt</th>
                                <th className="px-4 py-3 font-medium">Adresa</th>
                                <th className="px-4 py-3 text-right font-medium">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {clients.data.length > 0 ? (
                                clients.data.map((client) => (
                                    <tr
                                        key={client.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                {client.fotografija_url ? (
                                                    <img
                                                        src={client.fotografija_url}
                                                        alt={`${client.ime} ${client.prezime}`}
                                                        className="size-10 rounded-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="flex size-10 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                                                        {`${client.ime[0] ?? ''}${
                                                            client.prezime[0] ?? ''
                                                        }`}
                                                    </div>
                                                )}

                                                <div>
                                                    <div className="font-medium">
                                                        <Link
                                                            href={`/klijent/${client.id}`}
                                                            className="hover:underline"
                                                        >
                                                            {client.ime} {client.prezime}
                                                        </Link>
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {client.email ?? 'Bez email-a'}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">{client.broj_dokumenta || '-'}</td>
                                        <td className="px-4 py-3">
                                            {formatDateDisplay(client.datum_rodjenja)}
                                        </td>
                                        <td className="px-4 py-3">
                                            {client.broj_telefona}
                                        </td>
                                        <td className="px-4 py-3">{client.adresa}</td>
                                        <td className="px-4 py-3 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Akcije klijenta"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/klijenti/${client.id}/uredi`}>
                                                            Uredite
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onClick={() => handleDelete(client)}
                                                    >
                                                        Obrišite
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Nema klijenata za prikaz.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationControls
                    currentPage={clients.current_page}
                    lastPage={clients.last_page}
                    total={clients.total}
                    entityLabel="klijenata"
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
