import { Head, Link, router } from '@inertiajs/react';
import { MoreHorizontal, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import PaginationControls from '@/components/pagination-controls';
import { Badge } from '@/components/ui/badge';
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
import { activeStatusBadgeClass, activeStatusLabel } from '@/lib/status-badge';
import type { BreadcrumbItem } from '@/types';

type Arrangement = {
    id: number;
    sifra: string;
    naziv_putovanja: string;
    destinacija: string;
    datum_polaska: string;
    datum_povratka: string;
    tip_prevoza: string;
    tip_smjestaja: string;
    is_active: boolean;
    broj_prijavljenih: number | null;
};

type Props = {
    aranzmani: {
        data: Arrangement[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        pretraga?: string;
        datum_od?: string;
        datum_do?: string;
    };
    status?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Aranžmani',
        href: '/aranzmani',
    },
];

export default function ArrangementsIndex({ aranzmani: arrangements, filters, status }: Props) {
    const [searchValue, setSearchValue] = useState(filters.pretraga ?? '');
    const [datumOd, setDatumOd] = useState(filters.datum_od ?? '');
    const [datumDo, setDatumDo] = useState(filters.datum_do ?? '');

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // Keep search filters in URL so state survives refresh.
        router.get(
            '/aranzmani',
            {
                pretraga: searchValue.trim() || undefined,
                datum_od: datumOd || undefined,
                datum_do: datumDo || undefined,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleDelete = (arrangementId: number) => {
        router.delete(`/aranzmani/${arrangementId}`);
    };

    const goToPage = (page: number) => {
        router.get(
            '/aranzmani',
            {
                pretraga: searchValue.trim() || undefined,
                datum_od: datumOd || undefined,
                datum_do: datumDo || undefined,
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
            <Head title="Aranžmani" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Aranžmani</h1>
                        <p className="text-sm text-muted-foreground">
                            Upravljanje putnim aranžmanima i osnovnim podacima.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href="/aranzmani/dodaj">
                            <Plus className="mr-2 size-4" />
                            Dodajte aranžman
                        </Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSearchSubmit}
                    className="grid w-full max-w-4xl gap-2 md:grid-cols-[1fr_180px_180px_auto]"
                >
                    <Input
                        value={searchValue}
                        onChange={(event) => setSearchValue(event.target.value)}
                        placeholder="Pretraži po nazivu aranžmana"
                        aria-label="Pretraži po nazivu aranžmana"
                    />
                    <Input
                        type="date"
                        value={datumOd}
                        onChange={(event) => setDatumOd(event.target.value)}
                        aria-label="Datum putovanja od"
                    />
                    <Input
                        type="date"
                        value={datumDo}
                        onChange={(event) => setDatumDo(event.target.value)}
                        aria-label="Datum putovanja do"
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
                                <th className="px-4 py-3 font-medium">Šifra</th>
                                <th className="px-4 py-3 font-medium">Naziv</th>
                                <th className="px-4 py-3 font-medium">Destinacija</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Prijavljeno</th>
                                <th className="px-4 py-3 font-medium">Paketi</th>
                                <th className="px-4 py-3 text-right font-medium">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {arrangements.data.length > 0 ? (
                                arrangements.data.map((aranzman) => (
                                    <tr
                                        key={aranzman.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3">{aranzman.sifra}</td>
                                        <td className="px-4 py-3">
                                            {aranzman.naziv_putovanja}
                                        </td>
                                        <td className="px-4 py-3">
                                            {aranzman.destinacija}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant="outline"
                                                className={activeStatusBadgeClass(
                                                    aranzman.is_active,
                                                )}
                                            >
                                                {activeStatusLabel(
                                                    aranzman.is_active,
                                                )}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            {aranzman.broj_prijavljenih ?? 0}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link
                                                    href={`/aranzmani/${aranzman.id}/paketi`}
                                                >
                                                    Otvori pakete
                                                </Link>
                                            </Button>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Akcije aranžmana"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`/aranzmani/${aranzman.id}/uredi`}
                                                        >
                                                            Uredite
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onClick={() =>
                                                            handleDelete(
                                                                aranzman.id
                                                            )
                                                        }
                                                    >
                                                        Obrisati
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Nema aranžmana za zadanu pretragu.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationControls
                    currentPage={arrangements.current_page}
                    lastPage={arrangements.last_page}
                    total={arrangements.total}
                    entityLabel="aranžmana"
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
