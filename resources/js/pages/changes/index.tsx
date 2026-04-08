import { Head, router } from '@inertiajs/react';
import { ChevronDown, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import PaginationControls from '@/components/pagination-controls';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type LogDetails = {
    event_key: string;
    auditable_type: string;
    auditable_id: string;
    causer_type: string | null;
    causer_id: string | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    request_context: Record<string, unknown> | null;
};

type AuditLogRow = {
    id: string;
    event: string;
    user_name: string;
    location: string;
    changed_at: string;
    details: LogDetails;
};

type Props = {
    logs: {
        data: AuditLogRow[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        pretraga?: string;
        datum_od?: string;
        datum_do?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Izmjene',
        href: '/izmjene',
    },
];

export default function ChangesIndex({ logs, filters }: Props) {
    const [searchValue, setSearchValue] = useState(filters.pretraga ?? '');
    const [dateFrom, setDateFrom] = useState(filters.datum_od ?? '');
    const [dateTo, setDateTo] = useState(filters.datum_do ?? '');

    const handleSearchSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(
            '/izmjene',
            {
                pretraga: searchValue.trim() || undefined,
                datum_od: dateFrom || undefined,
                datum_do: dateTo || undefined,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const goToPage = (page: number) => {
        router.get(
            '/izmjene',
            {
                pretraga: searchValue.trim() || undefined,
                datum_od: dateFrom || undefined,
                datum_do: dateTo || undefined,
                page,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Izmjene" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">Izmjene</h1>
                    <p className="text-sm text-muted-foreground">
                        Pregled svih promjena sa detaljima: ko, gdje i kada je nešto mijenjao.
                    </p>
                </div>

                <form onSubmit={handleSearchSubmit} className="grid gap-2 md:grid-cols-[1fr_180px_180px_auto]">
                    <Input
                        value={searchValue}
                        onChange={(event) => setSearchValue(event.target.value)}
                        placeholder="Pretraga po korisniku ili lokaciji izmjene"
                        aria-label="Pretraga po korisniku ili lokaciji izmjene"
                    />
                    <Input
                        type="date"
                        value={dateFrom}
                        onChange={(event) => setDateFrom(event.target.value)}
                        aria-label="Datum od"
                    />
                    <Input
                        type="date"
                        value={dateTo}
                        onChange={(event) => setDateTo(event.target.value)}
                        aria-label="Datum do"
                    />
                    <Button type="submit" variant="secondary">
                        <Search className="mr-2 size-4" />
                        Pretraži
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70">
                    <div className="grid grid-cols-[1.2fr_1.2fr_1fr_auto] bg-muted/40 px-4 py-3 text-sm font-medium">
                        <span>UserRow</span>
                        <span>Lokacija izmjene</span>
                        <span>Kada</span>
                        <span className="text-right">Detalji</span>
                    </div>

                    {logs.data.length > 0 ? (
                        <div className="divide-y divide-sidebar-border/70">
                            {logs.data.map((log) => (
                                <Collapsible key={log.id}>
                                    <CollapsibleTrigger asChild>
                                        <button
                                            type="button"
                                            className="grid w-full grid-cols-[1.2fr_1.2fr_1fr_auto] items-center gap-2 px-4 py-3 text-left text-sm hover:bg-muted/30"
                                        >
                                            <span>{log.user_name}</span>
                                            <span className="truncate">{log.location}</span>
                                            <span>{log.changed_at}</span>
                                            <span className="flex justify-end">
                                                <ChevronDown className="size-4" />
                                            </span>
                                        </button>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div className="space-y-3 bg-muted/20 px-4 py-4 text-sm">
                                            <div className="grid gap-2 md:grid-cols-2">
                                                <div>
                                                    <p className="font-medium">Tip događaja</p>
                                                    <p className="text-muted-foreground">{log.event}</p>
                                                </div>
                                                <div>
                                                    <p className="font-medium">Model</p>
                                                    <p className="text-muted-foreground">
                                                        {log.details.auditable_type} ({log.details.auditable_id})
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="grid gap-2 md:grid-cols-2">
                                                <div>
                                                    <p className="font-medium">Stare vrijednosti</p>
                                                    <pre className="overflow-auto rounded-md border border-sidebar-border/70 bg-card p-2 text-xs">
                                                        {JSON.stringify(log.details.old_values ?? {}, null, 2)}
                                                    </pre>
                                                </div>
                                                <div>
                                                    <p className="font-medium">Nove vrijednosti</p>
                                                    <pre className="overflow-auto rounded-md border border-sidebar-border/70 bg-card p-2 text-xs">
                                                        {JSON.stringify(log.details.new_values ?? {}, null, 2)}
                                                    </pre>
                                                </div>
                                            </div>

                                            <div>
                                                <p className="font-medium">Request context</p>
                                                <pre className="overflow-auto rounded-md border border-sidebar-border/70 bg-card p-2 text-xs">
                                                    {JSON.stringify(log.details.request_context ?? {}, null, 2)}
                                                </pre>
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </Collapsible>
                            ))}
                        </div>
                    ) : (
                        <div className="px-4 py-8 text-center text-sm text-muted-foreground">
                            Nema logova za odabrane filtere.
                        </div>
                    )}
                </div>

                <PaginationControls
                    currentPage={logs.current_page}
                    lastPage={logs.last_page}
                    total={logs.total}
                    entityLabel="zapisa"
                    onPageChange={goToPage}
                />
            </div>
        </AppLayout>
    );
}
