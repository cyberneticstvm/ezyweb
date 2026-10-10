import type { CSSProperties } from 'react';

import { cn } from '../../lib/utils';
import type { ThemeTokens } from '../template-preview';

export interface BuilderSection {
    id: number;
    type: string;
    variant: string;
    anchor: string | null;
    content: SectionContent;
    design_settings: { surface: string; spacing: string; alignment: string };
    is_enabled: boolean;
    show_on_desktop: boolean;
    show_on_tablet: boolean;
    show_on_mobile: boolean;
    sort_order: number;
    updated_at?: string;
}

export interface HeroSlide extends Record<string, string> {
    image_id: string;
    mobile_image_id: string;
    alt: string;
    eyebrow: string;
    heading: string;
    description: string;
    primary_label: string;
    primary_url: string;
}

export interface CounterItem extends Record<string, string> {
    value: string;
    prefix: string;
    suffix: string;
    label: string;
    description: string;
}

export interface StatisticItem extends Record<string, string> {
    value: string;
    prefix: string;
    suffix: string;
    label: string;
    description: string;
    trend_text: string;
    trend_direction: string;
    source_label: string;
    source_url: string;
}

export interface BrandLogo extends Record<string, string> {
    image_id: string;
    name: string;
    alt: string;
    url: string;
}

export interface FeatureItem extends Record<string, string> {
    icon: string;
    image_id: string;
    image_alt: string;
    title: string;
    description: string;
    link_label: string;
    link_url: string;
    is_featured: string;
}

export interface ProcessItem extends Record<string, string> {
    number: string;
    icon: string;
    image_id: string;
    image_alt: string;
    title: string;
    description: string;
    meta: string;
    link_label: string;
    link_url: string;
}

export interface PricingItem extends Record<string, string> {
    name: string;
    badge: string;
    description: string;
    currency: string;
    price: string;
    original_price: string;
    period: string;
    features: string;
    button_label: string;
    button_url: string;
    is_featured: string;
}

export type SectionContent = Record<
    string,
    | string
    | HeroSlide[]
    | CounterItem[]
    | StatisticItem[]
    | BrandLogo[]
    | FeatureItem[]
    | ProcessItem[]
    | PricingItem[]
>;

export interface RegistryDefinition {
    type: string;
    label: string;
    category: string;
    description: string;
    variants: { key: string; label: string }[];
    fields: {
        key: string;
        label: string;
        type:
            | 'text'
            | 'textarea'
            | 'url'
            | 'richtext'
            | 'image-text'
            | 'video'
            | 'slides'
            | 'counters'
            | 'statistics'
            | 'spacer-divider'
            | 'logos'
            | 'products'
            | 'testimonials'
            | 'portfolio'
            | 'gallery'
            | 'team'
            | 'faqs'
            | 'blog-posts'
            | 'news'
            | 'events'
            | 'careers'
            | 'features'
            | 'process'
            | 'pricing'
            | 'contact'
            | 'newsletter'
            | 'map';
        max: number;
    }[];
    default_content: SectionContent;
    default_design: { surface: string; spacing: string; alignment: string };
}

export interface ProductOption {
    slug: string;
    name: string;
    is_active: boolean;
    is_featured: boolean;
    short_description: string | null;
    featured_image: string | null;
    price: string | null;
    show_price: boolean;
    enquiry_label: string | null;
}

export interface TestimonialOption {
    client_name: string;
    client_role: string | null;
    company: string | null;
    photo: string | null;
    quote: string;
    rating: number | null;
    is_active: boolean;
    is_featured: boolean;
}

export interface PortfolioOption {
    slug: string;
    title: string;
    category: string | null;
    client: string | null;
    project_date: string | null;
    location: string | null;
    short_description: string | null;
    featured_image: string | null;
    project_url: string | null;
    is_active: boolean;
    is_featured: boolean;
}

export interface GalleryOption {
    title: string;
    image: string;
    alt_text: string;
    caption: string | null;
    category: string | null;
    aspect_ratio: 'auto' | 'square' | 'portrait' | 'landscape';
    is_active: boolean;
}

export interface TeamOption {
    key: string;
    name: string;
    role: string | null;
    photo: string | null;
    bio: string | null;
    email: string | null;
    phone: string | null;
    linkedin_url: string | null;
    is_active: boolean;
    is_featured: boolean;
}

export interface FaqOption {
    key: string;
    question: string;
    answer: string;
    category: string | null;
    is_active: boolean;
}

export interface BlogOption {
    slug: string;
    title: string;
    excerpt: string | null;
    featured_image: string | null;
    author_name: string | null;
    category: string | null;
    tags: string[];
    status: string;
    published_at: string | null;
    is_published: boolean;
    reading_minutes: number;
}
export type NewsOption = BlogOption;
export interface EventOption {
    slug: string;
    title: string;
    excerpt: string | null;
    featured_image: string | null;
    category: string | null;
    status: string;
    published_at: string | null;
    is_published: boolean;
    event_start_date: string | null;
    event_start_time: string | null;
    event_end_date: string | null;
    event_end_time: string | null;
    venue: string | null;
    address: string | null;
    registration_url: string | null;
    online_event_url: string | null;
    is_upcoming: boolean;
}
export interface CareerOption {
    slug: string;
    title: string;
    department: string | null;
    location: string | null;
    employment_type: string | null;
    workplace_type: string | null;
    summary: string | null;
    salary_display: string | null;
    closing_date: string | null;
    application_email: string | null;
    is_active: boolean;
    is_open: boolean;
}

export function BuilderPreview({
    websiteName,
    templateKey,
    sections,
    registry,
    theme,
    viewport,
    selectedId,
    onSelect,
    productOptions = [],
    testimonialOptions = [],
    portfolioOptions = [],
    galleryOptions = [],
    teamOptions = [],
    faqOptions = [],
    blogOptions = [],
    newsOptions = [],
    eventOptions = [],
    careerOptions = [],
}: {
    websiteName: string;
    templateKey: string;
    sections: BuilderSection[];
    registry: Record<string, RegistryDefinition>;
    theme: ThemeTokens;
    viewport: 'desktop' | 'tablet' | 'mobile';
    selectedId: number | null;
    onSelect: (id: number) => void;
    productOptions?: ProductOption[];
    testimonialOptions?: TestimonialOption[];
    portfolioOptions?: PortfolioOption[];
    galleryOptions?: GalleryOption[];
    teamOptions?: TeamOption[];
    faqOptions?: FaqOption[];
    blogOptions?: BlogOption[];
    newsOptions?: NewsOption[];
    eventOptions?: EventOption[];
    careerOptions?: CareerOption[];
}) {
    const variables = {
        '--theme-primary': theme.primary,
        '--theme-primary-foreground': theme.primary_foreground,
        '--theme-accent': theme.accent,
        '--theme-accent-foreground': theme.accent_foreground,
        '--theme-background': theme.background,
        '--theme-surface': theme.surface,
        '--theme-heading': theme.heading,
        '--theme-text': theme.text,
        '--theme-muted-text': theme.muted_text,
        '--theme-border': theme.border,
        '--theme-radius': theme.card_radius,
    } as CSSProperties;
    const visible = sections.filter(
        (section) =>
            section.is_enabled &&
            (viewport === 'desktop'
                ? section.show_on_desktop
                : viewport === 'tablet'
                  ? section.show_on_tablet
                  : section.show_on_mobile),
    );

    return (
        <div
            style={variables}
            className={cn(
                'mx-auto min-h-[34rem] overflow-hidden bg-[var(--theme-background)] text-[var(--theme-text)] shadow-2xl transition-all',
                viewport === 'desktop' && 'w-full',
                viewport === 'tablet' && 'max-w-[48rem]',
                viewport === 'mobile' && 'max-w-[23.5rem]',
                templateKey === 'canvas' ? 'rounded-none' : 'rounded-xl',
            )}
        >
            <header className="flex items-center justify-between border-b border-[var(--theme-border)] px-5 py-4">
                <strong className="text-sm text-[var(--theme-heading)]">{websiteName}</strong>
                <div className="flex gap-3 text-[10px] text-[var(--theme-muted-text)]">
                    <span>Home</span>
                    <span>About</span>
                    <span>Contact</span>
                </div>
            </header>
            {visible.length === 0 ? (
                <div className="grid min-h-[28rem] place-items-center p-8 text-center">
                    <div>
                        <p className="font-semibold text-[var(--theme-heading)]">
                            Nothing visible in this preview
                        </p>
                        <p className="mt-2 text-sm text-[var(--theme-muted-text)]">
                            Enable a section or update its device visibility.
                        </p>
                    </div>
                </div>
            ) : (
                visible.map((section) => (
                    <SectionBlock
                        key={section.id}
                        section={section}
                        label={registry[section.type]?.label ?? section.type}
                        templateKey={templateKey}
                        selected={selectedId === section.id}
                        onSelect={() => onSelect(section.id)}
                        productOptions={productOptions}
                        testimonialOptions={testimonialOptions}
                        portfolioOptions={portfolioOptions}
                        galleryOptions={galleryOptions}
                        teamOptions={teamOptions}
                        faqOptions={faqOptions}
                        blogOptions={blogOptions}
                        newsOptions={newsOptions}
                        eventOptions={eventOptions}
                        careerOptions={careerOptions}
                    />
                ))
            )}
            <footer className="border-t border-[var(--theme-border)] px-5 py-5 text-xs text-[var(--theme-muted-text)]">
                © {new Date().getFullYear()} {websiteName}. Draft preview by Ezyweb.
            </footer>
        </div>
    );
}

function SectionBlock({
    section,
    label,
    templateKey,
    selected,
    onSelect,
    productOptions,
    testimonialOptions,
    portfolioOptions,
    galleryOptions,
    teamOptions,
    faqOptions,
    blogOptions,
    newsOptions,
    eventOptions,
    careerOptions,
}: {
    section: BuilderSection;
    label: string;
    templateKey: string;
    selected: boolean;
    onSelect: () => void;
    productOptions: ProductOption[];
    testimonialOptions: TestimonialOption[];
    portfolioOptions: PortfolioOption[];
    galleryOptions: GalleryOption[];
    teamOptions: TeamOption[];
    faqOptions: FaqOption[];
    blogOptions: BlogOption[];
    newsOptions: NewsOption[];
    eventOptions: EventOption[];
    careerOptions: CareerOption[];
}) {
    const content = section.content;
    const primary = section.design_settings.surface === 'primary';
    const dark = section.design_settings.surface === 'dark';
    const muted = section.design_settings.surface === 'muted';

    return (
        <section
            id={section.anchor ?? undefined}
            onClick={onSelect}
            className={cn(
                'relative cursor-pointer transition-shadow outline-none',
                section.type !== 'spacer-divider' && 'border-b border-[var(--theme-border)]',
                section.type === 'spacer-divider' && 'px-5 py-0',
                section.type !== 'spacer-divider' &&
                    section.design_settings.spacing === 'compact' &&
                    'px-5 py-7',
                section.type !== 'spacer-divider' &&
                    section.design_settings.spacing === 'normal' &&
                    'px-5 py-11',
                section.type !== 'spacer-divider' &&
                    section.design_settings.spacing === 'spacious' &&
                    'px-5 py-16',
                primary && 'bg-[var(--theme-primary)] text-[var(--theme-primary-foreground)]',
                dark && 'bg-slate-950 text-white',
                muted && 'bg-[var(--theme-surface)]',
                selected && 'ring-2 ring-blue-500 ring-inset',
            )}
            tabIndex={0}
            role="button"
            aria-label={`Edit ${label} section`}
            onKeyDown={(event) => event.key === 'Enter' && onSelect()}
        >
            <span className="absolute top-2 right-2 rounded bg-black/60 px-2 py-1 text-[9px] font-semibold text-white">
                {label} · {section.variant}
            </span>
            <div
                className={cn(
                    'mx-auto max-w-4xl',
                    section.design_settings.alignment === 'center' && 'text-center',
                    section.design_settings.alignment === 'right' && 'text-right',
                    !['hero', 'hero-slider', 'cta', 'contact', 'map', 'statistics'].includes(section.type) &&
                        templateKey === 'horizon' &&
                        section.variant === 'split' &&
                        'grid items-center gap-6 sm:grid-cols-2',
                )}
            >
                {section.type === 'hero' ? (
                    <HeroPreview content={content} label={label} />
                ) : section.type === 'cta' ? (
                    <CtaPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'about' ? (
                    <AboutPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'mission-vision' ? (
                    <MissionVisionPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'counters' ? (
                    <CountersPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'statistics' ? (
                    <StatisticsPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'features' ? (
                    <FeaturesPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'process' ? (
                    <ProcessPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'pricing' ? (
                    <PricingPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'contact' ? (
                    <ContactPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'newsletter' ? (
                    <NewsletterPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'map' ? (
                    <MapPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'brand-carousel' ? (
                    <BrandCarouselPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'logo-cloud' ? (
                    <LogoCloudPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'products' ? (
                    <ProductsPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        products={productOptions}
                    />
                ) : section.type === 'testimonials' ? (
                    <TestimonialsPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        testimonials={testimonialOptions}
                    />
                ) : section.type === 'portfolio' ? (
                    <PortfolioPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        projects={portfolioOptions}
                    />
                ) : section.type === 'gallery' ? (
                    <GalleryPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        images={galleryOptions}
                    />
                ) : section.type === 'team' ? (
                    <TeamPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        members={teamOptions}
                    />
                ) : section.type === 'faq' ? (
                    <FaqPreview content={content} label={label} variant={section.variant} faqs={faqOptions} />
                ) : section.type === 'blog-posts' ? (
                    <BlogPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        posts={blogOptions}
                    />
                ) : section.type === 'news' ? (
                    <NewsPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        articles={newsOptions}
                    />
                ) : section.type === 'events' ? (
                    <EventsPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        events={eventOptions}
                    />
                ) : section.type === 'careers' ? (
                    <CareersPreview
                        content={content}
                        label={label}
                        variant={section.variant}
                        jobs={careerOptions}
                    />
                ) : section.type === 'hero-slider' ? (
                    <HeroSliderPreview content={content} label={label} primary={primary} dark={dark} />
                ) : section.type === 'rich-text' ? (
                    <RichTextPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'image-text' ? (
                    <ImageTextPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'video' ? (
                    <VideoPreview content={content} label={label} variant={section.variant} />
                ) : section.type === 'spacer-divider' ? (
                    <SpacerDividerPreview content={content} variant={section.variant} />
                ) : (
                    <div>
                        {textValue(content.eyebrow) && (
                            <p className="mb-2 text-[10px] font-bold tracking-[0.18em] text-[var(--theme-accent)] uppercase">
                                {textValue(content.eyebrow)}
                            </p>
                        )}
                        <h2
                            className={cn(
                                'text-xl font-bold tracking-tight sm:text-3xl',
                                !primary && !dark && 'text-[var(--theme-heading)]',
                                templateKey === 'canvas' && 'font-serif',
                            )}
                        >
                            {textValue(content.heading) || label}
                        </h2>
                        {textValue(content.description) && (
                            <p className="mt-3 text-sm leading-6 opacity-80">
                                {textValue(content.description)}
                            </p>
                        )}
                        {textValue(content.primary_label) && (
                            <span className="mt-5 inline-flex rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-4 py-2 text-xs font-bold text-[var(--theme-accent-foreground)]">
                                {textValue(content.primary_label)}
                            </span>
                        )}
                    </div>
                )}
                {!['hero', 'hero-slider'].includes(section.type) &&
                    templateKey === 'horizon' &&
                    section.variant === 'split' && (
                        <div className="hidden aspect-[4/3] rounded-[var(--theme-radius)] bg-gradient-to-br from-[var(--theme-primary)] to-[var(--theme-accent)] opacity-20 sm:block" />
                    )}
            </div>
        </section>
    );
}

function NewsletterPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const centered = textValue(content.form_alignment) === 'center';
    const showName = textValue(content.show_name_field) === '1';

    return (
        <div
            className={cn(
                'grid gap-5',
                variant === 'inline' && 'items-center sm:grid-cols-[minmax(0,1fr)_minmax(18rem,1fr)]',
                variant === 'card' &&
                    'rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] p-5',
            )}
        >
            <div className={cn(centered && variant !== 'inline' && 'text-center')}>
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 text-sm leading-6 opacity-80">{textValue(content.description)}</p>
                )}
            </div>
            <div
                className={cn(
                    'rounded-[var(--theme-radius)] bg-white p-4 text-left text-slate-900 shadow-sm',
                    centered && 'mx-auto text-center',
                    textValue(content.form_width) === 'compact' && 'w-full max-w-sm',
                    textValue(content.form_width) === 'medium' && 'w-full max-w-xl',
                    textValue(content.form_width) === 'wide' && 'w-full max-w-3xl',
                )}
            >
                {textValue(content.show_form_heading) === '1' && textValue(content.form_heading) && (
                    <h3 className="text-sm font-bold">{textValue(content.form_heading)}</h3>
                )}
                {textValue(content.show_form_description) === '1' && textValue(content.form_description) && (
                    <p className="mt-1 text-[10px] text-slate-500">{textValue(content.form_description)}</p>
                )}
                <div className="mt-3 grid gap-2 sm:grid-cols-[1fr_auto]">
                    {showName && (
                        <span className="rounded border border-slate-200 px-3 py-2 text-[10px] text-slate-400">
                            {textValue(content.name_placeholder) || textValue(content.name_label) || 'Name'}
                        </span>
                    )}
                    <span className="rounded border border-slate-200 px-3 py-2 text-[10px] text-slate-400">
                        {textValue(content.email_placeholder) || 'you@example.com'}
                    </span>
                    <span className="rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-4 py-2 text-center text-[10px] font-bold text-white">
                        {textValue(content.button_label) || 'Subscribe'}
                    </span>
                </div>
                <p className="mt-3 text-[9px] text-slate-500">
                    □ {textValue(content.consent_label) || 'I agree to receive newsletter emails.'}
                    {textValue(content.privacy_label) && ` ${textValue(content.privacy_label)}`}
                </p>
                {textValue(content.show_privacy_note) !== '0' && textValue(content.privacy_note) && (
                    <p className="mt-2 text-[9px] text-slate-400">{textValue(content.privacy_note)}</p>
                )}
            </div>
        </div>
    );
}

function BlogPreview({
    content,
    label,
    variant,
    posts,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    posts: BlogOption[];
}) {
    const selected = new Set(textValue(content.blog_slugs).split('\n').filter(Boolean));
    let visible = posts.filter(
        (post) =>
            post.is_published &&
            (content.blog_selection_mode !== 'selected' || selected.has(post.slug)) &&
            (!textValue(content.blog_category) || post.category === textValue(content.blog_category)),
    );
    if (content.blog_sort === 'oldest')
        visible = [...visible].sort(
            (a, b) => new Date(a.published_at!).getTime() - new Date(b.published_at!).getTime(),
        );
    else if (content.blog_sort === 'title')
        visible = [...visible].sort((a, b) => a.title.localeCompare(b.title));
    else
        visible = [...visible].sort(
            (a, b) => new Date(b.published_at!).getTime() - new Date(a.published_at!).getTime(),
        );
    visible = visible.slice(0, Number(textValue(content.blog_limit)) || 6);
    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div
                    className={cn(
                        'grid gap-3 sm:grid-cols-3',
                        textValue(content.blog_columns) === '2' && 'sm:grid-cols-2',
                        textValue(content.blog_columns) === '4' && 'sm:grid-cols-4',
                    )}
                >
                    {visible.map((post, index) => (
                        <article
                            key={post.slug}
                            className={cn(
                                'overflow-hidden rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] text-left',
                                variant === 'featured' && index === 0 && 'sm:col-span-2',
                            )}
                        >
                            {content.show_images !== '0' && post.featured_image && (
                                <img
                                    src={post.featured_image}
                                    alt={post.title}
                                    className={cn(
                                        'aspect-video w-full',
                                        content.image_fit === 'contain' ? 'object-contain' : 'object-cover',
                                    )}
                                />
                            )}
                            <div className="p-4">
                                {content.show_categories !== '0' && post.category && (
                                    <p className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                        {post.category}
                                    </p>
                                )}
                                <h3 className="mt-1 text-sm font-bold text-[var(--theme-heading)]">
                                    {post.title}
                                </h3>
                                {content.show_excerpts !== '0' && post.excerpt && (
                                    <p className="mt-2 line-clamp-3 text-xs leading-5 opacity-70">
                                        {post.excerpt}
                                    </p>
                                )}
                                <p className="mt-3 flex flex-wrap gap-2 text-[10px] opacity-60">
                                    {content.show_authors !== '0' && post.author_name && (
                                        <span>By {post.author_name}</span>
                                    )}
                                    {content.show_dates !== '0' && post.published_at && (
                                        <span>{new Date(post.published_at).toLocaleDateString()}</span>
                                    )}
                                    {content.show_reading_time !== '0' && (
                                        <span>{post.reading_minutes} min read</span>
                                    )}
                                </p>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add and publish blog posts in Content → Blog or adjust this section’s filters.
                </p>
            )}
        </div>
    );
}

function NewsPreview({
    content,
    label,
    variant,
    articles,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    articles: NewsOption[];
}) {
    const selected = new Set(textValue(content.news_slugs).split('\n').filter(Boolean));
    let visible = articles.filter(
        (article) =>
            article.is_published &&
            (content.news_selection_mode !== 'selected' || selected.has(article.slug)) &&
            (!textValue(content.news_category) || article.category === textValue(content.news_category)),
    );
    if (content.news_sort === 'oldest')
        visible = [...visible].sort(
            (a, b) => new Date(a.published_at!).getTime() - new Date(b.published_at!).getTime(),
        );
    else if (content.news_sort === 'title')
        visible = [...visible].sort((a, b) => a.title.localeCompare(b.title));
    else
        visible = [...visible].sort(
            (a, b) => new Date(b.published_at!).getTime() - new Date(a.published_at!).getTime(),
        );
    visible = visible.slice(0, Number(textValue(content.news_limit)) || 6);
    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div
                    className={cn(
                        'grid gap-3 sm:grid-cols-3',
                        textValue(content.news_columns) === '2' && 'sm:grid-cols-2',
                        textValue(content.news_columns) === '4' && 'sm:grid-cols-4',
                        variant === 'list' && 'sm:grid-cols-1',
                    )}
                >
                    {visible.map((article) => (
                        <article
                            key={article.slug}
                            className={cn(
                                'overflow-hidden rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] text-left',
                                variant === 'list' && 'sm:grid sm:grid-cols-[minmax(9rem,30%)_1fr]',
                            )}
                        >
                            {content.show_images !== '0' && article.featured_image && (
                                <img
                                    src={article.featured_image}
                                    alt={article.title}
                                    className={cn(
                                        'aspect-video h-full w-full',
                                        content.image_fit === 'contain' ? 'object-contain' : 'object-cover',
                                    )}
                                />
                            )}
                            <div className="p-4">
                                {content.show_categories !== '0' && article.category && (
                                    <p className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                        {article.category}
                                    </p>
                                )}
                                <h3 className="mt-1 text-sm font-bold text-[var(--theme-heading)]">
                                    {article.title}
                                </h3>
                                {content.show_excerpts !== '0' && article.excerpt && (
                                    <p className="mt-2 line-clamp-3 text-xs leading-5 opacity-70">
                                        {article.excerpt}
                                    </p>
                                )}
                                <p className="mt-3 flex flex-wrap gap-2 text-[10px] opacity-60">
                                    {content.show_authors !== '0' && article.author_name && (
                                        <span>By {article.author_name}</span>
                                    )}
                                    {content.show_dates !== '0' && article.published_at && (
                                        <span>{new Date(article.published_at).toLocaleDateString()}</span>
                                    )}
                                    {content.show_reading_time === '1' && (
                                        <span>{article.reading_minutes} min read</span>
                                    )}
                                </p>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add and publish articles in Content → News or adjust this section’s filters.
                </p>
            )}
        </div>
    );
}

function EventsPreview({
    content,
    label,
    variant,
    events,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    events: EventOption[];
}) {
    const selected = new Set(textValue(content.event_slugs).split('\n').filter(Boolean));
    let visible = events.filter(
        (event) =>
            event.is_published &&
            (content.event_selection_mode !== 'selected' || selected.has(event.slug)) &&
            (!textValue(content.event_category) || event.category === textValue(content.event_category)) &&
            (content.event_timeframe === 'all' ||
                (content.event_timeframe === 'past' ? !event.is_upcoming : event.is_upcoming)),
    );
    if (content.event_sort === 'latest')
        visible = [...visible].sort((a, b) =>
            (b.event_start_date ?? '').localeCompare(a.event_start_date ?? ''),
        );
    else if (content.event_sort === 'title')
        visible = [...visible].sort((a, b) => a.title.localeCompare(b.title));
    else
        visible = [...visible].sort((a, b) =>
            (a.event_start_date ?? '').localeCompare(b.event_start_date ?? ''),
        );
    visible = visible.slice(0, Number(textValue(content.event_limit)) || 6);
    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div
                    className={cn(
                        'grid gap-3 sm:grid-cols-3',
                        textValue(content.event_columns) === '2' && 'sm:grid-cols-2',
                        textValue(content.event_columns) === '4' && 'sm:grid-cols-4',
                        variant === 'list' && 'sm:grid-cols-1',
                    )}
                >
                    {visible.map((event) => (
                        <article
                            key={event.slug}
                            className={cn(
                                'overflow-hidden rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] text-left',
                                variant === 'list' && 'sm:grid sm:grid-cols-[minmax(9rem,30%)_1fr]',
                            )}
                        >
                            {content.show_images !== '0' && event.featured_image && (
                                <img
                                    src={event.featured_image}
                                    alt={event.title}
                                    className={cn(
                                        'aspect-video h-full w-full',
                                        content.image_fit === 'contain' ? 'object-contain' : 'object-cover',
                                    )}
                                />
                            )}
                            <div className="p-4">
                                {content.show_categories !== '0' && event.category && (
                                    <p className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                        {event.category}
                                    </p>
                                )}
                                <h3 className="mt-1 text-sm font-bold text-[var(--theme-heading)]">
                                    {event.title}
                                </h3>
                                {content.show_dates !== '0' && event.event_start_date && (
                                    <p className="mt-2 text-xs font-bold">
                                        {new Date(`${event.event_start_date}T00:00:00`).toLocaleDateString()}
                                    </p>
                                )}
                                {content.show_times !== '0' && event.event_start_time && (
                                    <p className="mt-1 text-[10px] opacity-60">
                                        {event.event_start_time.slice(0, 5)}
                                        {event.event_end_time ? `–${event.event_end_time.slice(0, 5)}` : ''}
                                    </p>
                                )}
                                {content.show_venues !== '0' && (event.venue || event.online_event_url) && (
                                    <p className="mt-1 text-xs font-semibold">
                                        {event.venue || 'Online event'}
                                    </p>
                                )}
                                {content.show_addresses === '1' && event.address && (
                                    <p className="mt-1 text-[10px] opacity-60">{event.address}</p>
                                )}
                                {content.show_excerpts !== '0' && event.excerpt && (
                                    <p className="mt-2 line-clamp-3 text-xs leading-5 opacity-70">
                                        {event.excerpt}
                                    </p>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add and publish events in Content → Events or adjust this section’s filters.
                </p>
            )}
        </div>
    );
}

function CareersPreview({
    content,
    label,
    variant,
    jobs,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    jobs: CareerOption[];
}) {
    const selected = new Set(textValue(content.career_slugs).split('\n').filter(Boolean));
    let visible = jobs.filter(
        (job) =>
            job.is_open &&
            (content.career_selection_mode !== 'selected' || selected.has(job.slug)) &&
            (!textValue(content.career_department) ||
                job.department === textValue(content.career_department)) &&
            (!textValue(content.career_location) || job.location === textValue(content.career_location)) &&
            (!textValue(content.career_employment_type) ||
                job.employment_type === textValue(content.career_employment_type)) &&
            (!textValue(content.career_workplace_type) ||
                job.workplace_type === textValue(content.career_workplace_type)),
    );
    if (content.career_sort === 'title')
        visible = [...visible].sort((a, b) => a.title.localeCompare(b.title));
    else if (content.career_sort === 'closing-soonest')
        visible = [...visible].sort((a, b) =>
            (a.closing_date || '9999').localeCompare(b.closing_date || '9999'),
        );
    else if (content.career_sort === 'location')
        visible = [...visible].sort((a, b) => (a.location ?? '').localeCompare(b.location ?? ''));
    visible = visible.slice(0, Number(textValue(content.career_limit)) || 8);
    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div
                    className={cn(
                        'grid gap-3 sm:grid-cols-2',
                        textValue(content.career_columns) === '1' && 'sm:grid-cols-1',
                        textValue(content.career_columns) === '3' && 'sm:grid-cols-3',
                        variant === 'list' && 'sm:grid-cols-1',
                    )}
                >
                    {visible.map((job, index) => (
                        <article
                            key={job.slug}
                            className={cn(
                                'rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] p-4 text-left',
                                variant === 'featured' && index === 0 && 'sm:col-span-full',
                            )}
                        >
                            {content.show_departments !== '0' && job.department && (
                                <p className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                    {job.department}
                                </p>
                            )}
                            <h3 className="mt-1 text-sm font-bold text-[var(--theme-heading)]">
                                {job.title}
                            </h3>
                            <p className="mt-2 flex flex-wrap gap-2 text-[10px] opacity-60">
                                {content.show_locations !== '0' && job.location && (
                                    <span>{job.location}</span>
                                )}
                                {content.show_employment_types !== '0' && job.employment_type && (
                                    <span>{job.employment_type.replace('-', ' ')}</span>
                                )}
                                {content.show_workplace_types !== '0' && job.workplace_type && (
                                    <span>{job.workplace_type}</span>
                                )}
                            </p>
                            {content.show_summaries !== '0' && job.summary && (
                                <p className="mt-2 line-clamp-3 text-xs leading-5 opacity-70">
                                    {job.summary}
                                </p>
                            )}
                            {content.show_salaries !== '0' && job.salary_display && (
                                <p className="mt-2 text-xs font-bold">{job.salary_display}</p>
                            )}
                            {content.show_closing_dates !== '0' && job.closing_date && (
                                <p className="mt-2 text-[10px] opacity-60">
                                    Apply by {new Date(`${job.closing_date}T00:00:00`).toLocaleDateString()}
                                </p>
                            )}
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add active job openings in Content → Careers or adjust this section’s filters.
                </p>
            )}
        </div>
    );
}

function FaqPreview({
    content,
    label,
    variant,
    faqs,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    faqs: FaqOption[];
}) {
    const selected = new Set(textValue(content.faq_keys).split('\n').filter(Boolean));
    let visible = faqs.filter(
        (faq) =>
            faq.is_active &&
            (content.faq_selection_mode !== 'selected' || selected.has(faq.key)) &&
            (!textValue(content.faq_category) || faq.category === textValue(content.faq_category)),
    );
    if (content.faq_sort === 'question')
        visible = [...visible].sort((a, b) => a.question.localeCompare(b.question));
    if (content.faq_sort === 'category')
        visible = [...visible].sort((a, b) => (a.category ?? '').localeCompare(b.category ?? ''));
    visible = visible.slice(0, Number(textValue(content.faq_limit)) || 12);

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {content.enable_search === '1' && (
                <div className="mb-4 rounded-lg border border-[var(--theme-border)] bg-[var(--theme-surface)] px-3 py-2 text-left text-xs opacity-60">
                    {textValue(content.search_placeholder) || 'Search questions…'}
                </div>
            )}
            {visible.length ? (
                <div className={cn('grid gap-2', variant === 'two-column' && 'sm:grid-cols-2')}>
                    {visible.map((faq, index) => {
                        const open =
                            content.default_open === 'all' ||
                            (content.default_open === 'first' && index === 0);
                        return (
                            <article
                                key={faq.key}
                                className="rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] p-4 text-left"
                            >
                                {content.show_categories !== '0' && faq.category && (
                                    <p className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                        {faq.category}
                                    </p>
                                )}
                                <h3 className="text-sm font-bold text-[var(--theme-heading)]">
                                    {content.show_numbers === '1' ? `${index + 1}. ` : ''}
                                    {faq.question}
                                </h3>
                                {open && <p className="mt-2 text-xs leading-5 opacity-70">{faq.answer}</p>}
                            </article>
                        );
                    })}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add active questions in Content → FAQ or adjust this section’s filters.
                </p>
            )}
        </div>
    );
}

function TeamPreview({
    content,
    label,
    variant,
    members,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    members: TeamOption[];
}) {
    const selected = new Set(textValue(content.team_keys).split('\n').filter(Boolean));
    let visible = members.filter(
        (member) =>
            member.is_active &&
            (content.team_selection_mode !== 'selected' || selected.has(member.key)) &&
            (content.team_featured_only !== '1' || member.is_featured) &&
            (!textValue(content.team_role) || member.role === textValue(content.team_role)),
    );
    if (content.team_sort === 'featured-first')
        visible = [...visible].sort((a, b) => Number(b.is_featured) - Number(a.is_featured));
    if (content.team_sort === 'name') visible = [...visible].sort((a, b) => a.name.localeCompare(b.name));
    if (content.team_sort === 'role')
        visible = [...visible].sort((a, b) => (a.role ?? '').localeCompare(b.role ?? ''));
    visible = visible.slice(0, Number(textValue(content.team_limit)) || 8);

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div
                    className={cn(
                        'grid gap-3 sm:grid-cols-4',
                        textValue(content.team_columns) === '2' && 'sm:grid-cols-2',
                        textValue(content.team_columns) === '3' && 'sm:grid-cols-3',
                        variant === 'compact' && 'sm:grid-cols-2',
                    )}
                >
                    {visible.map((member) => (
                        <article
                            key={member.key}
                            className={cn(
                                'overflow-hidden rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)]',
                                variant === 'compact' && 'flex items-center',
                                content.card_alignment === 'center' && 'text-center',
                            )}
                        >
                            {content.show_photos !== '0' && member.photo && (
                                <img
                                    src={member.photo}
                                    alt=""
                                    className={cn(
                                        'aspect-[3/4] w-full object-cover',
                                        variant === 'compact' && 'size-24 shrink-0',
                                        content.image_shape === 'square' && 'aspect-square',
                                        content.image_shape === 'circle' &&
                                            'mx-auto mt-4 aspect-square max-w-24 rounded-full',
                                        content.image_fit === 'contain' && 'object-contain',
                                    )}
                                />
                            )}
                            <div className="p-4">
                                {content.show_featured_badge !== '0' && member.is_featured && (
                                    <p className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                        {textValue(content.featured_label) || 'Leadership'}
                                    </p>
                                )}
                                <h3 className="font-bold text-[var(--theme-heading)]">{member.name}</h3>
                                {content.show_roles !== '0' && member.role && (
                                    <p className="text-[10px] opacity-60">{member.role}</p>
                                )}
                                {content.show_bios !== '0' && member.bio && variant !== 'compact' && (
                                    <p className="mt-2 line-clamp-3 text-xs opacity-70">{member.bio}</p>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add active people in Content → Team or adjust this section’s filters.
                </p>
            )}
        </div>
    );
}

function GalleryPreview({
    content,
    label,
    variant,
    images,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    images: GalleryOption[];
}) {
    const selected = new Set(textValue(content.gallery_images).split('\n').filter(Boolean));
    let visible = images.filter(
        (item) =>
            item.is_active &&
            (content.gallery_selection_mode !== 'selected' || selected.has(item.image)) &&
            (!textValue(content.gallery_category) || item.category === textValue(content.gallery_category)),
    );
    if (content.gallery_sort === 'title')
        visible = [...visible].sort((a, b) => a.title.localeCompare(b.title));
    if (content.gallery_sort === 'category')
        visible = [...visible].sort((a, b) => (a.category ?? '').localeCompare(b.category ?? ''));
    visible = visible.slice(0, Number(textValue(content.gallery_limit)) || 12);

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div
                    className={cn(
                        'grid gap-3 sm:grid-cols-3',
                        textValue(content.gallery_columns) === '2' && 'sm:grid-cols-2',
                        textValue(content.gallery_columns) === '4' && 'sm:grid-cols-4',
                        textValue(content.gallery_columns) === '5' && 'sm:grid-cols-5',
                    )}
                >
                    {visible.map((item, index) => (
                        <figure
                            key={`${item.image}-${index}`}
                            className="overflow-hidden rounded-[var(--theme-radius)] bg-[var(--theme-surface)]"
                        >
                            <img
                                src={item.image}
                                alt=""
                                className={cn(
                                    'w-full object-cover',
                                    variant !== 'masonry' && 'aspect-square',
                                    variant === 'masonry' && index % 3 === 0
                                        ? 'aspect-[3/4]'
                                        : 'aspect-[4/3]',
                                    content.respect_aspect_ratios !== '0' &&
                                        item.aspect_ratio === 'portrait' &&
                                        'aspect-[3/4]',
                                    content.respect_aspect_ratios !== '0' &&
                                        item.aspect_ratio === 'landscape' &&
                                        'aspect-video',
                                    content.image_fit === 'contain' && 'object-contain',
                                )}
                            />
                            {(content.show_titles !== '0' || content.show_captions !== '0') && (
                                <figcaption className="p-3 text-left">
                                    {content.show_categories !== '0' && item.category && (
                                        <p className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                            {item.category}
                                        </p>
                                    )}
                                    {content.show_titles !== '0' && (
                                        <p className="text-xs font-bold">{item.title}</p>
                                    )}
                                    {content.show_captions !== '0' && item.caption && (
                                        <p className="mt-1 line-clamp-2 text-[10px] opacity-60">
                                            {item.caption}
                                        </p>
                                    )}
                                </figcaption>
                            )}
                        </figure>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add active images in Content → Gallery or adjust this section’s filters.
                </p>
            )}
        </div>
    );
}

function PortfolioPreview({
    content,
    label,
    variant,
    projects,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    projects: PortfolioOption[];
}) {
    const selected = new Set(textValue(content.portfolio_slugs).split(',').filter(Boolean));
    let visible = projects.filter(
        (project) =>
            project.is_active &&
            (content.portfolio_selection_mode !== 'selected' || selected.has(project.slug)) &&
            (content.portfolio_featured_only !== '1' || project.is_featured) &&
            (!textValue(content.portfolio_category) ||
                project.category === textValue(content.portfolio_category)),
    );
    if (content.portfolio_sort === 'featured-first')
        visible = [...visible].sort((a, b) => Number(b.is_featured) - Number(a.is_featured));
    if (content.portfolio_sort === 'newest')
        visible = [...visible].sort((a, b) => (b.project_date ?? '').localeCompare(a.project_date ?? ''));
    if (content.portfolio_sort === 'oldest')
        visible = [...visible].sort((a, b) => (a.project_date ?? '').localeCompare(b.project_date ?? ''));
    if (content.portfolio_sort === 'title')
        visible = [...visible].sort((a, b) => a.title.localeCompare(b.title));
    visible = visible.slice(0, Number(textValue(content.portfolio_limit)) || 6);

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div
                    className={cn(
                        'grid gap-3 sm:grid-cols-3',
                        textValue(content.portfolio_columns) === '2' && 'sm:grid-cols-2',
                        textValue(content.portfolio_columns) === '4' && 'sm:grid-cols-4',
                        variant === 'featured' && 'sm:grid-cols-2',
                    )}
                >
                    {visible.map((project, index) => (
                        <article
                            key={project.slug}
                            className={cn(
                                'overflow-hidden rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)]',
                                variant === 'featured' && index === 0 && 'sm:col-span-2',
                            )}
                        >
                            {content.show_images !== '0' && project.featured_image && (
                                <img
                                    src={project.featured_image}
                                    alt=""
                                    className={cn(
                                        'aspect-[4/3] w-full object-cover',
                                        variant === 'masonry' && index % 3 === 0 && 'aspect-[3/4]',
                                        content.image_fit === 'contain' && 'object-contain',
                                    )}
                                />
                            )}
                            <div className="p-4">
                                {content.show_categories !== '0' && project.category && (
                                    <p className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                        {project.category}
                                    </p>
                                )}
                                <h3 className="font-bold text-[var(--theme-heading)]">{project.title}</h3>
                                {content.show_descriptions !== '0' && project.short_description && (
                                    <p className="mt-2 line-clamp-3 text-xs opacity-70">
                                        {project.short_description}
                                    </p>
                                )}
                                <p className="mt-2 text-[10px] opacity-60">
                                    {content.show_clients !== '0' ? project.client : ''}
                                    {content.show_dates !== '0' && project.project_date
                                        ? `${project.client && content.show_clients !== '0' ? ' · ' : ''}${project.project_date}`
                                        : ''}
                                </p>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add active projects in Content → Portfolio or adjust this section’s filters.
                </p>
            )}
        </div>
    );
}

function TestimonialsPreview({
    content,
    label,
    variant,
    testimonials,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    testimonials: TestimonialOption[];
}) {
    let visible = testimonials.filter(
        (item) => item.is_active && (content.testimonial_featured_only !== '1' || item.is_featured),
    );
    if (content.testimonial_sort === 'featured-first')
        visible = [...visible].sort((a, b) => Number(b.is_featured) - Number(a.is_featured));
    if (content.testimonial_sort === 'rating-high')
        visible = [...visible].sort((a, b) => Number(b.rating ?? 0) - Number(a.rating ?? 0));
    visible = visible.slice(0, Number(textValue(content.testimonial_limit)) || 6);
    const carousel = ['carousel', 'slider'].includes(variant);

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div
                    className={cn(
                        'grid gap-3',
                        variant === 'grid' && 'sm:grid-cols-2',
                        carousel && 'flex overflow-hidden',
                    )}
                >
                    {visible.slice(0, carousel ? 3 : visible.length).map((item, index) => (
                        <article
                            key={`${item.client_name}-${index}`}
                            className={cn(
                                'min-w-0 rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] p-5',
                                carousel && 'min-w-[75%] flex-1',
                                variant === 'featured' && index === 0 && 'sm:col-span-2',
                                variant === 'quote' && 'border-0 bg-transparent text-center',
                            )}
                        >
                            {content.show_ratings !== '0' && item.rating && (
                                <p className="text-xs tracking-widest text-[var(--theme-accent)]">
                                    {'★'.repeat(item.rating)}
                                </p>
                            )}
                            <blockquote className="mt-2 text-sm leading-6">“{item.quote}”</blockquote>
                            <div className="mt-4 flex items-center gap-3">
                                {content.show_photos !== '0' && item.photo && (
                                    <img
                                        src={item.photo}
                                        alt=""
                                        className="size-9 rounded-full object-cover"
                                    />
                                )}
                                <div className="text-left">
                                    <p className="text-xs font-bold">{item.client_name}</p>
                                    <p className="text-[10px] opacity-60">
                                        {content.show_roles !== '0' ? item.client_role : ''}
                                        {content.show_roles !== '0' &&
                                        content.show_companies !== '0' &&
                                        item.client_role &&
                                        item.company
                                            ? ' · '
                                            : ''}
                                        {content.show_companies !== '0' ? item.company : ''}
                                    </p>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add active testimonials in Content → Testimonials.
                </p>
            )}
        </div>
    );
}

function ProductsPreview({
    content,
    label,
    variant,
    products,
}: {
    content: SectionContent;
    label: string;
    variant: string;
    products: ProductOption[];
}) {
    const selected = new Set(textValue(content.product_slugs).split(',').filter(Boolean));
    let visible = products.filter(
        (product) =>
            product.is_active &&
            (content.selection_mode !== 'selected' || selected.has(product.slug)) &&
            (content.featured_only !== '1' || product.is_featured),
    );
    if (content.sort === 'featured-first')
        visible = [...visible].sort((a, b) => Number(b.is_featured) - Number(a.is_featured));
    if (content.sort === 'name') visible = [...visible].sort((a, b) => a.name.localeCompare(b.name));
    if (content.sort === 'price-low')
        visible = [...visible].sort((a, b) => Number(a.price ?? 0) - Number(b.price ?? 0));
    if (content.sort === 'price-high')
        visible = [...visible].sort((a, b) => Number(b.price ?? 0) - Number(a.price ?? 0));
    visible = visible.slice(0, Number(textValue(content.limit)) || 6);

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {visible.length ? (
                <div className={cn('grid gap-3 sm:grid-cols-3', variant === 'featured' && 'sm:grid-cols-2')}>
                    {visible.map((product, index) => (
                        <article
                            key={product.slug}
                            className={cn(
                                'overflow-hidden rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)]',
                                variant === 'featured' && index === 0 && 'sm:col-span-2',
                            )}
                        >
                            {content.show_images !== '0' && product.featured_image && (
                                <img
                                    src={product.featured_image}
                                    alt=""
                                    className={cn(
                                        'aspect-[4/3] w-full object-cover',
                                        content.image_fit === 'contain' && 'object-contain',
                                    )}
                                />
                            )}
                            <div className="p-4">
                                {content.show_featured_badge !== '0' && product.is_featured && (
                                    <span className="text-[9px] font-bold text-[var(--theme-accent)] uppercase">
                                        {textValue(content.featured_label) || 'Featured'}
                                    </span>
                                )}
                                <h3 className="font-bold text-[var(--theme-heading)]">{product.name}</h3>
                                {content.show_descriptions !== '0' && product.short_description && (
                                    <p className="mt-2 line-clamp-3 text-xs opacity-70">
                                        {product.short_description}
                                    </p>
                                )}
                                {content.show_prices !== '0' && product.show_price && product.price && (
                                    <p className="mt-3 text-sm font-black">
                                        {textValue(content.price_prefix)}
                                        {Number(product.price).toLocaleString('en-IN')}
                                    </p>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add active products in Content → Products or adjust this section’s selection.
                </p>
            )}
        </div>
    );
}

function LogoCloudPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const logos = Array.isArray(content.logos) ? (content.logos as BrandLogo[]) : [];
    const columns = Math.min(6, Math.max(2, Number(textValue(content.logo_columns)) || 4));
    const size = textValue(content.logo_size);
    const spacing = textValue(content.logo_spacing);
    const showCards = textValue(content.show_cards) === '1';
    const showNames = textValue(content.show_names) === '1';
    const grayscale = textValue(content.grayscale) === '1';

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {logos.length ? (
                <div
                    className={cn(
                        'gap-3',
                        variant === 'band'
                            ? 'flex flex-wrap justify-center rounded-[var(--theme-radius)] bg-[var(--theme-surface)] p-3'
                            : 'grid',
                        spacing === 'compact' && 'gap-1.5',
                        spacing === 'loose' && 'gap-5',
                    )}
                    style={
                        variant === 'grid'
                            ? { gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }
                            : variant === 'default'
                              ? { gridTemplateColumns: 'repeat(auto-fit, minmax(7rem, 1fr))' }
                              : undefined
                    }
                >
                    {logos.map((logo, index) => (
                        <div
                            key={`${logo.image_id}-${index}`}
                            className={cn(
                                'grid min-h-16 min-w-0 place-items-center p-2 text-center',
                                variant === 'band' && 'min-w-24 flex-1',
                                showCards &&
                                    'rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-white',
                            )}
                        >
                            <img
                                src={`/media/${logo.image_id}/small`}
                                alt={logo.alt || logo.name}
                                className={cn(
                                    'max-w-full object-contain',
                                    size === 'compact'
                                        ? 'max-h-8'
                                        : size === 'large'
                                          ? 'max-h-16'
                                          : 'max-h-12',
                                    grayscale && 'grayscale',
                                )}
                            />
                            {showNames && logo.name && (
                                <span className="mt-1 block text-[9px] font-semibold">{logo.name}</span>
                            )}
                        </div>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Upload logo images in the Media library, then add them in Website Design.
                </p>
            )}
        </div>
    );
}

function BrandCarouselPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const logos = Array.isArray(content.logos) ? (content.logos as BrandLogo[]) : [];
    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {logos.length ? (
                <div
                    className={cn(
                        'grid grid-cols-2 gap-3 sm:grid-cols-4',
                        variant === 'carousel' && 'flex overflow-hidden',
                    )}
                >
                    {logos.slice(0, variant === 'carousel' ? 5 : 8).map((logo, index) => (
                        <div
                            key={`${logo.image_id}-${index}`}
                            className="grid min-h-20 min-w-32 flex-1 place-items-center rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-white p-3"
                        >
                            <img
                                src={`/media/${logo.image_id}/small`}
                                alt={logo.alt}
                                className={cn(
                                    'max-h-12 max-w-full object-contain',
                                    textValue(content.grayscale) === '1' && 'grayscale',
                                )}
                            />
                            {textValue(content.show_names) === '1' && logo.name && (
                                <span className="mt-1 text-[9px] font-semibold">{logo.name}</span>
                            )}
                        </div>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Select partner logos from the Media Library.
                </p>
            )}
        </div>
    );
}

function MapPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const showMap = textValue(content.show_map) !== '0';
    const showDetails = textValue(content.show_location_details) !== '0';
    const mapLeft = textValue(content.map_position) === 'left';

    return (
        <div>
            <div className={cn('mb-6', variant === 'full-width' && 'text-center')}>
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 text-sm leading-6 opacity-80">{textValue(content.description)}</p>
                )}
            </div>
            <div
                className={cn(
                    'grid gap-4 text-left',
                    showMap && showDetails && variant !== 'full-width' && 'sm:grid-cols-2',
                )}
            >
                {showDetails && (
                    <div
                        className={cn(
                            'rounded-[var(--theme-radius)] border border-[var(--theme-border)] p-4',
                            mapLeft && variant !== 'full-width' && 'sm:order-2',
                        )}
                    >
                        {textValue(content.location_heading) && (
                            <h3 className="text-sm font-bold text-[var(--theme-heading)]">
                                {textValue(content.location_heading)}
                            </h3>
                        )}
                        {textValue(content.show_address) !== '0' && (
                            <div className="mt-3">
                                <strong className="block text-[10px] text-[var(--theme-heading)]">
                                    {textValue(content.address_label) || 'Address'}
                                </strong>
                                <span className="text-[10px] opacity-60">Address from Contact settings</span>
                            </div>
                        )}
                        {textValue(content.show_working_hours) !== '0' && (
                            <div className="mt-3">
                                <strong className="block text-[10px] text-[var(--theme-heading)]">
                                    {textValue(content.hours_label) || 'Working hours'}
                                </strong>
                                <span className="text-[10px] opacity-60">Hours from Contact settings</span>
                            </div>
                        )}
                        {textValue(content.location_note) && (
                            <p className="mt-3 text-[10px] opacity-70">{textValue(content.location_note)}</p>
                        )}
                        <div className="mt-4 flex flex-wrap gap-2">
                            {textValue(content.show_directions_button) !== '0' &&
                                textValue(content.directions_label) && (
                                    <span className="rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-3 py-2 text-[10px] font-bold text-white">
                                        {textValue(content.directions_label)}
                                    </span>
                                )}
                            {textValue(content.show_contact_button) === '1' &&
                                textValue(content.contact_label) && (
                                    <span className="rounded-[var(--theme-radius)] border px-3 py-2 text-[10px] font-bold">
                                        {textValue(content.contact_label)}
                                    </span>
                                )}
                        </div>
                    </div>
                )}
                {showMap && (
                    <div
                        className={cn(
                            'grid min-h-52 place-items-center rounded-[var(--theme-radius)] bg-[var(--theme-border)] text-center text-[10px] opacity-75',
                            mapLeft && variant !== 'full-width' && 'sm:order-1',
                            variant === 'full-width' && 'min-h-64',
                        )}
                    >
                        <span>Google Map from Contact settings</span>
                    </div>
                )}
            </div>
        </div>
    );
}

function ContactPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const showForm = textValue(content.show_form) !== '0';
    const details = [
        {
            visible: content.show_address,
            label: content.address_label,
            fallbackLabel: 'Address',
            help: 'Address from Contact settings',
        },
        {
            visible: content.show_phone_numbers,
            label: content.phone_label,
            fallbackLabel: 'Phone',
            help: 'Phone numbers from Contact settings',
        },
        {
            visible: content.show_email_addresses,
            label: content.email_label,
            fallbackLabel: 'Email',
            help: 'Email addresses from Contact settings',
        },
        {
            visible: content.show_working_hours,
            label: content.hours_label,
            fallbackLabel: 'Working hours',
            help: 'Hours from Contact settings',
        },
    ].filter(({ visible }) => textValue(visible) !== '0');

    return (
        <div className={cn(variant === 'centered' && 'text-center')}>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 text-sm leading-6 opacity-80">{textValue(content.description)}</p>
                )}
            </div>
            <div
                className={cn(
                    'grid gap-4 text-left',
                    showForm && details.length > 0 && variant !== 'centered' && 'sm:grid-cols-2',
                )}
            >
                {details.length > 0 && (
                    <div
                        className={cn(
                            'rounded-[var(--theme-radius)] border border-[var(--theme-border)] p-4',
                            textValue(content.form_position) === 'left' && 'sm:order-2',
                        )}
                    >
                        {textValue(content.show_details_heading) !== '0' && (
                            <h3 className="mb-3 text-sm font-bold text-[var(--theme-heading)]">
                                {textValue(content.details_heading) || 'Contact details'}
                            </h3>
                        )}
                        <div className="space-y-3">
                            {details.map((detail) => (
                                <div key={detail.fallbackLabel}>
                                    <strong className="block text-[10px] text-[var(--theme-heading)]">
                                        {textValue(detail.label) || detail.fallbackLabel}
                                    </strong>
                                    <span className="text-[10px] opacity-60">{detail.help}</span>
                                </div>
                            ))}
                        </div>
                        {textValue(content.show_map) !== '0' && (
                            <div className="mt-4 grid aspect-video place-items-center rounded-lg bg-[var(--theme-border)] text-[10px] opacity-70">
                                Map from Contact settings
                            </div>
                        )}
                    </div>
                )}
                {showForm && (
                    <div
                        className={cn(
                            'rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-white p-4 text-slate-900',
                            textValue(content.form_position) === 'left' && 'sm:order-1',
                        )}
                    >
                        {textValue(content.show_form_heading) !== '0' && (
                            <h3 className="text-sm font-bold">
                                {textValue(content.form_heading) || 'Send an enquiry'}
                            </h3>
                        )}
                        {textValue(content.show_form_description) !== '0' &&
                            textValue(content.form_description) && (
                                <p className="mt-1 text-[10px] opacity-60">
                                    {textValue(content.form_description)}
                                </p>
                            )}
                        <div className="mt-3 grid gap-2">
                            {[
                                [content.name_label, content.name_placeholder, 'Name'],
                                [content.email_field_label, content.email_placeholder, 'Email'],
                                [content.phone_field_label, content.phone_placeholder, 'Phone'],
                                [content.subject_label, content.subject_placeholder, 'Subject'],
                                [content.message_label, content.message_placeholder, 'Message'],
                            ].map(([customLabel, placeholder, fallback], index) => (
                                <label key={index} className="text-[9px] font-bold">
                                    {textValue(customLabel) || textValue(fallback)}
                                    <span
                                        className={cn(
                                            'mt-1 block rounded border border-slate-200 px-2 py-2 font-normal text-slate-400',
                                            index === 4 && 'min-h-14',
                                        )}
                                    >
                                        {textValue(placeholder)}
                                    </span>
                                </label>
                            ))}
                        </div>
                        {textValue(content.privacy_note) && (
                            <p className="mt-2 text-[9px] text-slate-500">
                                {textValue(content.privacy_note)}
                            </p>
                        )}
                        <span className="mt-3 inline-block rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-3 py-2 text-[10px] font-bold text-white">
                            {textValue(content.submit_label) || 'Send enquiry'}
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}

function PricingPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const items = Array.isArray(content.pricing_items) ? (content.pricing_items as PricingItem[]) : [];
    const columns = textValue(content.pricing_columns) || '3';
    const currencyAfter = textValue(content.currency_position) === 'after';

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            <div
                className={cn(
                    'grid gap-4',
                    columns === '2'
                        ? 'sm:grid-cols-2'
                        : columns === '4'
                          ? 'sm:grid-cols-4'
                          : 'sm:grid-cols-3',
                    variant === 'comparison' && 'gap-0',
                )}
            >
                {items.map((item, index) => {
                    const features = item.features
                        .split(/\r?\n/)
                        .map((feature) => feature.trim())
                        .filter(Boolean);
                    return (
                        <article
                            key={`${item.name}-${index}`}
                            className={cn(
                                'relative flex flex-col rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] p-4',
                                item.is_featured === '1' &&
                                    'z-10 border-[var(--theme-primary)] ring-2 ring-[var(--theme-primary)]',
                                variant === 'comparison' && 'rounded-none',
                            )}
                        >
                            {textValue(content.show_plan_badges) !== '0' && item.badge && (
                                <span className="mb-3 w-fit rounded-full bg-[var(--theme-accent)] px-2 py-1 text-[9px] font-bold text-white uppercase">
                                    {item.badge}
                                </span>
                            )}
                            <h3 className="text-base font-bold text-[var(--theme-heading)]">{item.name}</h3>
                            {textValue(content.show_plan_descriptions) !== '0' && item.description && (
                                <p className="mt-2 text-[11px] leading-5 opacity-70">{item.description}</p>
                            )}
                            <div className="mt-4 flex flex-wrap items-baseline gap-1">
                                {textValue(content.show_original_prices) === '1' && item.original_price && (
                                    <del className="mr-1 text-xs opacity-50">
                                        {currencyAfter
                                            ? `${item.original_price}${item.currency}`
                                            : `${item.currency}${item.original_price}`}
                                    </del>
                                )}
                                <span className="text-2xl font-black text-[var(--theme-heading)]">
                                    {currencyAfter
                                        ? `${item.price}${item.currency}`
                                        : `${item.currency}${item.price}`}
                                </span>
                                {item.period && <span className="text-[10px] opacity-60">{item.period}</span>}
                            </div>
                            {textValue(content.show_plan_features) !== '0' && features.length > 0 && (
                                <ul className="my-4 space-y-2 text-[10px]">
                                    {features.slice(0, 8).map((feature, featureIndex) => {
                                        const excluded = feature.startsWith('-');
                                        return (
                                            <li
                                                key={`${feature}-${featureIndex}`}
                                                className={cn(
                                                    'flex gap-2',
                                                    excluded && 'line-through opacity-45',
                                                )}
                                            >
                                                <span aria-hidden="true">{excluded ? '×' : '✓'}</span>
                                                <span>{feature.replace(/^[+-]\s*/, '')}</span>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                            {textValue(content.show_plan_buttons) !== '0' && item.button_label && (
                                <span className="mt-auto inline-block rounded-[var(--theme-radius)] bg-[var(--theme-primary)] px-3 py-2 text-center text-[10px] font-bold text-[var(--theme-primary-foreground)]">
                                    {item.button_label}
                                </span>
                            )}
                        </article>
                    );
                })}
            </div>
            {textValue(content.pricing_note) && (
                <p className="mt-4 text-center text-[10px] opacity-60">{textValue(content.pricing_note)}</p>
            )}
        </div>
    );
}

function ProcessPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const items = Array.isArray(content.process_items) ? (content.process_items as ProcessItem[]) : [];
    const columns = textValue(content.process_columns) || '3';
    const centered = textValue(content.process_alignment) === 'center';
    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            <ol
                className={cn(
                    'grid gap-4',
                    variant === 'timeline'
                        ? 'grid-cols-1'
                        : columns === '2'
                          ? 'sm:grid-cols-2'
                          : columns === '4'
                            ? 'sm:grid-cols-4'
                            : 'sm:grid-cols-3',
                )}
            >
                {items.map((item, index) => {
                    const image = textValue(content.show_step_images) !== '0' && item.image_id;
                    return (
                        <li
                            key={`${item.title}-${index}`}
                            className={cn(
                                'relative rounded-[var(--theme-radius)] p-4',
                                variant !== 'default' &&
                                    'border border-[var(--theme-border)] bg-[var(--theme-surface)]',
                                variant === 'timeline' && 'ml-5 border-l-4',
                                centered && 'text-center',
                            )}
                        >
                            <div className={cn('mb-3 flex items-center gap-2', centered && 'justify-center')}>
                                {textValue(content.show_step_numbers) !== '0' && (
                                    <span className="inline-grid size-9 place-items-center rounded-full bg-[var(--theme-primary)] text-[10px] font-black text-[var(--theme-primary-foreground)]">
                                        {item.number || String(index + 1).padStart(2, '0')}
                                    </span>
                                )}
                                {textValue(content.show_step_icons) === '1' && item.icon && (
                                    <span className="text-lg" aria-hidden="true">
                                        {item.icon}
                                    </span>
                                )}
                            </div>
                            {image && (
                                <img
                                    src={`/media/${item.image_id}/small`}
                                    alt={item.image_alt}
                                    className={cn(
                                        'mb-3 aspect-video w-full rounded-lg object-cover',
                                        textValue(content.image_fit) === 'contain' && 'object-contain',
                                    )}
                                />
                            )}
                            {textValue(content.show_step_meta) === '1' && item.meta && (
                                <p className="mb-1 text-[9px] font-bold tracking-wider text-[var(--theme-accent)] uppercase">
                                    {item.meta}
                                </p>
                            )}
                            <h3 className="text-sm font-bold">{item.title}</h3>
                            {textValue(content.show_step_descriptions) !== '0' && item.description && (
                                <p className="mt-2 line-clamp-3 text-[11px] leading-5 opacity-70">
                                    {item.description}
                                </p>
                            )}
                            {textValue(content.show_step_links) !== '0' && item.link_label && (
                                <span className="mt-3 inline-block text-[10px] font-bold text-[var(--theme-primary)]">
                                    {item.link_label} →
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </div>
    );
}

function FeaturesPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const items = Array.isArray(content.feature_items) ? (content.feature_items as FeatureItem[]) : [];
    const columns = textValue(content.columns) || '3';
    const centered = textValue(content.card_alignment) === 'center';

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            <div
                className={cn(
                    'grid gap-3',
                    columns === '2' && 'sm:grid-cols-2',
                    columns === '3' && 'sm:grid-cols-3',
                    columns === '4' && 'sm:grid-cols-4',
                )}
            >
                {items.map((item, index) => {
                    const image = textValue(content.show_images) !== '0' && item.image_id;
                    const featured = item.is_featured === '1';
                    return (
                        <article
                            key={`${item.title}-${index}`}
                            className={cn(
                                'relative min-w-0 rounded-[var(--theme-radius)] p-4',
                                variant !== 'default' &&
                                    'border border-[var(--theme-border)] bg-[var(--theme-surface)]',
                                variant === 'icon-grid' && 'bg-transparent',
                                featured && 'ring-2 ring-[var(--theme-accent)]',
                                centered && 'text-center',
                            )}
                        >
                            {featured && textValue(content.featured_label) && (
                                <span className="absolute top-2 right-2 rounded-full bg-[var(--theme-accent)] px-2 py-0.5 text-[8px] font-bold text-[var(--theme-accent-foreground)]">
                                    {textValue(content.featured_label)}
                                </span>
                            )}
                            {image ? (
                                <img
                                    src={`/media/${item.image_id}/small`}
                                    alt={item.image_alt}
                                    className={cn(
                                        'mb-3 aspect-video w-full rounded-lg object-cover',
                                        textValue(content.image_fit) === 'contain' && 'object-contain',
                                    )}
                                />
                            ) : (
                                textValue(content.show_icons) !== '0' && (
                                    <span className="mb-3 inline-grid size-10 place-items-center rounded-lg bg-[var(--theme-primary)] text-sm font-black text-[var(--theme-primary-foreground)]">
                                        {item.icon || String(index + 1).padStart(2, '0')}
                                    </span>
                                )
                            )}
                            <h3 className="text-sm font-bold">{item.title}</h3>
                            {textValue(content.show_descriptions) !== '0' && item.description && (
                                <p className="mt-2 line-clamp-3 text-[11px] leading-5 opacity-70">
                                    {item.description}
                                </p>
                            )}
                            {textValue(content.show_links) !== '0' && item.link_label && (
                                <span className="mt-3 inline-block text-[10px] font-bold text-[var(--theme-primary)]">
                                    {item.link_label} →
                                </span>
                            )}
                        </article>
                    );
                })}
            </div>
        </div>
    );
}

function StatisticsPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const stats = Array.isArray(content.stats) ? (content.stats as StatisticItem[]) : [];
    const columns = Math.min(4, Math.max(2, Number(textValue(content.stats_columns)) || 4));
    const showDescriptions = textValue(content.show_descriptions) !== '0';
    const showTrends = textValue(content.show_trends) !== '0';
    const showSources = textValue(content.show_sources) !== '0';
    const highlightFirst = textValue(content.highlight_first) === '1';
    const grouping = textValue(content.use_grouping) !== '0';
    const formatValue = (item: StatisticItem) =>
        (Number.isFinite(Number(item.value)) ? Number(item.value) : 0).toLocaleString(undefined, {
            useGrouping: grouping,
            minimumFractionDigits: Math.min(2, (item.value.split('.')[1] ?? '').length),
            maximumFractionDigits: 2,
        });
    return (
        <div className={cn(variant === 'split' && 'grid gap-6 sm:grid-cols-[0.75fr_1.25fr]')}>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            {stats.length ? (
                <div
                    className="grid gap-3"
                    style={{
                        gridTemplateColumns: `repeat(${variant === 'split' ? 2 : columns}, minmax(0, 1fr))`,
                    }}
                >
                    {stats.map((item, index) => (
                        <article
                            key={`${item.label}-${index}`}
                            className={cn(
                                'min-w-0 rounded-[var(--theme-radius)] p-3',
                                variant === 'cards' &&
                                    'border border-[var(--theme-border)] bg-[var(--theme-surface)]',
                                highlightFirst &&
                                    index === 0 &&
                                    'bg-[var(--theme-primary)] text-[var(--theme-primary-foreground)]',
                            )}
                        >
                            <p className="text-2xl font-black break-words sm:text-3xl">
                                {item.prefix}
                                {formatValue(item)}
                                {item.suffix}
                            </p>
                            <h3 className="mt-1 text-xs font-bold">{item.label}</h3>
                            {showDescriptions && item.description && (
                                <p className="mt-1 line-clamp-2 text-[10px] opacity-70">{item.description}</p>
                            )}
                            {showTrends && item.trend_text && (
                                <p className="mt-2 text-[10px] font-bold opacity-80">
                                    {item.trend_direction === 'up'
                                        ? '↑ '
                                        : item.trend_direction === 'down'
                                          ? '↓ '
                                          : ''}
                                    {item.trend_text}
                                </p>
                            )}
                            {showSources && (item.source_label || item.source_url) && (
                                <p className="mt-1 text-[9px] opacity-60">
                                    Source: {item.source_label || 'View source'}
                                </p>
                            )}
                        </article>
                    ))}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed p-5 text-center text-xs opacity-60">
                    Add verified statistics in Website Design.
                </p>
            )}
        </div>
    );
}

function CountersPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const items = Array.isArray(content.items) ? (content.items as CounterItem[]) : [];
    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 max-w-2xl text-sm leading-6 opacity-80">
                        {textValue(content.description)}
                    </p>
                )}
            </div>
            <div
                className={cn(
                    'grid grid-cols-2 gap-3',
                    items.length > 2 && 'sm:grid-cols-4',
                    variant === 'band' && 'rounded-[var(--theme-radius)] bg-[var(--theme-primary)] p-4',
                )}
            >
                {items.map((item, index) => (
                    <article
                        key={`${item.label}-${index}`}
                        className={cn(
                            'min-w-0 py-3',
                            variant === 'cards' &&
                                'rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)] p-4',
                            variant === 'band' && 'text-[var(--theme-primary-foreground)]',
                        )}
                    >
                        <p className="truncate text-2xl font-black sm:text-3xl">
                            {item.prefix}
                            {Number(item.value || 0).toLocaleString()}
                            {item.suffix}
                        </p>
                        <h3 className="mt-1 text-xs font-bold">{item.label}</h3>
                        {item.description && (
                            <p className="mt-1 line-clamp-2 text-[10px] opacity-70">{item.description}</p>
                        )}
                    </article>
                ))}
            </div>
        </div>
    );
}

function MissionVisionPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const areas = ['story', 'mission', 'vision'] as const;
    const storyImageLeft = textValue(content.image_position) !== 'right';

    return (
        <div>
            <div className="mb-6">
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
            </div>
            <div
                className={cn(
                    'grid gap-5',
                    variant === 'side-by-side' && 'sm:grid-cols-3',
                    variant === 'cards' && 'sm:grid-cols-3',
                )}
            >
                {areas.map((area) => {
                    const imageId = textValue(content[`${area}_image_id`]);
                    const body =
                        textValue(content[`${area}_body`]) ||
                        (area === 'story' ? textValue(content.description) : '');
                    return (
                        <article
                            key={area}
                            className={cn(
                                variant === 'cards' &&
                                    'overflow-hidden rounded-[var(--theme-radius)] border border-[var(--theme-border)] bg-[var(--theme-surface)]',
                                variant === 'default' &&
                                    area === 'story' &&
                                    imageId &&
                                    'grid items-center gap-5 sm:grid-cols-2',
                                variant === 'default' && area !== 'story' && 'mt-2',
                            )}
                        >
                            {imageId && (
                                <img
                                    src={`/media/${imageId}/medium`}
                                    alt={textValue(content[`${area}_image_alt`])}
                                    className={cn(
                                        'aspect-video w-full rounded-[var(--theme-radius)] object-cover',
                                        variant === 'cards' && 'rounded-none',
                                        variant === 'default' &&
                                            area === 'story' &&
                                            !storyImageLeft &&
                                            'sm:order-2',
                                        textValue(content.image_fit) === 'contain' && 'object-contain',
                                    )}
                                />
                            )}
                            <div className={cn(variant === 'cards' && 'p-4')}>
                                <h3 className="font-bold text-[var(--theme-heading)]">
                                    {textValue(content[`${area}_heading`]) || `Our ${area}`}
                                </h3>
                                {body && (
                                    <p className="mt-2 line-clamp-4 text-sm leading-6 opacity-80">
                                        {htmlText(body)}
                                    </p>
                                )}
                            </div>
                        </article>
                    );
                })}
            </div>
        </div>
    );
}

function CtaPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const imageId = textValue(content.image_id);
    const layout = textValue(content.media_layout) || 'right';
    const background = Boolean(imageId) && layout === 'background';
    const imageLeft = layout === 'left';
    const overlay = Math.min(85, Math.max(20, Number(textValue(content.overlay_strength)) || 55)) / 100;

    return (
        <div
            className={cn(
                'relative overflow-hidden rounded-[var(--theme-radius)]',
                background && 'min-h-72 text-white',
            )}
        >
            {background && (
                <img
                    src={`/media/${imageId}/medium`}
                    alt={textValue(content.image_alt)}
                    className="absolute inset-0 size-full object-cover"
                />
            )}
            {background && <span className="absolute inset-0 bg-slate-950" style={{ opacity: overlay }} />}
            <div
                className={cn(
                    'relative grid items-center gap-6',
                    imageId && !background && 'sm:grid-cols-2',
                    variant === 'band' && 'py-3',
                )}
            >
                <div
                    className={cn(
                        background && 'z-10 max-w-2xl p-7',
                        imageLeft && imageId && !background && 'sm:order-2',
                    )}
                >
                    {textValue(content.eyebrow) && (
                        <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                            {textValue(content.eyebrow)}
                        </p>
                    )}
                    <h2
                        className={cn(
                            'text-xl font-bold sm:text-3xl',
                            !background && 'text-[var(--theme-heading)]',
                        )}
                    >
                        {textValue(content.heading) || label}
                    </h2>
                    {textValue(content.description) && (
                        <p className="mt-3 text-sm leading-6 opacity-80">{textValue(content.description)}</p>
                    )}
                    <div className="mt-5 flex flex-wrap gap-2">
                        {textValue(content.primary_label) && (
                            <span className="rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-4 py-2 text-xs font-bold text-[var(--theme-accent-foreground)]">
                                {textValue(content.primary_label)}
                            </span>
                        )}
                        {textValue(content.secondary_label) && (
                            <span className="rounded-[var(--theme-radius)] border px-4 py-2 text-xs font-bold">
                                {textValue(content.secondary_label)}
                            </span>
                        )}
                    </div>
                    {textValue(content.supporting_text) && (
                        <p className="mt-3 text-[11px] opacity-70">{textValue(content.supporting_text)}</p>
                    )}
                </div>
                {imageId && !background && (
                    <img
                        src={`/media/${imageId}/medium`}
                        alt={textValue(content.image_alt)}
                        className={cn(
                            'aspect-video w-full rounded-[var(--theme-radius)] object-cover',
                            imageLeft && 'sm:order-1',
                            textValue(content.image_fit) === 'contain' && 'object-contain',
                        )}
                    />
                )}
            </div>
        </div>
    );
}

function AboutPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const imageId = textValue(content.image_id);
    const imageLeft =
        variant === 'image-left' || (variant === 'default' && textValue(content.image_position) === 'left');
    const centered = variant === 'centered';
    return (
        <div
            className={cn(
                'grid items-center gap-6',
                imageId && !centered && 'sm:grid-cols-2',
                centered && 'text-center',
            )}
        >
            <div className={cn(imageLeft && imageId && 'sm:order-2')}>
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                <p className="mt-3 line-clamp-5 text-sm leading-6 opacity-80">
                    {htmlText(textValue(content.body) || textValue(content.description))}
                </p>
                {textValue(content.primary_label) && (
                    <span className="mt-5 inline-flex rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-4 py-2 text-xs font-bold text-[var(--theme-accent-foreground)]">
                        {textValue(content.primary_label)}
                    </span>
                )}
            </div>
            {imageId && (
                <img
                    src={`/media/${imageId}/medium`}
                    alt={textValue(content.image_alt)}
                    className={cn(
                        'aspect-[4/3] w-full rounded-[var(--theme-radius)] object-cover',
                        imageLeft && 'sm:order-1',
                        centered && 'mx-auto max-w-2xl',
                        textValue(content.image_fit) === 'contain' && 'object-contain',
                    )}
                />
            )}
        </div>
    );
}

function HeroPreview({ content, label }: { content: SectionContent; label: string }) {
    const imageId = textValue(content.image_id);
    const imageLeft = textValue(content.image_position) === 'left';
    return (
        <div className={cn('grid items-center gap-6', imageId && 'sm:grid-cols-2')}>
            <div className={cn(imageLeft && imageId && 'sm:order-2')}>
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-2xl font-bold text-[var(--theme-heading)] sm:text-4xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 text-sm leading-6 opacity-80">{textValue(content.description)}</p>
                )}
                <div className="mt-5 flex flex-wrap gap-2">
                    {textValue(content.primary_label) && (
                        <span className="rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-4 py-2 text-xs font-bold text-[var(--theme-accent-foreground)]">
                            {textValue(content.primary_label)}
                        </span>
                    )}
                    {textValue(content.secondary_label) && (
                        <span className="rounded-[var(--theme-radius)] border px-4 py-2 text-xs font-bold">
                            {textValue(content.secondary_label)}
                        </span>
                    )}
                </div>
            </div>
            {imageId && (
                <img
                    src={`/media/${imageId}/medium`}
                    alt={textValue(content.image_alt)}
                    className={cn(
                        'aspect-video w-full rounded-[var(--theme-radius)] object-cover',
                        imageLeft && 'sm:order-1',
                        textValue(content.image_fit) === 'contain' && 'object-contain',
                    )}
                />
            )}
        </div>
    );
}

function HeroSliderPreview({
    content,
    label,
    primary,
    dark,
}: {
    content: SectionContent;
    label: string;
    primary: boolean;
    dark: boolean;
}) {
    const slide = Array.isArray(content.slides)
        ? content.slides[0]
        : {
              image_id: '',
              mobile_image_id: '',
              alt: '',
              eyebrow: textValue(content.eyebrow),
              heading: textValue(content.heading),
              description: textValue(content.description),
              primary_label: textValue(content.primary_label),
              primary_url: textValue(content.primary_url),
          };
    const image = slide?.image_id ? `/media/${slide.image_id}/medium` : '';

    return (
        <div className="relative min-h-72 overflow-hidden rounded-[var(--theme-radius)] bg-[var(--theme-surface)]">
            {image && (
                <img src={image} alt={slide?.alt ?? ''} className="absolute inset-0 size-full object-cover" />
            )}
            <div
                className={cn(
                    'absolute inset-0',
                    image
                        ? 'bg-slate-950/50'
                        : 'bg-gradient-to-br from-[var(--theme-primary)] to-[var(--theme-accent)] opacity-20',
                )}
            />
            <div className="relative z-10 flex min-h-72 max-w-2xl flex-col justify-center p-7 text-left text-white">
                {slide?.eyebrow && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest uppercase">{slide.eyebrow}</p>
                )}
                <h2
                    className={cn(
                        'text-2xl font-bold',
                        !image && !primary && !dark && 'text-[var(--theme-heading)]',
                    )}
                >
                    {slide?.heading || label}
                </h2>
                {slide?.description && <p className="mt-3 text-sm opacity-85">{slide.description}</p>}
                {slide?.primary_label && (
                    <span className="mt-5 w-fit rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-4 py-2 text-xs font-bold text-[var(--theme-accent-foreground)]">
                        {slide.primary_label}
                    </span>
                )}
            </div>
            {Array.isArray(content.slides) && content.slides.length > 1 && (
                <span className="absolute right-4 bottom-4 z-20 rounded-full bg-black/60 px-2 py-1 text-[10px] text-white">
                    1 / {content.slides.length}
                </span>
            )}
        </div>
    );
}

function SpacerDividerPreview({ content, variant }: { content: SectionContent; variant: string }) {
    const allowed = [0, 16, 32, 48, 64, 96, 128];
    const before = Number(textValue(content.space_before));
    const after = Number(textValue(content.space_after));
    const width = textValue(content.line_width);
    const lineWidth =
        width === 'narrow' ? '25%' : width === 'medium' ? '50%' : width === 'wide' ? '75%' : '100%';
    const color =
        textValue(content.line_color) === 'primary'
            ? 'var(--theme-primary)'
            : textValue(content.line_color) === 'accent'
              ? 'var(--theme-accent)'
              : 'var(--theme-border)';
    const weight = Math.min(4, Math.max(1, Number(textValue(content.line_weight)) || 1));
    const style = textValue(content.line_style);
    const borderStyle = style === 'dashed' || style === 'dotted' ? style : 'solid';
    const label = textValue(content.line_label);
    const line = (
        <span className="min-w-0 flex-1" style={{ borderTop: `${weight}px ${borderStyle} ${color}` }} />
    );
    return (
        <div
            className="relative min-h-6"
            style={{
                paddingTop: allowed.includes(before) ? before : 48,
                paddingBottom: allowed.includes(after) ? after : 48,
            }}
        >
            {variant !== 'space' ? (
                <div
                    className={cn(
                        'relative mx-auto flex items-center',
                        width === 'narrow' && 'w-1/4',
                        width === 'medium' && 'w-1/2',
                        width === 'wide' && 'w-3/4',
                    )}
                    style={{ width: lineWidth }}
                >
                    {line}
                    {label && (
                        <span
                            className={cn(
                                'px-2 text-[10px] font-bold',
                                variant === 'line' && 'mx-2 rounded-full border px-3 py-1',
                            )}
                            style={variant === 'line' ? { borderColor: color } : undefined}
                        >
                            {label}
                        </span>
                    )}
                    {label && line}
                    {variant === 'line' && !label && (
                        <span
                            className="absolute left-1/2 size-2 -translate-x-1/2 rotate-45"
                            style={{ backgroundColor: color }}
                        />
                    )}
                </div>
            ) : (
                <span className="absolute inset-0 grid place-items-center text-[10px] opacity-45">
                    Preview guide only · blank space on live site
                </span>
            )}
        </div>
    );
}

function VideoPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const poster = textValue(content.poster_image_id);
    const ratio = textValue(content.aspect_ratio);
    const videoUrl = textValue(content.video_url);
    const provider = videoUrl.includes('youtu')
        ? 'YouTube'
        : videoUrl.includes('vimeo')
          ? 'Vimeo'
          : 'video file';
    return (
        <div className={cn('mx-auto', variant === 'contained' && 'max-w-3xl')}>
            {textValue(content.eyebrow) && (
                <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                    {textValue(content.eyebrow)}
                </p>
            )}
            <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                {textValue(content.heading) || label}
            </h2>
            {textValue(content.description) && (
                <p className="mt-3 text-sm leading-6 opacity-80">{textValue(content.description)}</p>
            )}
            <div
                className={cn(
                    'relative mt-6 grid place-items-center overflow-hidden rounded-[var(--theme-radius)] bg-slate-900 text-white',
                    ratio === 'vertical'
                        ? 'mx-auto aspect-[9/16] max-w-sm'
                        : ratio === 'standard'
                          ? 'aspect-[4/3]'
                          : 'aspect-video',
                )}
            >
                {poster && (
                    <img
                        src={`/media/${poster}/medium`}
                        alt={textValue(content.poster_alt)}
                        className="absolute inset-0 size-full object-cover"
                    />
                )}
                <span className="relative grid size-14 place-items-center rounded-full bg-white/90 text-xl text-slate-900 shadow-lg">
                    ▶
                </span>
                <span className="absolute right-3 bottom-3 rounded-full bg-slate-900/75 px-3 py-1 text-[10px] font-bold">
                    {textValue(content.video_url) ? `Play ${provider} video` : 'Add a video URL'}
                </span>
            </div>
            {textValue(content.show_caption) !== '0' && textValue(content.video_caption) && (
                <p className="mt-2 text-xs opacity-70">{textValue(content.video_caption)}</p>
            )}
            {(textValue(content.transcript_url) || textValue(content.primary_url)) && (
                <div className="mt-4 flex gap-3 text-xs font-semibold text-[var(--theme-accent)]">
                    {textValue(content.transcript_url) && (
                        <span>{textValue(content.transcript_label) || 'Transcript'}</span>
                    )}
                    {textValue(content.primary_url) && (
                        <span>{textValue(content.primary_label) || 'Learn more'}</span>
                    )}
                </div>
            )}
        </div>
    );
}

function ImageTextPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const imageId = textValue(content.image_id);
    const imageLeft =
        variant === 'image-left' || (variant === 'default' && textValue(content.image_position) === 'left');
    const stacked = variant === 'stacked';
    const imageSize = textValue(content.image_size);
    const availableActions: Array<[string, string, boolean]> = [
        [textValue(content.primary_label), textValue(content.primary_url), true],
        [textValue(content.secondary_label), textValue(content.secondary_url), false],
    ];
    const actions = availableActions.filter(([buttonLabel, url]) => buttonLabel !== '' && url !== '');

    return (
        <div
            className={cn(
                'grid items-center gap-6',
                imageId && !stacked && 'sm:grid-cols-2',
                imageId &&
                    !stacked &&
                    imageSize === 'compact' &&
                    (imageLeft ? 'sm:grid-cols-[0.7fr_1.5fr]' : 'sm:grid-cols-[1.5fr_0.7fr]'),
                imageId &&
                    !stacked &&
                    imageSize === 'large' &&
                    (imageLeft ? 'sm:grid-cols-[1.2fr_0.8fr]' : 'sm:grid-cols-[0.8fr_1.2fr]'),
            )}
        >
            <div className={cn(imageLeft && !stacked && 'sm:order-2')}>
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.description) && (
                    <p className="mt-3 text-sm leading-6 opacity-80">{textValue(content.description)}</p>
                )}
                {textValue(content.body) && (
                    <p className="mt-3 line-clamp-4 text-xs leading-5 opacity-70">
                        {htmlText(textValue(content.body))}
                    </p>
                )}
                {actions.length > 0 && (
                    <div className="mt-4 flex flex-wrap gap-2">
                        {actions.map(([buttonLabel, , primary], index) => (
                            <span
                                key={index}
                                className={cn(
                                    'rounded-[var(--theme-radius)] px-3 py-2 text-[10px] font-bold',
                                    primary
                                        ? 'bg-[var(--theme-accent)] text-white'
                                        : 'border border-[var(--theme-border)]',
                                )}
                            >
                                {buttonLabel}
                            </span>
                        ))}
                    </div>
                )}
                {textValue(content.supporting_text) && (
                    <p className="mt-3 text-[10px] opacity-60">{textValue(content.supporting_text)}</p>
                )}
            </div>
            {imageId && (
                <figure className={cn((imageLeft || stacked) && 'sm:order-1')}>
                    <img
                        src={`/media/${imageId}/medium`}
                        alt={textValue(content.image_alt)}
                        className={cn(
                            'w-full rounded-[var(--theme-radius)]',
                            textValue(content.image_ratio) === 'square'
                                ? 'aspect-square'
                                : textValue(content.image_ratio) === 'portrait'
                                  ? 'aspect-[3/4]'
                                  : 'aspect-[4/3]',
                            textValue(content.image_fit) === 'contain' ? 'object-contain' : 'object-cover',
                        )}
                    />
                    {textValue(content.show_image_caption) !== '0' && textValue(content.image_caption) && (
                        <figcaption className="mt-2 text-[10px] opacity-60">
                            {textValue(content.image_caption)}
                        </figcaption>
                    )}
                </figure>
            )}
        </div>
    );
}

function RichTextPreview({
    content,
    label,
    variant,
}: {
    content: SectionContent;
    label: string;
    variant: string;
}) {
    const imageId = textValue(content.image_id);
    const imageLeft = textValue(content.image_position) === 'left';
    return (
        <div
            className={cn(
                'grid items-center gap-6',
                imageId && 'sm:grid-cols-2',
                imageId &&
                    textValue(content.image_size) === 'compact' &&
                    (imageLeft ? 'sm:grid-cols-[0.7fr_1.5fr]' : 'sm:grid-cols-[1.5fr_0.7fr]'),
                imageId &&
                    textValue(content.image_size) === 'large' &&
                    (imageLeft ? 'sm:grid-cols-[1.2fr_0.8fr]' : 'sm:grid-cols-[0.8fr_1.2fr]'),
                variant === 'narrow' && 'mx-auto max-w-2xl',
                variant === 'wide' && 'max-w-none',
            )}
        >
            <div className={cn(imageLeft && imageId && 'sm:order-2')}>
                {textValue(content.eyebrow) && (
                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[var(--theme-accent)] uppercase">
                        {textValue(content.eyebrow)}
                    </p>
                )}
                <h2 className="text-xl font-bold text-[var(--theme-heading)] sm:text-3xl">
                    {textValue(content.heading) || label}
                </h2>
                {textValue(content.introduction) && (
                    <p className="mt-3 text-sm leading-6 font-medium opacity-90">
                        {textValue(content.introduction)}
                    </p>
                )}
                <p className="mt-3 line-clamp-5 text-sm leading-6 opacity-80">
                    {htmlText(textValue(content.body) || textValue(content.description))}
                </p>
                {textValue(content.primary_label) && textValue(content.primary_url) && (
                    <span className="mt-4 inline-block rounded-[var(--theme-radius)] bg-[var(--theme-accent)] px-3 py-2 text-[10px] font-bold text-white">
                        {textValue(content.primary_label)}
                    </span>
                )}
            </div>
            {imageId && (
                <figure className={cn(imageLeft && 'sm:order-1')}>
                    <img
                        src={`/media/${imageId}/medium`}
                        alt={textValue(content.image_alt)}
                        className={cn(
                            'w-full rounded-[var(--theme-radius)]',
                            textValue(content.image_ratio) === 'square'
                                ? 'aspect-square'
                                : textValue(content.image_ratio) === 'portrait'
                                  ? 'aspect-[3/4]'
                                  : 'aspect-[4/3]',
                            textValue(content.image_fit) === 'contain' ? 'object-contain' : 'object-cover',
                        )}
                    />
                    {textValue(content.show_image_caption) !== '0' && textValue(content.image_caption) && (
                        <figcaption className="mt-2 text-[10px] opacity-60">
                            {textValue(content.image_caption)}
                        </figcaption>
                    )}
                </figure>
            )}
        </div>
    );
}

function textValue(
    value:
        | string
        | HeroSlide[]
        | CounterItem[]
        | StatisticItem[]
        | BrandLogo[]
        | FeatureItem[]
        | ProcessItem[]
        | PricingItem[]
        | undefined,
): string {
    return typeof value === 'string' ? value : '';
}

function htmlText(value: string): string {
    if (typeof document === 'undefined') return value.replace(/<[^>]*>/g, ' ');
    const element = document.createElement('div');
    element.innerHTML = value;
    return element.textContent ?? '';
}
