import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Camera, CheckCircle2, IdCard, Mail, ShieldCheck, Upload } from 'lucide-react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Postavke profila',
        href: edit(),
    },
];

export default function Profile({
    mustVerifyEmail,
    potpisUrl,
    pecatUrl,
    status,
}: {
    mustVerifyEmail: boolean;
    potpisUrl: string | null;
    pecatUrl: string | null;
    status?: string;
}) {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Postavke profila" />

            <h1 className="sr-only">Postavke profila</h1>

            <SettingsLayout>
                <div className="space-y-8">
                    <Heading
                        variant="small"
                        title="Profil"
                        description="Uredite lične podatke, potpis i pečat"
                    />

                    <section className="relative overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-sm">
                        <div className="h-28 bg-gradient-to-r from-sky-600 via-cyan-500 to-emerald-500" />
                        <div className="absolute top-20 left-6 rounded-full border-4 border-white bg-card shadow-md">
                            <Avatar className="size-20 rounded-full">
                                <AvatarFallback className="rounded-full bg-slate-900 text-xl font-semibold text-white">
                                    {getInitials(auth.user.name)}
                                </AvatarFallback>
                            </Avatar>
                        </div>
                        <div className="space-y-3 px-6 pt-14 pb-5">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p className="text-xl font-semibold">{auth.user.name}</p>
                                    <p className="text-sm text-muted-foreground">{auth.user.email}</p>
                                </div>
                                <div
                                    className={cn(
                                        'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium',
                                        auth.user.email_verified_at
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-amber-200 bg-amber-50 text-amber-700',
                                    )}
                                >
                                    {auth.user.email_verified_at ? (
                                        <CheckCircle2 className="size-3.5" />
                                    ) : (
                                        <Mail className="size-3.5" />
                                    )}
                                    {auth.user.email_verified_at ? 'Email verifikovan' : 'Email nije verifikovan'}
                                </div>
                            </div>

                            <div className="grid gap-2 text-xs text-muted-foreground sm:grid-cols-3">
                                <div className="rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2">
                                    <p className="mb-1 inline-flex items-center gap-1.5 font-medium text-foreground">
                                        <IdCard className="size-3.5" />
                                        Korisnički ID
                                    </p>
                                    <p className="truncate">{String(auth.user.id)}</p>
                                </div>
                                <div className="rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2">
                                    <p className="mb-1 inline-flex items-center gap-1.5 font-medium text-foreground">
                                        <ShieldCheck className="size-3.5" />
                                        Status naloga
                                    </p>
                                    <p>Aktivan</p>
                                </div>
                                <div className="rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2">
                                    <p className="mb-1 inline-flex items-center gap-1.5 font-medium text-foreground">
                                        <Camera className="size-3.5" />
                                        Potpis i pečat
                                    </p>
                                    <p>{potpisUrl || pecatUrl ? 'Djelimično / potpuno dodano' : 'Nije dodano'}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                            forceFormData: true,
                        }}
                        encType="multipart/form-data"
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <section className="space-y-4 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                                    <h2 className="text-base font-semibold">Osnovni podaci</h2>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Ime i prezime</Label>

                                            <Input
                                                id="name"
                                                className="mt-1 block w-full"
                                                defaultValue={auth.user.name}
                                                name="name"
                                                required
                                                autoComplete="name"
                                                placeholder="Unesite ime i prezime"
                                            />

                                            <InputError className="mt-2" message={errors.name} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Email adresa</Label>

                                            <Input
                                                id="email"
                                                type="email"
                                                className="mt-1 block w-full"
                                                defaultValue={auth.user.email}
                                                name="email"
                                                required
                                                autoComplete="username"
                                                placeholder="Unesite email adresu"
                                            />

                                            <InputError className="mt-2" message={errors.email} />
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
                                            <Input id="potpis" type="file" name="potpis" accept="image/*" />
                                            <InputError className="mt-2" message={errors.potpis} />
                                            <div className="mt-3 rounded-md border border-dashed border-sidebar-border/70 bg-white p-3">
                                                {potpisUrl ? (
                                                    <img
                                                        src={potpisUrl}
                                                        alt="Potpis korisnika"
                                                        className="h-20 w-full object-contain"
                                                    />
                                                ) : (
                                                    <p className="text-xs text-muted-foreground">
                                                        Nema učitanog potpisa.
                                                    </p>
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
                                            <Input id="pecat" type="file" name="pecat" accept="image/*" />
                                            <InputError className="mt-2" message={errors.pecat} />
                                            <div className="mt-3 rounded-md border border-dashed border-sidebar-border/70 bg-white p-3">
                                                {pecatUrl ? (
                                                    <img
                                                        src={pecatUrl}
                                                        alt="Pečat korisnika"
                                                        className="h-24 w-full object-contain"
                                                    />
                                                ) : (
                                                    <p className="text-xs text-muted-foreground">
                                                        Nema učitanog pečata.
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm">
                                            <p className="text-amber-800">
                                                Vaša email adresa nije verifikovana.{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="font-medium text-amber-900 underline decoration-amber-400 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current"
                                                >
                                                    Kliknite ovdje da ponovo pošaljete verifikacijski email.
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-emerald-700">
                                                    Novi verifikacijski link je poslan na vašu email adresu.
                                                </div>
                                            )}
                                        </div>
                                    )}

                                <div className="flex items-center gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 shadow-sm">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        Sačuvajte izmjene
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-emerald-700">
                                            Uspješno sačuvano
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
