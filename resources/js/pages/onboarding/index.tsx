import { Head, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Building2,
    Check,
    CheckCircle2,
    CircleUserRound,
    Globe2,
    LayoutTemplate,
    Loader2,
    Palette,
    PanelsTopLeft,
    Rocket,
} from 'lucide-react';
import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { OnboardingLayout } from '../../layouts/onboarding-layout';
import { cn } from '../../lib/utils';
import type { AppPageProps } from '../../types';

interface TemplateOption {
    name: string;
    description: string;
    categories: string;
}

interface PaletteOption {
    name: string;
    primary: string;
    secondary: string;
    accent: string;
}

interface OnboardingOptions {
    categories: Record<string, string>;
    countries: Record<string, string>;
    timezones: Record<string, string>;
    languages: Record<string, string>;
    templates: Record<string, TemplateOption>;
    palettes: Record<string, PaletteOption>;
    sections: Record<string, string>;
}

interface ThemeData {
    palette: string;
    primary: string;
    secondary: string;
    accent: string;
    heading_font: string;
    body_font: string;
    button_radius: string;
    corner_radius: string;
}

interface OnboardingForm {
    business_name: string;
    business_category: string;
    country_code: string;
    timezone: string;
    default_language: string;
    contact_email: string;
    contact_phone: string;
    subdomain: string;
    website_type: 'landing_page' | 'multipage';
    template_key: string;
    theme: ThemeData;
    sections: string[];
    confirmed: boolean;
}

interface Availability {
    available: boolean;
    hostname: string;
    message: string;
}

const steps = [
    { title: 'Account', icon: CircleUserRound },
    { title: 'Business', icon: Building2 },
    { title: 'Address', icon: Globe2 },
    { title: 'Website type', icon: PanelsTopLeft },
    { title: 'Template', icon: LayoutTemplate },
    { title: 'Theme', icon: Palette },
    { title: 'Sections', icon: CheckCircle2 },
    { title: 'Confirm', icon: Rocket },
];

export default function Onboarding({
    options,
    defaults,
}: {
    options: OnboardingOptions;
    defaults: { country_code: string; timezone: string; locale: string };
}) {
    const { auth } = usePage<AppPageProps>().props;
    const [step, setStep] = useState(0);
    const [availability, setAvailability] = useState<Availability | null>(null);
    const [checking, setChecking] = useState(false);
    const firstPalette = options.palettes.ocean;
    const { data, setData, post, processing, errors } = useForm<OnboardingForm>({
        business_name: '',
        business_category: 'consulting',
        country_code: defaults.country_code,
        timezone: defaults.timezone,
        default_language: defaults.locale,
        contact_email: auth.user?.email ?? '',
        contact_phone: auth.user?.phone ?? '',
        subdomain: '',
        website_type: 'multipage',
        template_key: 'horizon',
        theme: {
            palette: 'ocean',
            primary: firstPalette.primary,
            secondary: firstPalette.secondary,
            accent: firstPalette.accent,
            heading_font: 'Inter',
            body_font: 'Inter',
            button_radius: '0.5rem',
            corner_radius: '0.75rem',
        },
        sections: ['hero', 'about', 'services', 'contact'],
        confirmed: false,
    });

    useEffect(() => {
        const normalized = data.subdomain.toLowerCase().trim();
        if (!/^(?!.*--)[a-z0-9][a-z0-9-]{1,38}[a-z0-9]$/.test(normalized)) {
            return;
        }

        const controller = new AbortController();
        let active = true;
        const timer = window.setTimeout(() => {
            setChecking(true);
            fetch(`/onboarding/subdomain-availability?subdomain=${encodeURIComponent(normalized)}`, {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: { Accept: 'application/json' },
            })
                .then(async (response) => (await response.json()) as Availability)
                .then((result) => active && setAvailability(result))
                .catch((error: unknown) => {
                    if (active && !(error instanceof DOMException && error.name === 'AbortError'))
                        setAvailability(null);
                })
                .finally(() => active && setChecking(false));
        }, 350);

        return () => {
            window.clearTimeout(timer);
            active = false;
            controller.abort();
        };
    }, [data.subdomain]);

    const canContinue = useMemo(() => {
        if (step === 1) return data.business_name.trim().length >= 2 && data.contact_email.includes('@');
        if (step === 2) return availability?.available === true;
        if (step === 6) return data.sections.length > 0;
        if (step === 7) return data.confirmed;
        return true;
    }, [availability, data, step]);

    const choosePalette = (key: string) => {
        const palette = options.palettes[key];
        setData('theme', {
            ...data.theme,
            palette: key,
            primary: palette.primary,
            secondary: palette.secondary,
            accent: palette.accent,
        });
    };

    const toggleSection = (key: string) => {
        setData(
            'sections',
            data.sections.includes(key)
                ? data.sections.filter((item) => item !== key)
                : [...data.sections, key],
        );
    };

    const submit = () => post('/onboarding', { preserveScroll: true });

    return (
        <OnboardingLayout>
            <Head title="Create your workspace" />
            <div className="mb-8 max-w-2xl">
                <p className="text-brand-700 text-sm font-semibold">Ezyweb guided setup</p>
                <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                    Let’s build your foundation.
                </h1>
                <p className="mt-3 leading-7 text-slate-600">
                    Eight focused steps. You can refine content and design later.
                </p>
            </div>

            <div className="grid gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
                <aside>
                    <div className="mb-3 flex items-center justify-between text-sm lg:hidden">
                        <span className="font-semibold">
                            Step {step + 1} of {steps.length}
                        </span>
                        <span className="text-slate-500">{steps[step].title}</span>
                    </div>
                    <div className="mb-6 h-2 overflow-hidden rounded-full bg-slate-200 lg:hidden">
                        <div
                            className="bg-brand-600 h-full rounded-full transition-all"
                            style={{ width: `${((step + 1) / steps.length) * 100}%` }}
                        />
                    </div>
                    <ol className="hidden space-y-1 lg:block">
                        {steps.map(({ title, icon: Icon }, index) => (
                            <li key={title}>
                                <button
                                    type="button"
                                    onClick={() => index < step && setStep(index)}
                                    disabled={index > step}
                                    className={cn(
                                        'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm',
                                        index === step
                                            ? 'bg-brand-50 text-brand-700 font-semibold'
                                            : index < step
                                              ? 'text-slate-700 hover:bg-white'
                                              : 'cursor-not-allowed text-slate-400',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'grid size-7 place-items-center rounded-lg',
                                            index <= step ? 'bg-white shadow-sm' : 'bg-slate-100',
                                        )}
                                    >
                                        {index < step ? (
                                            <Check className="size-4" />
                                        ) : (
                                            <Icon className="size-4" />
                                        )}
                                    </span>
                                    {title}
                                </button>
                            </li>
                        ))}
                    </ol>
                </aside>

                <section className="overflow-hidden rounded-2xl border bg-white shadow-sm">
                    <div className="min-h-[31rem] p-5 sm:p-8">
                        {step === 0 && (
                            <Step
                                title="Your account is ready"
                                description="We’ll use this secure account as the owner of your first workspace."
                            >
                                <div className="rounded-2xl border bg-slate-50 p-5">
                                    <p className="font-semibold">{auth.user?.name}</p>
                                    <p className="mt-1 text-sm text-slate-600">{auth.user?.email}</p>
                                    <span className="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <CheckCircle2 className="size-3.5" />
                                        Account complete
                                    </span>
                                </div>
                            </Step>
                        )}
                        {step === 1 && (
                            <Step
                                title="Tell us about your business"
                                description="These details establish your organisation and sensible website defaults."
                            >
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field
                                        label="Business or organisation name"
                                        error={errors.business_name}
                                        wide
                                    >
                                        <input
                                            value={data.business_name}
                                            onChange={(e) => setData('business_name', e.target.value)}
                                            className="input"
                                            autoFocus
                                        />
                                    </Field>
                                    <Field label="Business category" error={errors.business_category}>
                                        <Select
                                            value={data.business_category}
                                            onChange={(value) => setData('business_category', value)}
                                            options={options.categories}
                                        />
                                    </Field>
                                    <Field label="Country">
                                        <Select
                                            value={data.country_code}
                                            onChange={(value) => setData('country_code', value)}
                                            options={options.countries}
                                        />
                                    </Field>
                                    <Field label="Time zone">
                                        <Select
                                            value={data.timezone}
                                            onChange={(value) => setData('timezone', value)}
                                            options={options.timezones}
                                        />
                                    </Field>
                                    <Field label="Default language">
                                        <Select
                                            value={data.default_language}
                                            onChange={(value) => setData('default_language', value)}
                                            options={options.languages}
                                        />
                                    </Field>
                                    <Field label="Contact email" error={errors.contact_email}>
                                        <input
                                            type="email"
                                            value={data.contact_email}
                                            onChange={(e) => setData('contact_email', e.target.value)}
                                            className="input"
                                        />
                                    </Field>
                                    <Field label="Contact phone" error={errors.contact_phone}>
                                        <input
                                            type="tel"
                                            value={data.contact_phone}
                                            onChange={(e) => setData('contact_phone', e.target.value)}
                                            className="input"
                                        />
                                    </Field>
                                </div>
                            </Step>
                        )}
                        {step === 2 && (
                            <Step
                                title="Choose your website address"
                                description="This free Ezyweb subdomain will be globally unique."
                            >
                                <div className="mx-auto max-w-xl">
                                    <label className="block text-sm font-medium">Subdomain</label>
                                    <div className="focus-within:ring-brand-500/20 mt-2 flex rounded-xl border bg-white focus-within:ring-3">
                                        <input
                                            value={data.subdomain}
                                            onChange={(e) => {
                                                setAvailability(null);
                                                setChecking(false);
                                                setData(
                                                    'subdomain',
                                                    e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''),
                                                );
                                            }}
                                            className="min-w-0 flex-1 rounded-l-xl px-3.5 py-3 text-sm outline-none"
                                            placeholder="yourbusiness"
                                            autoFocus
                                        />
                                        <span className="flex items-center border-l bg-slate-50 px-3 text-sm text-slate-500">
                                            .ezyweb.in
                                        </span>
                                    </div>
                                    <div className="mt-3 min-h-6 text-sm">
                                        {checking ? (
                                            <span className="flex items-center gap-2 text-slate-500">
                                                <Loader2 className="size-4 animate-spin" />
                                                Checking availability…
                                            </span>
                                        ) : availability ? (
                                            <span
                                                className={
                                                    availability.available
                                                        ? 'text-emerald-700'
                                                        : 'text-red-600'
                                                }
                                            >
                                                {availability.message}
                                            </span>
                                        ) : (
                                            <span className="text-slate-500">
                                                3–40 lowercase letters, numbers, or single hyphens.
                                            </span>
                                        )}
                                    </div>
                                    {errors.subdomain && (
                                        <p className="mt-2 text-sm text-red-600">{errors.subdomain}</p>
                                    )}
                                    <div className="mt-8 rounded-2xl bg-slate-950 p-5 text-white">
                                        <p className="text-xs font-semibold tracking-wider text-slate-400 uppercase">
                                            Your future URL
                                        </p>
                                        <p className="mt-2 truncate text-lg font-semibold">
                                            https://{data.subdomain || 'yourbusiness'}.ezyweb.in
                                        </p>
                                    </div>
                                </div>
                            </Step>
                        )}
                        {step === 3 && (
                            <Step
                                title="Choose your website type"
                                description="Both options use the same professional design system."
                            >
                                <ChoiceGrid
                                    choices={[
                                        [
                                            'landing_page',
                                            'Single-page landing website',
                                            'All key sections on one focused scrolling page.',
                                        ],
                                        [
                                            'multipage',
                                            'Multipage business website',
                                            'Separate pages for deeper content and future growth.',
                                        ],
                                    ]}
                                    value={data.website_type}
                                    onChange={(value) =>
                                        setData('website_type', value as OnboardingForm['website_type'])
                                    }
                                />
                            </Step>
                        )}
                        {step === 4 && (
                            <Step
                                title="Select a starting template"
                                description="Your content remains independent, so presentation can evolve later."
                            >
                                <div className="grid gap-4 md:grid-cols-3">
                                    {Object.entries(options.templates).map(([key, item]) => (
                                        <button
                                            type="button"
                                            key={key}
                                            onClick={() => setData('template_key', key)}
                                            className={cn(
                                                'overflow-hidden rounded-2xl border-2 text-left transition',
                                                data.template_key === key
                                                    ? 'border-brand-600 shadow-md'
                                                    : 'border-slate-200 hover:border-slate-300',
                                            )}
                                        >
                                            <div
                                                className={cn(
                                                    'h-28 p-4',
                                                    key === 'horizon'
                                                        ? 'bg-gradient-to-br from-slate-800 to-blue-700'
                                                        : key === 'localpro'
                                                          ? 'bg-gradient-to-br from-emerald-700 to-amber-400'
                                                          : 'bg-gradient-to-br from-slate-900 to-violet-600',
                                                )}
                                            >
                                                <div className="h-2 w-16 rounded bg-white/80" />
                                                <div className="mt-8 h-3 w-3/4 rounded bg-white/90" />
                                                <div className="mt-2 h-2 w-1/2 rounded bg-white/40" />
                                            </div>
                                            <div className="p-4">
                                                <div className="flex items-center justify-between">
                                                    <h3 className="font-semibold">{item.name}</h3>
                                                    {data.template_key === key && (
                                                        <CheckCircle2 className="text-brand-700 size-5" />
                                                    )}
                                                </div>
                                                <p className="mt-2 text-sm text-slate-600">
                                                    {item.description}
                                                </p>
                                                <p className="mt-3 text-xs text-slate-400">
                                                    {item.categories}
                                                </p>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </Step>
                        )}
                        {step === 5 && (
                            <Step
                                title="Set your visual direction"
                                description="Choose a palette and foundational typography. Full theme controls arrive in Phase 3."
                            >
                                <div className="space-y-7">
                                    <div>
                                        <label className="text-sm font-medium">Colour palette</label>
                                        <div className="mt-3 grid gap-3 sm:grid-cols-3">
                                            {Object.entries(options.palettes).map(([key, palette]) => (
                                                <button
                                                    type="button"
                                                    key={key}
                                                    onClick={() => choosePalette(key)}
                                                    className={cn(
                                                        'rounded-xl border-2 p-3 text-left',
                                                        data.theme.palette === key
                                                            ? 'border-brand-600'
                                                            : 'border-slate-200',
                                                    )}
                                                >
                                                    <div className="flex gap-1.5">
                                                        <span
                                                            className="size-7 rounded-lg"
                                                            style={{ backgroundColor: palette.primary }}
                                                        />
                                                        <span
                                                            className="size-7 rounded-lg"
                                                            style={{ backgroundColor: palette.secondary }}
                                                        />
                                                        <span
                                                            className="size-7 rounded-lg"
                                                            style={{ backgroundColor: palette.accent }}
                                                        />
                                                    </div>
                                                    <p className="mt-2 text-sm font-semibold">
                                                        {palette.name}
                                                    </p>
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-3">
                                        {(['primary', 'secondary', 'accent'] as const).map((color) => (
                                            <Field
                                                key={color}
                                                label={`${color[0].toUpperCase()}${color.slice(1)} colour`}
                                            >
                                                <div className="flex items-center gap-2 rounded-xl border p-2">
                                                    <input
                                                        type="color"
                                                        value={data.theme[color]}
                                                        onChange={(e) =>
                                                            setData('theme', {
                                                                ...data.theme,
                                                                [color]: e.target.value,
                                                            })
                                                        }
                                                        className="size-8 rounded border-0"
                                                    />
                                                    <span className="font-mono text-sm">
                                                        {data.theme[color]}
                                                    </span>
                                                </div>
                                            </Field>
                                        ))}
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field label="Heading font">
                                            <Select
                                                value={data.theme.heading_font}
                                                onChange={(value) =>
                                                    setData('theme', { ...data.theme, heading_font: value })
                                                }
                                                options={{
                                                    Inter: 'Inter',
                                                    Manrope: 'Manrope',
                                                    'Playfair Display': 'Playfair Display',
                                                }}
                                            />
                                        </Field>
                                        <Field label="Body font">
                                            <Select
                                                value={data.theme.body_font}
                                                onChange={(value) =>
                                                    setData('theme', { ...data.theme, body_font: value })
                                                }
                                                options={{
                                                    Inter: 'Inter',
                                                    'Source Sans 3': 'Source Sans 3',
                                                    Lato: 'Lato',
                                                }}
                                            />
                                        </Field>
                                        <Field label="Button radius">
                                            <Select
                                                value={data.theme.button_radius}
                                                onChange={(value) =>
                                                    setData('theme', { ...data.theme, button_radius: value })
                                                }
                                                options={{
                                                    '0.25rem': 'Compact',
                                                    '0.5rem': 'Balanced',
                                                    '0.75rem': 'Rounded',
                                                    '9999px': 'Pill',
                                                }}
                                            />
                                        </Field>
                                        <Field label="General corner radius">
                                            <Select
                                                value={data.theme.corner_radius}
                                                onChange={(value) =>
                                                    setData('theme', { ...data.theme, corner_radius: value })
                                                }
                                                options={{
                                                    '0.25rem': 'Compact',
                                                    '0.5rem': 'Balanced',
                                                    '0.75rem': 'Rounded',
                                                    '1rem': 'Soft',
                                                }}
                                            />
                                        </Field>
                                    </div>
                                </div>
                            </Step>
                        )}
                        {step === 6 && (
                            <Step
                                title="Choose your initial sections"
                                description="Select the content areas you expect to need. This choice is preserved for the builder phase."
                            >
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {Object.entries(options.sections).map(([key, label]) => (
                                        <label
                                            key={key}
                                            className={cn(
                                                'flex cursor-pointer items-center gap-3 rounded-xl border-2 p-4',
                                                data.sections.includes(key)
                                                    ? 'border-brand-600 bg-brand-50'
                                                    : 'border-slate-200',
                                            )}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={data.sections.includes(key)}
                                                onChange={() => toggleSection(key)}
                                                className="accent-brand-700 size-4"
                                            />
                                            <span className="text-sm font-medium">{label}</span>
                                        </label>
                                    ))}
                                </div>
                                {errors.sections && (
                                    <p className="mt-3 text-sm text-red-600">{errors.sections}</p>
                                )}
                            </Step>
                        )}
                        {step === 7 && (
                            <Step
                                title="Confirm your workspace"
                                description="Review the foundation Ezyweb will create in one secure transaction."
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Summary label="Business" value={data.business_name} />
                                    <Summary label="Website address" value={`${data.subdomain}.ezyweb.in`} />
                                    <Summary
                                        label="Website type"
                                        value={
                                            data.website_type === 'landing_page'
                                                ? 'Single-page landing website'
                                                : 'Multipage business website'
                                        }
                                    />
                                    <Summary
                                        label="Template"
                                        value={options.templates[data.template_key].name}
                                    />
                                    <Summary
                                        label="Palette"
                                        value={options.palettes[data.theme.palette].name}
                                    />
                                    <Summary
                                        label="Initial sections"
                                        value={`${data.sections.length} selected`}
                                    />
                                </div>
                                <label className="mt-6 flex cursor-pointer items-start gap-3 rounded-xl border bg-slate-50 p-4">
                                    <input
                                        type="checkbox"
                                        checked={data.confirmed}
                                        onChange={(e) => setData('confirmed', e.target.checked)}
                                        className="accent-brand-700 mt-1 size-4"
                                    />
                                    <span className="text-sm leading-6 text-slate-700">
                                        Create my tenant, owner membership, draft website, and verified Ezyweb
                                        subdomain with these settings.
                                    </span>
                                </label>
                                {Object.keys(errors).length > 0 && (
                                    <div className="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">
                                        Please review the highlighted information. No records were created.
                                    </div>
                                )}
                            </Step>
                        )}
                    </div>

                    <div className="flex items-center justify-between border-t bg-slate-50 px-5 py-4 sm:px-8">
                        <button
                            type="button"
                            onClick={() => setStep(Math.max(0, step - 1))}
                            disabled={step === 0 || processing}
                            className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-white disabled:invisible"
                        >
                            <ArrowLeft className="size-4" />
                            Back
                        </button>
                        {step < steps.length - 1 ? (
                            <button
                                type="button"
                                onClick={() => setStep(step + 1)}
                                disabled={!canContinue}
                                className="bg-brand-700 hover:bg-brand-600 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-45"
                            >
                                Continue
                                <ArrowRight className="size-4" />
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={submit}
                                disabled={!canContinue || processing}
                                className="bg-brand-700 hover:bg-brand-600 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                {processing ? (
                                    <Loader2 className="size-4 animate-spin" />
                                ) : (
                                    <Rocket className="size-4" />
                                )}
                                Create workspace
                            </button>
                        )}
                    </div>
                </section>
            </div>
        </OnboardingLayout>
    );
}

function Step({ title, description, children }: { title: string; description: string; children: ReactNode }) {
    return (
        <div>
            <h2 className="text-2xl font-bold tracking-tight">{title}</h2>
            <p className="mt-2 mb-7 text-sm leading-6 text-slate-600">{description}</p>
            {children}
        </div>
    );
}
function Field({
    label,
    error,
    children,
    wide = false,
}: {
    label: string;
    error?: string;
    children: ReactNode;
    wide?: boolean;
}) {
    return (
        <label className={cn('block', wide && 'sm:col-span-2')}>
            <span className="mb-1.5 block text-sm font-medium">{label}</span>
            {children}
            {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
        </label>
    );
}
function Select({
    value,
    onChange,
    options,
}: {
    value: string;
    onChange: (value: string) => void;
    options: Record<string, string>;
}) {
    return (
        <select value={value} onChange={(e) => onChange(e.target.value)} className="input">
            <>
                {Object.entries(options).map(([key, label]) => (
                    <option key={key} value={key}>
                        {label}
                    </option>
                ))}
            </>
        </select>
    );
}
function ChoiceGrid({
    choices,
    value,
    onChange,
}: {
    choices: string[][];
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {choices.map(([key, title, description]) => (
                <button
                    type="button"
                    key={key}
                    onClick={() => onChange(key)}
                    className={cn(
                        'rounded-2xl border-2 p-5 text-left',
                        value === key ? 'border-brand-600 bg-brand-50' : 'border-slate-200',
                    )}
                >
                    <div className="flex items-center justify-between">
                        <h3 className="font-semibold">{title}</h3>
                        {value === key && <CheckCircle2 className="text-brand-700 size-5" />}
                    </div>
                    <p className="mt-2 text-sm leading-6 text-slate-600">{description}</p>
                </button>
            ))}
        </div>
    );
}
function Summary({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border p-4">
            <p className="text-xs font-semibold tracking-wide text-slate-400 uppercase">{label}</p>
            <p className="mt-2 font-medium text-slate-900">{value || 'Not provided'}</p>
        </div>
    );
}
