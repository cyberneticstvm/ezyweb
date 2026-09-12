import type { CSSProperties } from 'react';
import { cn } from '../lib/utils';

export interface ThemeTokens {
    primary: string;
    primary_foreground: string;
    secondary: string;
    secondary_foreground: string;
    accent: string;
    accent_foreground: string;
    background: string;
    surface: string;
    heading: string;
    text: string;
    muted_text: string;
    border: string;
    heading_font: string;
    body_font: string;
    button_style: string;
    button_radius: string;
    card_radius: string;
    shadow_strength: string;
    container_width: string;
    section_spacing: string;
}

interface Props {
    templateKey: string;
    theme: ThemeTokens;
    compact?: boolean;
}

export function TemplatePreview({ templateKey, theme, compact = false }: Props) {
    const styles = {
        '--preview-primary': theme.primary,
        '--preview-primary-fg': theme.primary_foreground,
        '--preview-secondary': theme.secondary,
        '--preview-accent': theme.accent,
        '--preview-accent-fg': theme.accent_foreground,
        '--preview-bg': theme.background,
        '--preview-surface': theme.surface,
        '--preview-heading': theme.heading,
        '--preview-text': theme.text,
        '--preview-muted': theme.muted_text,
        '--preview-border': theme.border,
        '--preview-button-radius': theme.button_radius,
        '--preview-card-radius': theme.card_radius,
        fontFamily: theme.body_font,
    } as CSSProperties;

    if (templateKey === 'localpro')
        return <LocalProPreview theme={theme} styles={styles} compact={compact} />;
    if (templateKey === 'canvas') return <CanvasPreview theme={theme} styles={styles} compact={compact} />;
    return <HorizonPreview theme={theme} styles={styles} compact={compact} />;
}

function PreviewButton({ children, secondary = false }: { children: string; secondary?: boolean }) {
    return (
        <span
            className={cn(
                'inline-flex px-3 py-2 text-[10px] font-bold',
                secondary ? 'border bg-transparent' : 'text-[var(--preview-primary-fg)]',
            )}
            style={{
                borderRadius: 'var(--preview-button-radius)',
                background: secondary ? 'transparent' : 'var(--preview-primary)',
                borderColor: 'var(--preview-primary)',
                color: secondary ? 'var(--preview-primary)' : undefined,
            }}
        >
            {children}
        </span>
    );
}

function HorizonPreview({
    theme,
    styles,
    compact,
}: {
    theme: ThemeTokens;
    styles: CSSProperties;
    compact: boolean;
}) {
    return (
        <div
            style={styles}
            className="min-h-full overflow-hidden bg-[var(--preview-bg)] text-[var(--preview-text)]"
        >
            <header className="flex items-center justify-between border-b border-[var(--preview-border)] px-5 py-3">
                <strong style={{ color: theme.heading, fontFamily: theme.heading_font }}>Northstar</strong>
                <div className="flex items-center gap-4 text-[9px] text-[var(--preview-muted)]">
                    <span>Services</span>
                    <span>About</span>
                    <PreviewButton>Let’s talk</PreviewButton>
                </div>
            </header>
            <section
                className={cn(
                    'grid items-center gap-6 px-6 py-8',
                    !compact && 'sm:grid-cols-2 sm:px-9 sm:py-12',
                )}
            >
                <div>
                    <p className="text-[9px] font-bold tracking-wider text-[var(--preview-primary)] uppercase">
                        Strategy with momentum
                    </p>
                    <h2
                        className="mt-3 text-2xl leading-tight font-bold"
                        style={{ color: theme.heading, fontFamily: theme.heading_font }}
                    >
                        Build what matters. Grow with clarity.
                    </h2>
                    <p className="mt-3 text-[10px] leading-5 text-[var(--preview-muted)]">
                        A confident digital partner for ambitious teams ready to turn ideas into measurable
                        progress.
                    </p>
                    <div className="mt-5 flex gap-2">
                        <PreviewButton>Start a project</PreviewButton>
                        <PreviewButton secondary>Our work</PreviewButton>
                    </div>
                </div>
                <div className="relative aspect-[4/3] overflow-hidden rounded-[var(--preview-card-radius)] bg-[var(--preview-primary)] opacity-90">
                    <div className="absolute top-8 right-6 h-2/3 w-2/3 rounded-full bg-[var(--preview-accent)] opacity-80" />
                    <div className="absolute bottom-4 left-4 h-2/5 w-3/5 rounded-lg bg-white/20" />
                </div>
            </section>
            <section className="bg-[var(--preview-surface)] px-6 py-7">
                <h3
                    className="text-sm font-bold"
                    style={{ color: theme.heading, fontFamily: theme.heading_font }}
                >
                    Expertise for every stage
                </h3>
                <div className={cn('mt-4 grid gap-3', !compact && 'grid-cols-3')}>
                    {['Direction', 'Design', 'Growth'].map((item) => (
                        <div
                            key={item}
                            className="border border-[var(--preview-border)] bg-[var(--preview-bg)] p-3"
                            style={{ borderRadius: theme.card_radius }}
                        >
                            <span className="block size-5 rounded bg-[var(--preview-primary)]" />
                            <strong className="mt-3 block text-[10px]" style={{ color: theme.heading }}>
                                {item}
                            </strong>
                            <span className="mt-2 block h-1.5 w-4/5 rounded bg-[var(--preview-border)]" />
                        </div>
                    ))}
                </div>
            </section>
        </div>
    );
}

function LocalProPreview({
    theme,
    styles,
    compact,
}: {
    theme: ThemeTokens;
    styles: CSSProperties;
    compact: boolean;
}) {
    return (
        <div
            style={styles}
            className="min-h-full overflow-hidden bg-[var(--preview-bg)] text-[var(--preview-text)]"
        >
            <div className="flex justify-between bg-[var(--preview-primary)] px-5 py-2 text-[8px] text-[var(--preview-primary-fg)]">
                <span>Open today · 9am–7pm</span>
                <span>Call +91 98765 43210</span>
            </div>
            <header className="flex items-center justify-between bg-[var(--preview-surface)] px-5 py-3">
                <strong style={{ color: theme.primary, fontFamily: theme.heading_font }}>CarePoint</strong>
                <PreviewButton>Book a visit</PreviewButton>
            </header>
            <section className={cn('grid items-center gap-5 px-6 py-8', !compact && 'sm:grid-cols-2')}>
                <div>
                    <span className="rounded-full bg-[var(--preview-accent)] px-3 py-1 text-[8px] font-bold text-[var(--preview-accent-fg)]">
                        Trusted locally since 2012
                    </span>
                    <h2
                        className="mt-4 text-2xl leading-tight font-bold"
                        style={{ color: theme.heading, fontFamily: theme.heading_font }}
                    >
                        Friendly care, close to home.
                    </h2>
                    <p className="mt-3 text-[10px] leading-5 text-[var(--preview-muted)]">
                        Helpful professionals, clear answers, and convenient appointments for your whole
                        family.
                    </p>
                    <div className="mt-5 flex gap-2">
                        <PreviewButton>Call now</PreviewButton>
                        <PreviewButton secondary>Directions</PreviewButton>
                    </div>
                </div>
                <div className="mx-auto grid aspect-square w-4/5 place-items-center rounded-full bg-[var(--preview-primary)]/15">
                    <div className="h-3/5 w-3/5 rounded-t-full rounded-b-[40%] bg-[var(--preview-primary)] opacity-80" />
                </div>
            </section>
            <section className="px-6 py-7">
                <h3
                    className="text-center text-sm font-bold"
                    style={{ color: theme.heading, fontFamily: theme.heading_font }}
                >
                    How can we help?
                </h3>
                <div className={cn('mt-4 grid gap-3', !compact && 'grid-cols-3')}>
                    {['Consultation', 'Diagnostics', 'Ongoing care'].map((item) => (
                        <div
                            key={item}
                            className="bg-[var(--preview-surface)] p-4 text-center shadow-sm"
                            style={{ borderRadius: theme.card_radius }}
                        >
                            <span className="mx-auto block size-6 rounded-full bg-[var(--preview-accent)]" />
                            <strong className="mt-3 block text-[10px]" style={{ color: theme.heading }}>
                                {item}
                            </strong>
                        </div>
                    ))}
                </div>
            </section>
        </div>
    );
}

function CanvasPreview({
    theme,
    styles,
    compact,
}: {
    theme: ThemeTokens;
    styles: CSSProperties;
    compact: boolean;
}) {
    return (
        <div
            style={styles}
            className="min-h-full overflow-hidden bg-[var(--preview-bg)] text-[var(--preview-text)]"
        >
            <header className="flex items-center justify-between px-5 py-4">
                <strong
                    className="tracking-widest uppercase"
                    style={{ color: theme.heading, fontFamily: theme.heading_font }}
                >
                    Atelier N°7
                </strong>
                <span className="text-[9px] text-[var(--preview-muted)]">Work · Studio · Contact</span>
            </header>
            <section
                className={cn(
                    'grid gap-0 bg-[var(--preview-primary)] text-[var(--preview-primary-fg)]',
                    !compact && 'sm:grid-cols-[1.05fr_.95fr]',
                )}
            >
                <div className="p-7 sm:p-10">
                    <p className="text-[8px] tracking-[.25em] uppercase opacity-60">
                        Independent creative practice
                    </p>
                    <h2 className="mt-5 text-3xl leading-tight" style={{ fontFamily: theme.heading_font }}>
                        Ideas made visible.
                    </h2>
                    <p className="mt-4 max-w-xs text-[10px] leading-5 opacity-65">
                        Identity, spaces, and digital experiences shaped with purpose and restraint.
                    </p>
                    <div className="mt-6">
                        <span className="border-b border-[var(--preview-accent)] pb-1 text-[9px] font-bold">
                            Explore selected work →
                        </span>
                    </div>
                </div>
                <div className="relative min-h-48 bg-[var(--preview-secondary)]">
                    <div className="absolute inset-[15%] rounded-full bg-[var(--preview-accent)] opacity-70" />
                    <div className="absolute right-[12%] bottom-[10%] h-1/2 w-1/3 bg-white/20" />
                </div>
            </section>
            <section className="px-5 py-7">
                <div className="flex items-end justify-between">
                    <h3 className="text-lg" style={{ color: theme.heading, fontFamily: theme.heading_font }}>
                        Selected work
                    </h3>
                    <span className="text-[8px] text-[var(--preview-muted)]">View all projects</span>
                </div>
                <div className={cn('mt-4 grid gap-2', !compact && 'grid-cols-[1fr_1.35fr_.8fr]')}>
                    <div className="aspect-[4/3] bg-[var(--preview-border)]" />
                    <div className="relative aspect-[4/3] bg-[var(--preview-accent)]/35">
                        <div className="absolute inset-1/4 rounded-full bg-[var(--preview-accent)]" />
                    </div>
                    <div className="aspect-[4/3] bg-[var(--preview-secondary)]/40" />
                </div>
            </section>
        </div>
    );
}
