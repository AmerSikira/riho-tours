import { Head, Link, router } from '@inertiajs/react';
import { MoreHorizontal, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
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

type RoleRow = {
    id: number;
    name: string;
    users_count: number;
    permissions: string[];
};

type Props = {
    roles: RoleRow[];
    filters: {
        pretraga?: string;
    };
    status?: string;
    error?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uloge',
        href: '/uloge',
    },
];

export default function UlogeIndex({ roles, filters, status, error }: Props) {
    const [searchValue, setSearchValue] = useState(filters.pretraga ?? '');

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // Keep search query in URL so filter state stays persistent.
        router.get(
            '/uloge',
            {
                pretraga: searchValue.trim() || undefined,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleDeleteRole = (roleId: number) => {
        router.delete(`/uloge/${roleId}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Uloge" />

            <div className="mx-auto flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Uloge</h1>
                        <p className="text-sm text-muted-foreground">
                            Kreiranje i upravljanje ulogama sa checkbox dozvolama.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href="/uloge/dodaj">
                            <Plus className="mr-2 size-4" />
                            Dodajte novu ulogu
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
                        placeholder="Pretraži uloge"
                        aria-label="Pretraži uloge"
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

                {error && (
                    <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                        {error}
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Naziv uloge</th>
                                <th className="px-4 py-3 font-medium">Broj korisnika</th>
                                <th className="px-4 py-3 font-medium">Dozvole</th>
                                <th className="px-4 py-3 text-right font-medium">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            {roles.length > 0 ? (
                                roles.map((role) => (
                                    <tr
                                        key={role.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3">{role.name}</td>
                                        <td className="px-4 py-3">{role.users_count}</td>
                                        <td className="px-4 py-3">
                                            {role.permissions.length > 0
                                                ? role.permissions.join(', ')
                                                : 'Nema dozvola'}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label="Akcije uloge"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/uloge/${role.id}/uredi`}>
                                                            Uredite
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onClick={() =>
                                                            handleDeleteRole(
                                                                role.id
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
                                        colSpan={4}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Nema uloga za zadanu pretragu.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
