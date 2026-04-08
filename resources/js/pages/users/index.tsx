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

type UserRow = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    role: string | null;
    created_at: string | null;
};

type Props = {
    users: {
        data: UserRow[];
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
        title: 'Upravljanje korisnicima',
        href: '/korisnici',
    },
];

export default function KorisniciIndex({ users, filters, status }: Props) {
    const [searchValue, setSearchValue] = useState(filters.pretraga ?? '');

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // Keep search state in the URL so page refresh preserves filters.
        router.get(
            '/korisnici',
            {
                pretraga: searchValue.trim() || undefined,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleToggleStatus = (user: UserRow) => {
        router.patch(`/korisnici/${user.id}/status`);
    };

    const handleDeleteUser = (user: UserRow) => {
        router.delete(`/korisnici/${user.id}`);
    };

    const goToPage = (page: number) => {
        router.get(
            '/korisnici',
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
            <Head title="Upravljanje korisnicima" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            Upravljanje korisnicima
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Pregled i pretraga korisničkih naloga.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href="/korisnici/dodaj">
                            <Plus className="mr-2 size-4" />
                            Dodajte novog korisnika
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
                        placeholder="Pretraži korisnike po imenu"
                        aria-label="Pretraži korisnike po imenu"
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
                                <th className="px-4 py-3 font-medium">Ime i prezime</th>
                                <th className="px-4 py-3 font-medium">Email</th>
                                <th className="px-4 py-3 font-medium">RoleRow</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 text-right font-medium">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.length > 0 ? (
                                users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3">{user.name}</td>
                                        <td className="px-4 py-3">{user.email}</td>
                                        <td className="px-4 py-3">
                                            {user.role ?? 'Nije dodijeljena'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant="outline"
                                                className={activeStatusBadgeClass(
                                                    user.is_active,
                                                )}
                                            >
                                                {activeStatusLabel(user.is_active)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Akcije korisnika"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`/korisnici/${user.id}`}
                                                        >
                                                            Uredite
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() =>
                                                            handleToggleStatus(
                                                                user
                                                            )
                                                        }
                                                    >
                                                        {user.is_active
                                                            ? 'Deaktivirati'
                                                            : 'Aktivirati'}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onClick={() =>
                                                            handleDeleteUser(
                                                                user
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
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Nema rezultata za zadanu pretragu.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <PaginationControls
                    currentPage={users.current_page}
                    lastPage={users.last_page}
                    total={users.total}
                    entityLabel="korisnika"
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
