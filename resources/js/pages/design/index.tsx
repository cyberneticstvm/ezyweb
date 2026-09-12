import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Check,
    ExternalLink,
    Monitor,
    Palette,
    RotateCcw,
    Save,
    Smartphone,
    Tablet,
    TriangleAlert,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { TemplatePreview, type ThemeTokens } from '../../components/template-preview';
import { useConfirm } from '../../components/confirm-provider';
import { DashboardLayout } from '../../layouts/dashboard-layout';
import { cn } from '../../lib/utils';
import type { AppPageProps } from '../../types';

interface TemplateRecord {
    id: number;
    key: string;
    name: string;
    description: string;
    suitable_categories: string[];
    preview_desktop_path: string;
    preview_mobile_path: string;
    default_theme: ThemeTokens;
    variant_map: Record<string, string>;
}
interface Preset {
    name: string;
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    surface: string;
    heading: string;
    text: string;
    muted_text: string;
    border: string;
}
interface ContrastResult {
    pair: string;
    ratio: number;
    required: number;
    passes: boolean;
}
type Viewport = 'desktop' | 'tablet' | 'mobile';

export default function Design({
    templates,
    presets,
    theme,
}: {
    templates: TemplateRecord[];
    presets: Record<string, Preset>;
    theme: ThemeTokens;
    cssVariables: Record<string, string>;
}) {
    const { website, errors } = usePage<AppPageProps>().props;
    const [draft, setDraft] = useState(theme);
    const [saved, setSaved] = useState(theme);
    const [viewport, setViewport] = useState<Viewport>('desktop');
    const confirm = useConfirm();
    const isDirty = JSON.stringify(draft) !== JSON.stringify(saved);
    const selectedKey = website?.template_key ?? 'horizon';
    const selected = templates.find((item) => item.key === selectedKey) ?? templates[0];
    const liveContrast = useMemo(() => analyzeContrast(draft), [draft]);

    useEffect(() => {
        const handle = (event: BeforeUnloadEvent) => {
            if (isDirty) event.preventDefault();
        };
        window.addEventListener('beforeunload', handle);
        return () => window.removeEventListener('beforeunload', handle);
    }, [isDirty]);

    const update = <K extends keyof ThemeTokens>(key: K, value: ThemeTokens[K]) =>
        setDraft((current) => ({ ...current, [key]: value }));
    const applyPreset = (preset: Preset) => setDraft((current) => ({ ...current, ...preset }));
    const save = () => {
        const payload: Record<string, string> = { ...draft };

        router.put(
            '/dashboard/design/theme',
            { theme: payload },
            { preserveScroll: true, onSuccess: () => setSaved(draft) },
        );
    };
    const reset = async () => {
        if (
            await confirm({
                title: 'Reset theme settings?',
                description: `Restore ${selected.name} colours, typography, spacing and component styles.`,
                confirmLabel: 'Reset theme',
            })
        ) {
            router.post(
                '/dashboard/design/theme/reset',
                {},
                {
                    onSuccess: () => {
                        setDraft(selected.default_theme);
                        setSaved(selected.default_theme);
                    },
                },
            );
        }
    };
    const switchTemplate = async (template: TemplateRecord) => {
        if (template.key === selectedKey) return;
        if (
            await confirm({
                title: `Switch to ${template.name}?`,
                description:
                    'Ezyweb will snapshot the current presentation, preserve all content and theme choices, then apply the new template structure for preview.',
                confirmLabel: 'Switch template',
            })
        ) {
            router.post('/dashboard/design/template', { template_key: template.key, confirmed: true });
        }
    };

    return (
        <DashboardLayout
            title="Design system"
            description="Choose a presentation system and shape your brand with safe, reusable design tokens."
            breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Design' }]}
        >
            <Head title="Design system" />
            <section className="rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-brand-700 text-sm font-semibold">Template gallery</p>
                        <h2 className="mt-1 text-xl font-bold">Choose your visual foundation</h2>
                    </div>
                    <p className="text-xs text-slate-500">Content remains unchanged when you switch.</p>
                </div>
                <div className="mt-5 grid gap-5 lg:grid-cols-3">
                    {templates.map((template) => (
                        <article
                            key={template.key}
                            className={cn(
                                'overflow-hidden rounded-2xl border-2 bg-white',
                                template.key === selectedKey
                                    ? 'border-brand-600 shadow-md'
                                    : 'border-slate-200',
                            )}
                        >
                            <img
                                src={template.preview_desktop_path}
                                alt={`${template.name} template preview`}
                                className="aspect-[8/5] w-full border-b object-cover"
                            />
                            <div className="p-4">
                                <div className="flex items-center justify-between">
                                    <h3 className="font-bold">{template.name}</h3>
                                    {template.key === selectedKey && (
                                        <span className="text-brand-700 inline-flex items-center gap-1 text-xs font-semibold">
                                            <Check className="size-3.5" />
                                            Selected
                                        </span>
                                    )}
                                </div>
                                <p className="mt-2 min-h-12 text-sm leading-6 text-slate-600">
                                    {template.description}
                                </p>
                                <div className="mt-4 flex gap-2">
                                    <button
                                        onClick={() => void switchTemplate(template)}
                                        disabled={template.key === selectedKey}
                                        className="flex-1 rounded-xl bg-slate-950 px-3 py-2 text-sm font-semibold text-white disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                        {template.key === selectedKey
                                            ? 'Current template'
                                            : 'Select template'}
                                    </button>
                                    <Link
                                        href={`/templates/${template.key}/preview`}
                                        target="_blank"
                                        className="grid size-9 place-items-center rounded-xl border"
                                        aria-label={`Preview ${template.name}`}
                                    >
                                        <ExternalLink className="size-4" />
                                    </Link>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            </section>
            <section className="mt-6 grid gap-6 xl:grid-cols-[24rem_minmax(0,1fr)]">
                <div className="space-y-5">
                    <div className="rounded-2xl border bg-white p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-brand-700 text-sm font-semibold">Theme controls</p>
                                <h2 className="mt-1 font-bold">Brand appearance</h2>
                            </div>
                            <Palette className="size-5 text-slate-400" />
                        </div>
                        <ControlGroup title="Preset palettes">
                            <div className="grid grid-cols-2 gap-2">
                                {Object.entries(presets).map(([key, preset]) => (
                                    <button
                                        key={key}
                                        onClick={() => applyPreset(preset)}
                                        className="hover:border-brand-500 rounded-xl border p-3 text-left"
                                    >
                                        <div className="flex gap-1">
                                            <span
                                                className="size-5 rounded"
                                                style={{ background: preset.primary }}
                                            />
                                            <span
                                                className="size-5 rounded"
                                                style={{ background: preset.secondary }}
                                            />
                                            <span
                                                className="size-5 rounded"
                                                style={{ background: preset.accent }}
                                            />
                                        </div>
                                        <span className="mt-2 block text-xs font-semibold">
                                            {preset.name}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </ControlGroup>
                        <ControlGroup title="Brand colours">
                            <div className="grid grid-cols-2 gap-3">
                                {(
                                    [
                                        'primary',
                                        'secondary',
                                        'accent',
                                        'background',
                                        'surface',
                                        'heading',
                                        'text',
                                        'muted_text',
                                        'border',
                                    ] as const
                                ).map((key) => (
                                    <ColorField
                                        key={key}
                                        label={key.replace('_', ' ')}
                                        value={draft[key]}
                                        onChange={(value) => update(key, value)}
                                    />
                                ))}
                            </div>
                        </ControlGroup>
                        <ControlGroup title="Button contrast">
                            <div className="grid grid-cols-2 gap-3">
                                <ColorField
                                    label="Primary text"
                                    value={draft.primary_foreground}
                                    onChange={(value) => update('primary_foreground', value)}
                                />
                                <ColorField
                                    label="Accent text"
                                    value={draft.accent_foreground}
                                    onChange={(value) => update('accent_foreground', value)}
                                />
                            </div>
                        </ControlGroup>
                        <ControlGroup title="Typography">
                            <SelectField
                                label="Heading font"
                                value={draft.heading_font}
                                options={['Inter', 'Manrope', 'Playfair Display']}
                                onChange={(value) => update('heading_font', value)}
                            />
                            <SelectField
                                label="Body font"
                                value={draft.body_font}
                                options={['Inter', 'Source Sans 3', 'Lato']}
                                onChange={(value) => update('body_font', value)}
                            />
                        </ControlGroup>
                        <ControlGroup title="Shape and layout">
                            <SelectField
                                label="Button style"
                                value={draft.button_style}
                                options={['solid', 'outline', 'soft']}
                                onChange={(value) => update('button_style', value)}
                            />
                            <SelectField
                                label="Button radius"
                                value={draft.button_radius}
                                options={['0.25rem', '0.5rem', '0.75rem', '9999px']}
                                onChange={(value) => update('button_radius', value)}
                            />
                            <SelectField
                                label="Card radius"
                                value={draft.card_radius}
                                options={['0.25rem', '0.5rem', '0.75rem', '1rem']}
                                onChange={(value) => update('card_radius', value)}
                            />
                            <SelectField
                                label="Shadow strength"
                                value={draft.shadow_strength}
                                options={['none', 'subtle', 'medium']}
                                onChange={(value) => update('shadow_strength', value)}
                            />
                            <SelectField
                                label="Container width"
                                value={draft.container_width}
                                options={['1080px', '1200px', '1280px']}
                                onChange={(value) => update('container_width', value)}
                            />
                            <SelectField
                                label="Section spacing"
                                value={draft.section_spacing}
                                options={['compact', 'comfortable', 'spacious']}
                                onChange={(value) => update('section_spacing', value)}
                            />
                        </ControlGroup>
                        {Object.keys(errors).length > 0 && (
                            <p className="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">
                                Some theme values could not be saved. Review the controls and try again.
                            </p>
                        )}
                        <div className="mt-5 flex gap-2">
                            <button
                                onClick={save}
                                disabled={!isDirty}
                                className="bg-brand-700 inline-flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white disabled:bg-slate-200 disabled:text-slate-500"
                            >
                                <Save className="size-4" />
                                Save theme
                            </button>
                            <button
                                onClick={() => void reset()}
                                className="grid size-10 place-items-center rounded-xl border"
                                aria-label="Reset theme"
                            >
                                <RotateCcw className="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
                <div className="min-w-0">
                    <div className="sticky top-24">
                        <div className="rounded-2xl border bg-white p-4 shadow-sm">
                            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p className="font-semibold">Live preview</p>
                                    <p className="text-xs text-slate-500">Draft changes appear instantly</p>
                                </div>
                                <div className="flex rounded-xl bg-slate-100 p-1">
                                    {(
                                        [
                                            ['desktop', Monitor],
                                            ['tablet', Tablet],
                                            ['mobile', Smartphone],
                                        ] as const
                                    ).map(([key, Icon]) => (
                                        <button
                                            key={key}
                                            onClick={() => setViewport(key)}
                                            className={cn(
                                                'rounded-lg p-2',
                                                viewport === key
                                                    ? 'text-brand-700 bg-white shadow-sm'
                                                    : 'text-slate-500',
                                            )}
                                            aria-label={`${key} preview`}
                                        >
                                            <Icon className="size-4" />
                                        </button>
                                    ))}
                                </div>
                            </div>
                            <div className="overflow-auto rounded-xl bg-slate-200 p-3 sm:p-6">
                                <div
                                    className={cn(
                                        'mx-auto overflow-hidden rounded-lg bg-white shadow-xl transition-all',
                                        viewport === 'desktop'
                                            ? 'w-full'
                                            : viewport === 'tablet'
                                              ? 'w-[720px] max-w-full'
                                              : 'w-[375px] max-w-full',
                                    )}
                                >
                                    <TemplatePreview
                                        templateKey={selectedKey}
                                        theme={draft}
                                        compact={viewport === 'mobile'}
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="mt-4 rounded-2xl border bg-white p-5 shadow-sm">
                            <div className="flex items-center gap-2">
                                <TriangleAlert
                                    className={cn(
                                        'size-5',
                                        liveContrast.every((item) => item.passes)
                                            ? 'text-emerald-600'
                                            : 'text-amber-600',
                                    )}
                                />
                                <h3 className="font-semibold">Contrast check</h3>
                            </div>
                            <div className="mt-4 grid gap-2 sm:grid-cols-2">
                                {liveContrast.map((item) => (
                                    <div
                                        key={item.pair}
                                        className={cn(
                                            'rounded-xl p-3 text-sm',
                                            item.passes
                                                ? 'bg-emerald-50 text-emerald-800'
                                                : 'bg-amber-50 text-amber-800',
                                        )}
                                    >
                                        <div className="flex justify-between gap-2">
                                            <span>{item.pair}</span>
                                            <strong>{item.ratio}:1</strong>
                                        </div>
                                        <p className="mt-1 text-xs opacity-70">
                                            {item.passes ? 'WCAG AA passes' : 'Needs at least 4.5:1'}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </DashboardLayout>
    );
}

function ControlGroup({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="mt-6 border-t pt-5">
            <h3 className="mb-3 text-xs font-bold tracking-wide text-slate-500 uppercase">{title}</h3>
            <div className="space-y-3">{children}</div>
        </div>
    );
}
function ColorField({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="block">
            <span className="mb-1 block text-xs font-medium text-slate-600 capitalize">{label}</span>
            <span className="flex items-center rounded-lg border p-1.5">
                <input
                    type="color"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className="size-7 rounded border-0 bg-transparent"
                />
                <input
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    pattern="#[0-9a-fA-F]{6}"
                    className="min-w-0 flex-1 px-1 font-mono text-[11px] outline-none"
                />
            </span>
        </label>
    );
}
function SelectField({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string;
    options: string[];
    onChange: (value: string) => void;
}) {
    return (
        <label className="grid grid-cols-[1fr_9rem] items-center gap-3 text-sm">
            <span className="text-slate-600">{label}</span>
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="rounded-lg border px-2 py-2 text-xs"
            >
                {options.map((item) => (
                    <option key={item} value={item}>
                        {item}
                    </option>
                ))}
            </select>
        </label>
    );
}
function luminance(hex: string) {
    const channels = [0, 2, 4]
        .map((index) => parseInt(hex.replace('#', '').slice(index, index + 2), 16) / 255)
        .map((value) => (value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4));
    return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
}
function ratio(a: string, b: string) {
    const values = [luminance(a), luminance(b)];
    return Math.round(((Math.max(...values) + 0.05) / (Math.min(...values) + 0.05)) * 100) / 100;
}
function analyzeContrast(theme: ThemeTokens): ContrastResult[] {
    return [
        ['Body text / background', theme.text, theme.background],
        ['Heading / background', theme.heading, theme.background],
        ['Primary button', theme.primary_foreground, theme.primary],
        ['Accent button', theme.accent_foreground, theme.accent],
    ].map(([pair, foreground, background]) => {
        const value = ratio(foreground, background);
        return { pair, ratio: value, required: 4.5, passes: value >= 4.5 };
    });
}
