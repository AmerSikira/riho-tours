import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import PaginationControls from '@/components/pagination-controls';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Package = {
    id: number;
    naziv: string;
    broj_aranzmana: number;
    broj_aktivnih: number;
};

type Props = {
    paketi: {
        data: Package[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        pretraga?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Paketi',
        href: '/paketi',
    },
];

export default function PackagesIndex({ paketi: packages, filters }: Props) {
    const [searchValue, setSearchValue] = useState(filters.pretraga ?? '');

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/paketi',
            { pretraga: searchValue.trim() || undefined },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/paketi',
            { pretraga: searchValue.trim() || undefined, page },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Paketi" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">Paketi</h1>
                    <p className="text-sm text-muted-foreground">
                        Globalni pregled svih paketa i broja vezanih aranžmana.
                    </p>
                </div>

                <form
                    onSubmit={handleSearchSubmit}
                    className="flex w-full max-w-md items-center gap-2"
                >
                    <Input
                        value={searchValue}
                        onChange={(event) => setSearchValue(event.target.value)}
                        placeholder="Pretraži pakete po nazivu"
                        aria-label="Pretraži pakete po nazivu"
                    />
                    <Button type="submit" variant="secondary">
                        <Search className="mr-2 size-4" />
                        Pretraži
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Naziv paketa</th>
                                <th className="px-4 py-3 font-medium">Broj aranžmana</th>
                                <th className="px-4 py-3 font-medium">
                                    Aktivne veze
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Detalji
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {packages.data.length > 0 ? (
                                packages.data.map((packageItem) => (
                                    <tr
                                        key={packageItem.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3">{packageItem.naziv}</td>
                                        <td className="px-4 py-3">
                                            {packageItem.broj_aranzmana}
                                        </td>
                                        <td className="px-4 py-3">
                                            {packageItem.broj_aktivnih}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button variant="outline" asChild>
                                                <Link href={`/paketi/${packageItem.id}`}>
                                                    Otvori paket
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Nema paketa za prikaz.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationControls
                    currentPage={packages.current_page}
                    lastPage={packages.last_page}
                    total={packages.total}
                    entityLabel="paketa"
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
