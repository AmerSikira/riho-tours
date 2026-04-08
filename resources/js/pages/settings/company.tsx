import { Head, useForm } from '@inertiajs/react';
import {
    KeyRound,
    BadgeInfo,
    Building2,
    Mail,
    MapPin,
    Save,
} from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Props = {
    setting: {
        company_name: string;
        invoice_prefix: string;
        company_id: string;
        maticni_broj_subjekta_upisa: string;
        pdv: string;
        u_pdv_sistemu: boolean;
        trn: string;
        broj_kase: string;
        banka: string;
        iban: string;
        swift: string;
        osiguravajuce_drustvo: string;
        polisa_osiguranja: string;
        email: string;
        phone: string;
        address: string;
        city: string;
        zip: string;
        logo_url: string | null;
        potpis_url: string | null;
        pecat_url: string | null;
        api_key_active: boolean;
        api_key_last_used_at: string | null;
        api_domain_1: string;
        api_domain_2: string;
    };
    status?: string;
    generated_api_token?: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Postavke',
        href: '/postavke',
    },
];

export default function CompanySettingsPage({ setting, status, generated_api_token }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        company_name: setting.company_name,
        invoice_prefix: setting.invoice_prefix,
        company_id: setting.company_id,
        maticni_broj_subjekta_upisa: setting.maticni_broj_subjekta_upisa,
        pdv: setting.pdv,
        u_pdv_sistemu: setting.u_pdv_sistemu ? '1' : '0',
        trn: setting.trn,
        broj_kase: setting.broj_kase,
        banka: setting.banka,
        iban: setting.iban,
        swift: setting.swift,
        osiguravajuce_drustvo: setting.osiguravajuce_drustvo,
        polisa_osiguranja: setting.polisa_osiguranja,
        email: setting.email,
        phone: setting.phone,
        address: setting.address,
        city: setting.city,
        zip: setting.zip,
        logo: null as File | null,
        logo_url: setting.logo_url,
        potpis: null as File | null,
        potpis_url: setting.potpis_url,
        pecat: null as File | null,
        pecat_url: setting.pecat_url,
        regenerate_api_token: false,
        api_domain_1: setting.api_domain_1 ?? '',
        api_domain_2: setting.api_domain_2 ?? '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/postavke', {
            forceFormData: true,
        });
    };

    const initials = (data.company_name || 'Kompanija')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Postavke" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">Postavke kompanije</h1>
                    <p className="text-sm text-muted-foreground">
                        Uredite podatke firme koji se koriste kroz predračune, rezervacije i
                        poslovnu dokumentaciju.
                    </p>
                </div>

                {status && (
                    <div className="rounded-md border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                        {status}
                    </div>
                )}
                {generated_api_token && (
                    <div className="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <div className="font-semibold">API ključ je generisan.</div>
                        <div className="mt-1 break-all font-mono text-xs">{generated_api_token}</div>
                        <div className="mt-1 text-xs text-amber-700">
                            Prikaže se samo jednom. Sačuvajte ga odmah.
                        </div>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="grid gap-5 lg:grid-cols-[320px_1fr]">
                    <aside className="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm lg:sticky lg:top-4 lg:h-fit">
                        <div className="flex flex-col items-center text-center">
                            <div className="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-4 border-sky-100 bg-muted text-2xl font-semibold text-sky-700">
                                {data.logo_url ? (
                                    <img
                                        src={data.logo_url}
                                        alt="Logo kompanije"
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    initials || 'K'
                                )}
                            </div>
                            <h2 className="mt-4 text-lg font-semibold">
                                {data.company_name || 'Naziv kompanije'}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Postavke poslovnog računa
                            </p>
                        </div>

                        <div className="mt-5 space-y-2 rounded-xl bg-muted/40 p-3">
                            <Label htmlFor="logo">Logo kompanije</Label>
                            <Input
                                id="logo"
                                type="file"
                                accept="image/*"
                                onChange={(event) =>
                                    setData('logo', event.target.files?.[0] ?? null)
                                }
                            />
                            <InputError message={errors.logo} />
                        </div>

                        <div className="mt-3 space-y-2 rounded-xl bg-muted/40 p-3">
                            <Label htmlFor="potpis">Potpis</Label>
                            <Input
                                id="potpis"
                                type="file"
                                accept="image/*"
                                onChange={(event) =>
                                    setData('potpis', event.target.files?.[0] ?? null)
                                }
                            />
                            <InputError message={errors.potpis} />
                            {data.potpis_url && (
                                <img
                                    src={data.potpis_url}
                                    alt="Potpis"
                                    className="h-16 w-full rounded-md border object-contain bg-white p-1"
                                />
                            )}
                        </div>

                        <div className="mt-3 space-y-2 rounded-xl bg-muted/40 p-3">
                            <Label htmlFor="pecat">Pečat</Label>
                            <Input
                                id="pecat"
                                type="file"
                                accept="image/*"
                                onChange={(event) =>
                                    setData('pecat', event.target.files?.[0] ?? null)
                                }
                            />
                            <InputError message={errors.pecat} />
                            {data.pecat_url && (
                                <img
                                    src={data.pecat_url}
                                    alt="Pečat"
                                    className="h-24 w-full rounded-md border object-contain bg-white p-1"
                                />
                            )}
                        </div>
                    </aside>

                    <div className="space-y-5">
                        <section className="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <Building2 className="size-4 text-sky-600" />
                                <h3 className="font-semibold">Osnovne informacije</h3>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="company_name">Naziv kompanije</Label>
                                    <Input
                                        id="company_name"
                                        value={data.company_name}
                                        onChange={(event) =>
                                            setData('company_name', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.company_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="company_id">ID</Label>
                                    <Input
                                        id="company_id"
                                        value={data.company_id}
                                        onChange={(event) =>
                                            setData('company_id', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.company_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="invoice_prefix">Prefiks broja računa</Label>
                                    <Input
                                        id="invoice_prefix"
                                        value={data.invoice_prefix}
                                        placeholder="WEB"
                                        onChange={(event) =>
                                            setData('invoice_prefix', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.invoice_prefix} />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="maticni_broj_subjekta_upisa">
                                        Matični broj subjekta upisa
                                    </Label>
                                    <Input
                                        id="maticni_broj_subjekta_upisa"
                                        value={data.maticni_broj_subjekta_upisa}
                                        placeholder="000-0-Reg-00-000000"
                                        onChange={(event) =>
                                            setData(
                                                'maticni_broj_subjekta_upisa',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            errors.maticni_broj_subjekta_upisa
                                        }
                                    />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <BadgeInfo className="size-4 text-sky-600" />
                                <h3 className="font-semibold">Porezni podaci</h3>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="pdv">PDV</Label>
                                    <Input
                                        id="pdv"
                                        value={data.pdv}
                                        onChange={(event) => setData('pdv', event.target.value)}
                                    />
                                    <InputError message={errors.pdv} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>U PDV sistemu</Label>
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked={data.u_pdv_sistemu === '1'}
                                        onClick={() =>
                                            setData(
                                                'u_pdv_sistemu',
                                                data.u_pdv_sistemu === '1' ? '0' : '1',
                                            )
                                        }
                                        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                            data.u_pdv_sistemu === '1'
                                                ? 'bg-primary'
                                                : 'bg-muted-foreground/30'
                                        }`}
                                    >
                                        <span
                                            className={`inline-block h-5 w-5 rounded-full bg-white transition-transform ${
                                                data.u_pdv_sistemu === '1'
                                                    ? 'translate-x-5'
                                                    : 'translate-x-1'
                                            }`}
                                        />
                                    </button>
                                    <InputError message={errors.u_pdv_sistemu} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="trn">TRN</Label>
                                    <Input
                                        id="trn"
                                        value={data.trn}
                                        onChange={(event) => setData('trn', event.target.value)}
                                    />
                                    <InputError message={errors.trn} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="broj_kase">Broj kase</Label>
                                    <Input
                                        id="broj_kase"
                                        value={data.broj_kase}
                                        onChange={(event) =>
                                            setData('broj_kase', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.broj_kase} />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <BadgeInfo className="size-4 text-sky-600" />
                                <h3 className="font-semibold">Bankovni podaci</h3>
                            </div>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="banka">Banka</Label>
                                    <Input
                                        id="banka"
                                        value={data.banka}
                                        onChange={(event) =>
                                            setData('banka', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.banka} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="iban">IBAN</Label>
                                    <Input
                                        id="iban"
                                        value={data.iban}
                                        onChange={(event) =>
                                            setData('iban', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.iban} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="swift">SWIFT</Label>
                                    <Input
                                        id="swift"
                                        value={data.swift}
                                        onChange={(event) =>
                                            setData('swift', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.swift} />
                                </div>

                                <div className="grid gap-2 md:col-span-3">
                                    <Label htmlFor="osiguravajuce_drustvo">
                                        Osiguravajuće društvo
                                    </Label>
                                    <Input
                                        id="osiguravajuce_drustvo"
                                        value={data.osiguravajuce_drustvo}
                                        onChange={(event) =>
                                            setData(
                                                'osiguravajuce_drustvo',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.osiguravajuce_drustvo}
                                    />
                                </div>

                                <div className="grid gap-2 md:col-span-3">
                                    <Label htmlFor="polisa_osiguranja">
                                        Polisa osiguranja
                                    </Label>
                                    <Input
                                        id="polisa_osiguranja"
                                        value={data.polisa_osiguranja}
                                        onChange={(event) =>
                                            setData(
                                                'polisa_osiguranja',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.polisa_osiguranja}
                                    />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <Mail className="size-4 text-sky-600" />
                                <h3 className="font-semibold">Kontakt</h3>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(event) => setData('email', event.target.value)}
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Telefon</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(event) => setData('phone', event.target.value)}
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <KeyRound className="size-4 text-sky-600" />
                                <h3 className="font-semibold">API postavke</h3>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label>Status API ključa</Label>
                                    <div className="rounded-md border bg-muted/30 px-3 py-2 text-sm">
                                        {setting.api_key_active ? 'Aktivan ključ postoji' : 'Ključ nije generisan'}
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label>Zadnje korištenje</Label>
                                    <div className="rounded-md border bg-muted/30 px-3 py-2 text-sm">
                                        {setting.api_key_last_used_at
                                            ? new Date(setting.api_key_last_used_at).toLocaleString()
                                            : 'Nije korišten'}
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="api_domain_1">Domena 1</Label>
                                    <Input
                                        id="api_domain_1"
                                        value={data.api_domain_1}
                                        placeholder="example.com"
                                        onChange={(event) =>
                                            setData('api_domain_1', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.api_domain_1} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="api_domain_2">Domena 2</Label>
                                    <Input
                                        id="api_domain_2"
                                        value={data.api_domain_2}
                                        placeholder="app.example.com"
                                        onChange={(event) =>
                                            setData('api_domain_2', event.target.value)
                                        }
                                    />
                                    <InputError message={errors.api_domain_2} />
                                </div>
                                <div className="md:col-span-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setData('regenerate_api_token', true)}
                                    >
                                        Generiši novi API ključ
                                    </Button>
                                    {data.regenerate_api_token && (
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            Novi ključ će biti kreiran nakon klika na "Sačuvajte postavke".
                                        </p>
                                    )}
                                    <InputError message={errors.regenerate_api_token} />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <MapPin className="size-4 text-sky-600" />
                                <h3 className="font-semibold">Lokacija</h3>
                            </div>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="address">Adresa</Label>
                                    <Input
                                        id="address"
                                        value={data.address}
                                        onChange={(event) => setData('address', event.target.value)}
                                    />
                                    <InputError message={errors.address} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="city">Grad</Label>
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(event) => setData('city', event.target.value)}
                                    />
                                    <InputError message={errors.city} />
                                </div>
                            </div>

                            <div className="mt-4 grid gap-2 md:max-w-72">
                                <Label htmlFor="zip">Poštanski broj</Label>
                                <Input
                                    id="zip"
                                    value={data.zip}
                                    onChange={(event) => setData('zip', event.target.value)}
                                />
                                <InputError message={errors.zip} />
                            </div>
                        </section>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing} className="min-w-56">
                                <Save className="mr-2 size-4" />
                                Sačuvajte postavke
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
