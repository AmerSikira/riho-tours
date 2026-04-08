import { Form, Head, Link } from '@inertiajs/react';
import type { ClipboardEvent } from 'react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

type HeroVariant = {
    image: string;
    alt: string;
    eyebrow: string;
    title: string;
    description: string;
};

const heroVariants: HeroVariant[] = [
    {
        image: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1600&q=80',
        alt: 'Pogled na tropsku plažu sa tirkiznim morem',
        eyebrow: 'Sun & Sea',
        title: 'Svaka rezervacija počinje sa pravom inspiracijom.',
        description: 'Organizuj putovanja, klijente i dokumentaciju bez gubitka vremena.',
    },
    {
        image: 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1600&q=80',
        alt: 'Planinski pejzaž sa jezerom i oblacima',
        eyebrow: 'Mountain Escape',
        title: 'Detalji su važni kada planiraš veliko putovanje.',
        description: 'Rezervacije, aranžmani i finansije na jednom mjestu.',
    },
    {
        image: 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=1600&q=80',
        alt: 'Pogled iz aviona na planine i oblake',
        eyebrow: 'Sky Journey',
        title: 'Leti kroz procese jednako brzo kao tvoji putnici.',
        description: 'Jedna platforma za sve korake od upita do potvrde.',
    },
    {
        image: 'https://images.unsplash.com/photo-1501555088652-021faa106b9b?auto=format&fit=crop&w=1600&q=80',
        alt: 'Obalni grad tokom zalaska sunca',
        eyebrow: 'City Break',
        title: 'Pretvori interes klijenta u potvrđenu rezervaciju.',
        description: 'Brže izdavanje ponuda, jasne evidencije i bolja kontrola.',
    },
    {
        image: 'https://images.unsplash.com/photo-1530521954074-e64f6810b32d?auto=format&fit=crop&w=1600&q=80',
        alt: 'Pustinjski pejzaž sa karavanom',
        eyebrow: 'Adventure',
        title: 'Pouzdano upravljanje i kad je put složen.',
        description: 'Od prvog kontakta do finalnog dokumenta, sve je pod kontrolom.',
    },
];

export default function Login({
    status,
    canResetPassword,
}: Props) {
    const [heroVariant] = useState<HeroVariant>(() => {
        const randomIndex = Math.floor(Math.random() * heroVariants.length);

        return heroVariants[randomIndex];
    });

    const handleTrimmedPaste = (event: ClipboardEvent<HTMLInputElement>) => {
        const input = event.currentTarget;
        const pastedText = event.clipboardData.getData('text').trim();

        event.preventDefault();

        const selectionStart = input.selectionStart ?? input.value.length;
        const selectionEnd = input.selectionEnd ?? selectionStart;
        const nextValue =
            input.value.slice(0, selectionStart) +
            pastedText +
            input.value.slice(selectionEnd);

        input.value = nextValue;

        const caretPosition = selectionStart + pastedText.length;
        input.setSelectionRange(caretPosition, caretPosition);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    return (
        <>
            <Head title="Prijava" />

            <div className="grid min-h-svh lg:h-svh lg:grid-cols-2">
                <div className="flex items-center justify-center bg-background px-6 py-12 md:px-10 lg:overflow-y-auto">
                    <div className="w-full max-w-md">
                        <Link
                            href={home()}
                            className="mb-8 inline-flex items-center gap-2 font-medium"
                        >
                            <AppLogoIcon className="w-full" />

                        </Link>

                        <div className="mb-8 space-y-2">
                            <h1 className="text-2xl font-semibold">
                                Prijava na sistem
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Unesite email i lozinku za pristup aplikaciji.
                            </p>
                        </div>

                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            className="flex flex-col gap-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-6">
                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Email adresa</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                name="email"
                                                required
                                                autoFocus
                                                tabIndex={1}
                                                autoComplete="email"
                                                placeholder="ime@firma.ba"
                                                onPaste={handleTrimmedPaste}
                                            />
                                            <InputError message={errors.email} />
                                        </div>

                                        <div className="grid gap-2">
                                            <div className="flex items-center">
                                                <Label htmlFor="password">
                                                    Lozinka
                                                </Label>
                                                {canResetPassword && (
                                                    <TextLink
                                                        href={request()}
                                                        className="ml-auto text-sm"
                                                        tabIndex={5}
                                                    >
                                                        Zaboravili ste lozinku?
                                                    </TextLink>
                                                )}
                                            </div>
                                            <PasswordInput
                                                id="password"
                                                name="password"
                                                required
                                                tabIndex={2}
                                                autoComplete="current-password"
                                                placeholder="Unesite lozinku"
                                                onPaste={handleTrimmedPaste}
                                            />
                                            <InputError message={errors.password} />
                                        </div>

                                        <div className="flex items-center space-x-3">
                                            <Checkbox
                                                id="remember"
                                                name="remember"
                                                tabIndex={3}
                                            />
                                            <Label htmlFor="remember">
                                                Zapamti me
                                            </Label>
                                        </div>

                                        <Button
                                            type="submit"
                                            className="mt-2 w-full"
                                            tabIndex={4}
                                            disabled={processing}
                                            data-test="login-button"
                                        >
                                            {processing && <Spinner />}
                                            Prijavi se
                                        </Button>
                                    </div>

                                </>
                            )}
                        </Form>

                        {status && (
                            <div className="mt-6 rounded-md border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                                {status}
                            </div>
                        )}
                    </div>
                </div>

                <div className="relative hidden lg:block lg:h-svh">
                    <img
                        src={heroVariant.image}
                        alt={heroVariant.alt}
                        className="h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
                    <div className="absolute right-10 bottom-10 max-w-md text-white">
                        <p className="mb-2 text-sm uppercase tracking-[0.2em] text-white/80">
                            {heroVariant.eyebrow}
                        </p>
                        <h2 className="text-3xl font-semibold leading-tight">
                            {heroVariant.title}
                        </h2>
                        <p className="mt-3 text-sm text-white/85">
                            {heroVariant.description}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
