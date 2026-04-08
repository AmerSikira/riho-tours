import { Head, Link, useForm } from '@inertiajs/react';
import { BadgeCheck, CalendarDays, Mail, Save, ShieldCheck, Upload, UserRound } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { formatDateDisplay } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type UserPayload = {
    id: string;
    name: string;
    email: string;
    role: string | null;
    is_active: boolean;
    created_at: string | null;
    email_verified_at: string | null;
    potpis_url: string | null;
    pecat_url: string | null;
};

type Props = {
    user: UserPayload;
    roles: string[];
};

export default function EditUser({ user, roles }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Upravljanje korisnicima',
            href: '/korisnici',
        },
        {
            title: 'Uredite korisnika',
            href: `/korisnici/${user.id}`,
        },
    ];
    const getInitials = useInitials();

    const { data, setData, patch, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        role: user.role ?? (roles[0] ?? ''),
        is_active: user.is_active ? '1' : '0',
        potpis: null as File | null,
        pecat: null as File | null,
        potpis_url: user.potpis_url,
        pecat_url: user.pecat_url,
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // Persist updated profile and role data for the selected user.
        patch(`/korisnici/${user.id}`, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Uredite korisnika" />

            <div className="flex h-full w-full max-w-6xl flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Profil korisnika</h1>
                        <p className="text-sm text-muted-foreground">
                            Uredite nalog kroz prošireni profil korisnika.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/korisnici">Nazad na listu</Link>
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="mx-auto w-full max-w-4xl space-y-6"
                >
                    <section className="relative overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-sm">
                        <div className="h-32 bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600" />
                        <div className="absolute top-20 left-6 rounded-full border-4 border-background bg-card shadow-md">
                            <Avatar className="size-24 rounded-full">
                                <AvatarFallback className="rounded-full bg-slate-900 text-2xl font-semibold text-white">
                                    {getInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                        </div>
                        <div className="space-y-3 px-6 pt-16 pb-5">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p className="text-2xl font-semibold">{user.name}</p>
                                    <p className="text-sm text-muted-foreground">{user.email}</p>
                                </div>
                                <div className="rounded-full border border-sidebar-border/70 bg-muted/40 px-3 py-1 text-xs font-medium">
                                    {user.is_active ? 'Aktivan nalog' : 'Neaktivan nalog'}
                                </div>
                            </div>

                            <div className="grid gap-2 sm:grid-cols-3">
                                <div className="rounded-lg border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-xs">
                                    <p className="mb-1 inline-flex items-center gap-1 font-medium">
                                        <UserRound className="size-3.5" />
                                        ID korisnika
                                    </p>
                                    <p className="truncate text-muted-foreground">{user.id}</p>
                                </div>
                                <div className="rounded-lg border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-xs">
                                    <p className="mb-1 inline-flex items-center gap-1 font-medium">
                                        <CalendarDays className="size-3.5" />
                                        Kreiran
                                    </p>
                                    <p className="text-muted-foreground">{formatDateDisplay(user.created_at)}</p>
                                </div>
                                <div className="rounded-lg border border-sidebar-border/70 bg-muted/20 px-3 py-2 text-xs">
                                    <p className="mb-1 inline-flex items-center gap-1 font-medium">
                                        <BadgeCheck className="size-3.5" />
                                        Email verifikacija
                                    </p>
                                    <p className="text-muted-foreground">
                                        {user.email_verified_at ? 'Verifikovan' : 'Nije verifikovan'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="space-y-4 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                        <h2 className="text-base font-semibold">Osnovni podaci</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Ime i prezime</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    placeholder="Unesite ime i prezime"
                                    autoComplete="name"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email adresa</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    placeholder="korisnik@firma.ba"
                                    autoComplete="email"
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-4 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                        <h2 className="text-base font-semibold">Pristup i status</h2>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="role">RoleRow</Label>
                                <select
                                    id="role"
                                    value={data.role}
                                    onChange={(event) => setData('role', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                    required
                                >
                                    {roles.map((role) => (
                                        <option key={role} value={role}>
                                            {role}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.role} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="is_active">Status korisnika</Label>
                                <select
                                    id="is_active"
                                    value={data.is_active}
                                    onChange={(event) => setData('is_active', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                    required
                                >
                                    <option value="1">Aktivan</option>
                                    <option value="0">Neaktivan</option>
                                </select>
                                <InputError message={errors.is_active} />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-4 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                        <h2 className="text-base font-semibold">Potpis i pečat</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="rounded-xl border border-sidebar-border/70 bg-muted/20 p-4">
                                <Label htmlFor="potpis" className="mb-2 block">
                                    Potpis
                                </Label>
                                <div className="mb-3 inline-flex items-center gap-2 text-xs text-muted-foreground">
                                    <Upload className="size-3.5" />
                                    PNG ili JPG format
                                </div>
                                <Input
                                    id="potpis"
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) => {
                                        setData('potpis', event.target.files?.[0] ?? null);
                                        setData('potpis_url', null);
                                    }}
                                />
                                <InputError className="mt-2" message={errors.potpis} />
                                <div className="mt-3 rounded-md border border-dashed border-sidebar-border/70 bg-white p-3">
                                    {data.potpis_url ? (
                                        <img
                                            src={data.potpis_url}
                                            alt="Potpis korisnika"
                                            className="h-20 w-full object-contain"
                                        />
                                    ) : (
                                        <p className="text-xs text-muted-foreground">Nema učitanog potpisa.</p>
                                    )}
                                </div>
                            </div>

                            <div className="rounded-xl border border-sidebar-border/70 bg-muted/20 p-4">
                                <Label htmlFor="pecat" className="mb-2 block">
                                    Pečat
                                </Label>
                                <div className="mb-3 inline-flex items-center gap-2 text-xs text-muted-foreground">
                                    <Upload className="size-3.5" />
                                    PNG ili JPG format
                                </div>
                                <Input
                                    id="pecat"
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) => {
                                        setData('pecat', event.target.files?.[0] ?? null);
                                        setData('pecat_url', null);
                                    }}
                                />
                                <InputError className="mt-2" message={errors.pecat} />
                                <div className="mt-3 rounded-md border border-dashed border-sidebar-border/70 bg-white p-3">
                                    {data.pecat_url ? (
                                        <img
                                            src={data.pecat_url}
                                            alt="Pečat korisnika"
                                            className="h-24 w-full object-contain"
                                        />
                                    ) : (
                                        <p className="text-xs text-muted-foreground">Nema učitanog pečata.</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="space-y-4 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                        <h2 className="text-base font-semibold">Promjena lozinke</h2>
                        <p className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                            <ShieldCheck className="size-3.5" />
                            Ostavite prazno ako ne želite mijenjati lozinku.
                        </p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="password">Nova lozinka</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) => setData('password', event.target.value)}
                                    placeholder="Unesite novu lozinku"
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">Potvrda lozinke</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(event) => setData('password_confirmation', event.target.value)}
                                    placeholder="Ponovite novu lozinku"
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.password_confirmation} />
                            </div>
                        </div>
                    </section>

                    <div className="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm">
                        <p className="inline-flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail className="size-4" />
                            Podaci će biti sačuvani odmah nakon potvrde.
                        </p>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 size-4" />
                            Sačuvajte izmjene
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
