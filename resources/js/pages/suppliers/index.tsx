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
import type { BreadcrumbItem } from '@/types';

type Supplier = {
    id: string;
    company_name: string;
    company_id: string | null;
    pdv: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    city: string | null;
};

type Props = {
    dobavljaci: {
        data: Supplier[];
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
        title: 'Dobavljači',
        href: '/dobavljaci',
    },
];

export default function SuppliersIndex({ dobavljaci: suppliers, filters, status }: Props) {
    const [searchValue, setSearchValue] = useState(filters.pretraga ?? '');

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/dobavljaci',
            {
                pretraga: searchValue.trim() || undefined,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleDelete = (supplier: Supplier) => {
        if (!window.confirm('Da li ste sigurni da želite obrisati ovog dobavljača?')) {
            return;
        }

        router.delete(`/dobavljaci/${supplier.id}`);
    };

    const goToPage = (page: number) => {
        router.get(
            '/dobavljaci',
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
            <Head title="Dobavljači" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Dobavljači</h1>
                        <p className="text-sm text-muted-foreground">
                            Kompanije za koje prodajete aranžmane.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href="/dobavljaci/dodaj">
                            <Plus className="mr-2 size-4" />
                            Dodajte dobavljača
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
                        placeholder="Pretraži po nazivu, ID ili PDV"
                        aria-label="Pretraži po nazivu, ID ili PDV"
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
                                <th className="px-4 py-3 font-medium">Naziv kompanije</th>
                                <th className="px-4 py-3 font-medium">ID / PDV</th>
                                <th className="px-4 py-3 font-medium">Kontakt</th>
                                <th className="px-4 py-3 font-medium">Adresa</th>
                                <th className="px-4 py-3 text-right font-medium">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {suppliers.data.length > 0 ? (
                                suppliers.data.map((supplier) => (
                                    <tr key={supplier.id} className="border-t border-sidebar-border/70">
                                        <td className="px-4 py-3 font-medium">
                                            {supplier.company_name}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div>{supplier.company_id || '-'}</div>
                                            <div className="text-xs text-muted-foreground">
                                                PDV: {supplier.pdv || '-'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div>{supplier.phone || '-'}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {supplier.email || 'Bez email-a'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            {[supplier.address, supplier.city]
                                                .filter(Boolean)
                                                .join(', ') || '-'}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Akcije dobavljača"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/dobavljaci/${supplier.id}`}>
                                                            Detalji
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`/dobavljaci/${supplier.id}/uredi`}
                                                        >
                                                            Uredite
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onClick={() => handleDelete(supplier)}
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
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Nema dobavljača za prikaz.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationControls
                    currentPage={suppliers.current_page}
                    lastPage={suppliers.last_page}
                    total={suppliers.total}
                    entityLabel="dobavljača"
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
