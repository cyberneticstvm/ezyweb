import {
    DndContext,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Check,
    ChevronRight,
    Copy,
    Eye,
    EyeOff,
    GripVertical,
    ImageIcon,
    Layers3,
    Monitor,
    PanelLeft,
    PencilLine,
    Plus,
    Save,
    Smartphone,
    Tablet,
    Trash2,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';

import {
    BuilderPreview,
    type BrandLogo,
    type BuilderSection,
    type CounterItem,
    type StatisticItem,
    type FeatureItem,
    type PricingItem,
    type ProcessItem,
    type HeroSlide,
    type GalleryOption,
    type FaqOption,
    type BlogOption,
    type NewsOption,
    type EventOption,
    type CareerOption,
    type ProductOption,
    type PortfolioOption,
    type TestimonialOption,
    type TeamOption,
    type RegistryDefinition,
    type SectionContent,
} from '../../components/builder/section-preview';
import { useConfirm } from '../../components/confirm-provider';
import { EmptyState } from '../../components/empty-state';
import type { ThemeTokens } from '../../components/template-preview';
import { cn } from '../../lib/utils';
import { DashboardLayout } from '../../layouts/dashboard-layout';

interface BuilderPage {
    id: number;
    title: string;
    slug: string;
    navigation_label: string | null;
    is_home: boolean;
    is_enabled: boolean;
    sort_order: number;
    sections: BuilderSection[];
}

interface BuilderProps {
    builderWebsite: {
        id: number;
        name: string;
        type: string;
        template_key: string;
        draft_revision: number;
        draft_updated_at: string | null;
    };
    pages: BuilderPage[];
    registry: Record<string, RegistryDefinition>;
    theme: ThemeTokens;
    previewUrl: string;
    mediaAssets: MediaAssetOption[];
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
}

interface MediaAssetOption {
    public_id: string;
    title: string;
    alt_text: string | null;
    thumbnail_url: string;
}

type MobileTab = 'pages' | 'sections' | 'edit' | 'preview';
type Viewport = 'desktop' | 'tablet' | 'mobile';

export default function Builder({
    builderWebsite,
    pages,
    registry,
    theme,
    previewUrl,
    mediaAssets,
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
}: BuilderProps) {
    const [pageId, setPageId] = useState<number | null>(pages[0]?.id ?? null);
    const [sectionId, setSectionId] = useState<number | null>(pages[0]?.sections[0]?.id ?? null);
    const [mobileTab, setMobileTab] = useState<MobileTab>('sections');
    const [viewport, setViewport] = useState<Viewport>('desktop');
    const [newPageTitle, setNewPageTitle] = useState('');
    const [newSectionType, setNewSectionType] = useState(Object.keys(registry)[0] ?? 'hero');
    const [previewOverrides, setPreviewOverrides] = useState<
        Record<number, { revision: number; draft: Partial<BuilderSection> }>
    >({});
    const confirm = useConfirm();
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );
    const page = useMemo(() => pages.find((item) => item.id === pageId) ?? pages[0] ?? null, [pageId, pages]);
    const section = page?.sections.find((item) => item.id === sectionId) ?? null;
    const previewSections = (page?.sections ?? []).map((item) => ({
        ...item,
        ...(previewOverrides[item.id]?.revision === builderWebsite.draft_revision
            ? previewOverrides[item.id].draft
            : {}),
    }));
    const updatePreview = useCallback(
        (id: number, draft: Partial<BuilderSection>) => {
            setPreviewOverrides((current) => ({
                ...current,
                [id]: { revision: builderWebsite.draft_revision, draft },
            }));
        },
        [builderWebsite.draft_revision],
    );

    const selectPage = (selected: BuilderPage) => {
        setPageId(selected.id);
        setSectionId(selected.sections[0]?.id ?? null);
        setMobileTab('sections');
    };

    const selectSection = (id: number) => {
        setSectionId(id);
        setMobileTab('edit');
    };

    const addPage = () => {
        if (!newPageTitle.trim()) return;
        router.post(
            '/dashboard/builder/pages',
            { title: newPageTitle },
            {
                preserveScroll: true,
                onSuccess: () => setNewPageTitle(''),
                onError: (errors) => toast.error(Object.values(errors)[0] ?? 'Unable to add page.'),
            },
        );
    };

    const addSection = () => {
        if (!page) return;
        router.post(
            '/dashboard/builder/sections',
            { page_id: page.id, type: newSectionType },
            { preserveScroll: true, onError: () => toast.error('Unable to add this section.') },
        );
    };

    const reorder = (ids: number[]) => {
        if (!page) return;
        router.put(
            '/dashboard/builder/sections/reorder',
            { page_id: page.id, section_ids: ids },
            { preserveScroll: true },
        );
    };

    const moveSection = (id: number, direction: -1 | 1) => {
        if (!page) return;
        const current = page.sections.findIndex((item) => item.id === id);
        const next = current + direction;
        if (current < 0 || next < 0 || next >= page.sections.length) return;
        reorder(arrayMove(page.sections, current, next).map((item) => item.id));
    };

    const onDragEnd = ({ active, over }: DragEndEvent) => {
        if (!page || !over || active.id === over.id) return;
        const oldIndex = page.sections.findIndex((item) => item.id === active.id);
        const newIndex = page.sections.findIndex((item) => item.id === over.id);
        if (oldIndex >= 0 && newIndex >= 0)
            reorder(arrayMove(page.sections, oldIndex, newIndex).map((item) => item.id));
    };

    const pagesPanel = (
        <Panel
            title="Pages"
            description={builderWebsite.type === 'landing_page' ? 'Landing-page mode' : 'Multipage website'}
        >
            <div className="space-y-2">
                {pages.map((item) => (
                    <button
                        key={item.id}
                        onClick={() => selectPage(item)}
                        className={cn(
                            'flex w-full items-center gap-3 rounded-xl border px-3 py-3 text-left transition',
                            item.id === page?.id
                                ? 'border-blue-300 bg-blue-50'
                                : 'hover:border-slate-300 hover:bg-slate-50',
                        )}
                    >
                        <span className="grid size-8 place-items-center rounded-lg bg-white text-xs font-bold shadow-sm">
                            {item.is_home ? 'H' : item.title.charAt(0).toUpperCase()}
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-semibold">{item.title}</span>
                            <span className="block truncate text-[11px] text-slate-500">/{item.slug}</span>
                        </span>
                        {!item.is_enabled && (
                            <EyeOff className="size-4 text-slate-400" aria-label="Disabled page" />
                        )}
                        <ChevronRight className="size-4 text-slate-400" />
                    </button>
                ))}
            </div>
            {page && (
                <button
                    onClick={() => {
                        setSectionId(null);
                        setMobileTab('edit');
                    }}
                    className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold"
                >
                    <PencilLine className="size-3.5" /> Edit page settings
                </button>
            )}
            {(builderWebsite.type !== 'landing_page' || pages.length === 0) && (
                <div className="mt-5 border-t pt-4">
                    <label className="text-xs font-semibold text-slate-600" htmlFor="new-page-title">
                        Add page
                    </label>
                    <div className="mt-2 flex gap-2">
                        <input
                            id="new-page-title"
                            value={newPageTitle}
                            onChange={(event) => setNewPageTitle(event.target.value)}
                            onKeyDown={(event) => event.key === 'Enter' && addPage()}
                            placeholder="e.g. Contact"
                            className="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm"
                        />
                        <button
                            onClick={addPage}
                            className="rounded-lg bg-slate-950 px-3 text-white"
                            aria-label="Add page"
                        >
                            <Plus className="size-4" />
                        </button>
                    </div>
                </div>
            )}
        </Panel>
    );

    const sectionsPanel = (
        <Panel
            title="Website structure"
            description={page ? `${page.title} · ${page.sections.length} sections` : 'Select a page'}
        >
            {page && page.sections.length > 0 ? (
                <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
                    <SortableContext
                        items={page.sections.map((item) => item.id)}
                        strategy={verticalListSortingStrategy}
                    >
                        <div className="space-y-2">
                            {page.sections.map((item, index) => (
                                <SortableSection
                                    key={item.id}
                                    section={item}
                                    label={registry[item.type]?.label ?? item.type}
                                    selected={item.id === section?.id}
                                    first={index === 0}
                                    last={index === page.sections.length - 1}
                                    onSelect={() => selectSection(item.id)}
                                    onMove={(direction) => moveSection(item.id, direction)}
                                    onToggle={() =>
                                        saveSection(item, { is_enabled: !item.is_enabled }, () => undefined)
                                    }
                                />
                            ))}
                        </div>
                    </SortableContext>
                </DndContext>
            ) : (
                <EmptyState
                    icon={Layers3}
                    title="No sections yet"
                    description="Choose a section below to start building this page."
                />
            )}
            {page && (
                <div className="mt-5 border-t pt-4">
                    <label className="text-xs font-semibold text-slate-600" htmlFor="new-section-type">
                        Available sections
                    </label>
                    <select
                        id="new-section-type"
                        value={newSectionType}
                        onChange={(event) => setNewSectionType(event.target.value)}
                        className="mt-2 w-full rounded-lg border px-3 py-2 text-sm"
                    >
                        {Object.values(registry).map((definition) => (
                            <option key={definition.type} value={definition.type}>
                                {definition.category} · {definition.label}
                            </option>
                        ))}
                    </select>
                    <button
                        onClick={addSection}
                        className="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-slate-950 px-3 py-2 text-sm font-semibold text-white"
                    >
                        <Plus className="size-4" /> Add section
                    </button>
                </div>
            )}
        </Panel>
    );

    const editPanel =
        section && page ? (
            <SectionEditor
                key={`${section.id}-${section.updated_at ?? ''}`}
                section={section}
                definition={registry[section.type]}
                mediaAssets={mediaAssets}
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
                onDraft={updatePreview}
                onDelete={async () => {
                    if (
                        await confirm({
                            title: `Remove ${registry[section.type]?.label ?? section.type}?`,
                            description:
                                'This removes the section from the draft. Disabling is safer when you may need it later.',
                            confirmLabel: 'Remove section',
                        })
                    ) {
                        router.delete(`/dashboard/builder/sections/${section.id}`, { preserveScroll: true });
                    }
                }}
            />
        ) : page ? (
            <PageEditor page={page} confirm={confirm} />
        ) : (
            <Panel title="Edit" description="Create or select a page to continue">
                <EmptyState
                    icon={PanelLeft}
                    title="No page selected"
                    description="Your page settings and section fields will appear here."
                />
            </Panel>
        );

    const previewPanel = (
        <div className="min-w-0 rounded-2xl border bg-slate-200/70 p-3 sm:p-5">
            <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div className="flex rounded-lg border bg-white p-1" aria-label="Preview viewport">
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
                                'rounded-md p-2',
                                viewport === key ? 'bg-slate-950 text-white' : 'text-slate-500',
                            )}
                            aria-label={`${key} preview`}
                        >
                            <Icon className="size-4" />
                        </button>
                    ))}
                </div>
                <span className="flex items-center gap-1.5 text-xs font-medium text-slate-600">
                    <Check className="size-4 text-emerald-600" /> Draft revision{' '}
                    {builderWebsite.draft_revision}
                </span>
            </div>
            <BuilderPreview
                websiteName={builderWebsite.name}
                templateKey={builderWebsite.template_key}
                sections={previewSections}
                registry={registry}
                theme={theme}
                viewport={viewport}
                selectedId={section?.id ?? null}
                onSelect={selectSection}
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
        </div>
    );

    return (
        <DashboardLayout
            title="Website builder"
            description="Shape pages and reusable sections in a controlled, responsive draft workspace."
            breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Website builder' }]}
        >
            <Head title="Website builder" />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white p-3 shadow-sm">
                <div>
                    <p className="text-sm font-semibold text-slate-950">
                        {page?.title ?? 'No page selected'}
                    </p>
                    <p className="text-xs text-slate-500">Changes are saved as an unpublished draft.</p>
                </div>
                <div className="flex gap-2">
                    <a
                        href={previewUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="rounded-lg border px-3 py-2 text-xs font-semibold text-slate-700"
                    >
                        Preview draft
                    </a>
                    <Link
                        href="/dashboard/publishing"
                        className="rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white"
                    >
                        Publish
                    </Link>
                </div>
            </div>

            <div className="mb-4 grid grid-cols-4 gap-1 rounded-xl border bg-white p-1 lg:hidden">
                {(
                    [
                        ['pages', PanelLeft],
                        ['sections', Layers3],
                        ['edit', PencilLine],
                        ['preview', Eye],
                    ] as const
                ).map(([tab, Icon]) => (
                    <button
                        key={tab}
                        onClick={() => setMobileTab(tab)}
                        className={cn(
                            'flex flex-col items-center gap-1 rounded-lg px-2 py-2 text-[11px] capitalize',
                            mobileTab === tab && 'bg-slate-950 text-white',
                        )}
                    >
                        <Icon className="size-4" /> {tab}
                    </button>
                ))}
            </div>

            <div className="lg:hidden">
                {mobileTab === 'pages' && pagesPanel}
                {mobileTab === 'sections' && sectionsPanel}
                {mobileTab === 'edit' && editPanel}
                {mobileTab === 'preview' && previewPanel}
            </div>

            <div className="hidden min-h-[42rem] grid-cols-[15rem_minmax(26rem,1fr)_19rem] gap-4 lg:grid">
                <div className="space-y-4">
                    {pagesPanel}
                    {sectionsPanel}
                </div>
                {previewPanel}
                {editPanel}
            </div>
        </DashboardLayout>
    );
}

function Panel({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-2xl border bg-white p-4 shadow-sm">
            <h2 className="text-sm font-bold text-slate-950">{title}</h2>
            <p className="mt-1 mb-4 text-[11px] leading-4 text-slate-500">{description}</p>
            {children}
        </section>
    );
}

function SortableSection({
    section,
    label,
    selected,
    first,
    last,
    onSelect,
    onMove,
    onToggle,
}: {
    section: BuilderSection;
    label: string;
    selected: boolean;
    first: boolean;
    last: boolean;
    onSelect: () => void;
    onMove: (direction: -1 | 1) => void;
    onToggle: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: section.id,
    });
    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn(
                'rounded-xl border bg-white p-2',
                selected && 'border-blue-300 bg-blue-50',
                isDragging && 'z-20 shadow-xl',
            )}
        >
            <div className="flex items-center gap-1">
                <button
                    {...attributes}
                    {...listeners}
                    className="cursor-grab rounded p-1 text-slate-400 hover:bg-slate-100"
                    aria-label={`Drag ${label}`}
                >
                    <GripVertical className="size-4" />
                </button>
                <button onClick={onSelect} className="min-w-0 flex-1 px-1 text-left">
                    <span className="block truncate text-xs font-semibold">{label}</span>
                    <span className="block truncate text-[10px] text-slate-500">{section.variant}</span>
                </button>
                <button
                    onClick={onToggle}
                    className="rounded p-1 text-slate-500 hover:bg-slate-100"
                    aria-label={`${section.is_enabled ? 'Disable' : 'Enable'} ${label}`}
                >
                    {section.is_enabled ? <Eye className="size-4" /> : <EyeOff className="size-4" />}
                </button>
            </div>
            <div className="mt-1 flex items-center justify-between border-t pt-1 text-[9px] text-slate-500">
                <span>{visibilityLabel(section)}</span>
                <span className="flex">
                    <button
                        disabled={first}
                        onClick={() => onMove(-1)}
                        className="rounded p-1 disabled:opacity-25"
                        aria-label={`Move ${label} up`}
                    >
                        <ArrowUp className="size-3" />
                    </button>
                    <button
                        disabled={last}
                        onClick={() => onMove(1)}
                        className="rounded p-1 disabled:opacity-25"
                        aria-label={`Move ${label} down`}
                    >
                        <ArrowDown className="size-3" />
                    </button>
                </span>
            </div>
        </div>
    );
}

function SectionEditor({
    section,
    definition,
    mediaAssets,
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
    onDraft,
    onDelete,
}: {
    section: BuilderSection;
    definition: RegistryDefinition;
    mediaAssets: MediaAssetOption[];
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
    onDraft: (id: number, draft: Partial<BuilderSection>) => void;
    onDelete: () => void;
}) {
    const [content, setContent] = useState(section.content);
    const [variant, setVariant] = useState(section.variant);
    const [anchor, setAnchor] = useState(section.anchor ?? '');
    const [design, setDesign] = useState(section.design_settings);
    const [visibility, setVisibility] = useState({
        is_enabled: section.is_enabled,
        show_on_desktop: section.show_on_desktop,
        show_on_tablet: section.show_on_tablet,
        show_on_mobile: section.show_on_mobile,
    });
    const [saving, setSaving] = useState(false);
    const dirty =
        JSON.stringify({ content, variant, anchor, design, visibility }) !==
        JSON.stringify({
            content: section.content,
            variant: section.variant,
            anchor: section.anchor ?? '',
            design: section.design_settings,
            visibility: {
                is_enabled: section.is_enabled,
                show_on_desktop: section.show_on_desktop,
                show_on_tablet: section.show_on_tablet,
                show_on_mobile: section.show_on_mobile,
            },
        });

    useEffect(() => {
        onDraft(section.id, {
            content,
            variant,
            anchor,
            design_settings: design,
            ...visibility,
        });
    }, [anchor, content, design, onDraft, section.id, variant, visibility]);

    useEffect(() => {
        const warn = (event: BeforeUnloadEvent) => {
            if (!dirty) return;
            event.preventDefault();
        };
        window.addEventListener('beforeunload', warn);
        return () => window.removeEventListener('beforeunload', warn);
    }, [dirty]);

    const save = () => {
        setSaving(true);
        saveSection(section, { content, variant, anchor, design_settings: design, ...visibility }, () =>
            setSaving(false),
        );
    };

    return (
        <Panel title={definition?.label ?? section.type} description="Content and section design settings">
            <div className="space-y-4">
                {dirty && (
                    <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                        Unsaved edits · preview updated
                    </p>
                )}
                {section.type === 'hero' ? (
                    <HeroSectionEditor content={content} mediaAssets={mediaAssets} onChange={setContent} />
                ) : section.type === 'cta' ? (
                    <CtaSectionEditor content={content} mediaAssets={mediaAssets} onChange={setContent} />
                ) : section.type === 'about' ? (
                    <AboutSectionEditor content={content} mediaAssets={mediaAssets} onChange={setContent} />
                ) : section.type === 'mission-vision' ? (
                    <MissionVisionSectionEditor
                        content={content}
                        mediaAssets={mediaAssets}
                        onChange={setContent}
                    />
                ) : section.type === 'counters' ? (
                    <CountersSectionEditor content={content} onChange={setContent} />
                ) : section.type === 'statistics' ? (
                    <StatisticsSectionEditor content={content} onChange={setContent} />
                ) : section.type === 'features' ? (
                    <FeaturesSectionEditor
                        content={content}
                        mediaAssets={mediaAssets}
                        onChange={setContent}
                    />
                ) : section.type === 'process' ? (
                    <ProcessSectionEditor content={content} mediaAssets={mediaAssets} onChange={setContent} />
                ) : section.type === 'pricing' ? (
                    <PricingSectionEditor content={content} onChange={setContent} />
                ) : section.type === 'contact' ? (
                    <ContactSectionEditor content={content} onChange={setContent} />
                ) : section.type === 'newsletter' ? (
                    <NewsletterSectionEditor content={content} onChange={setContent} />
                ) : section.type === 'map' ? (
                    <MapSectionEditor content={content} onChange={setContent} />
                ) : section.type === 'brand-carousel' ? (
                    <BrandCarouselSectionEditor
                        content={content}
                        mediaAssets={mediaAssets}
                        onChange={setContent}
                    />
                ) : section.type === 'logo-cloud' ? (
                    <BrandCarouselSectionEditor
                        content={content}
                        mediaAssets={mediaAssets}
                        onChange={setContent}
                        mode="cloud"
                    />
                ) : section.type === 'products' ? (
                    <ProductsSectionEditor
                        content={content}
                        products={productOptions}
                        onChange={setContent}
                    />
                ) : section.type === 'testimonials' ? (
                    <TestimonialsSectionEditor
                        content={content}
                        testimonials={testimonialOptions}
                        onChange={setContent}
                    />
                ) : section.type === 'portfolio' ? (
                    <PortfolioSectionEditor
                        content={content}
                        projects={portfolioOptions}
                        onChange={setContent}
                    />
                ) : section.type === 'gallery' ? (
                    <GallerySectionEditor content={content} images={galleryOptions} onChange={setContent} />
                ) : section.type === 'team' ? (
                    <TeamSectionEditor content={content} members={teamOptions} onChange={setContent} />
                ) : section.type === 'faq' ? (
                    <FaqSectionEditor content={content} faqs={faqOptions} onChange={setContent} />
                ) : section.type === 'blog-posts' ? (
                    <BlogSectionEditor content={content} posts={blogOptions} onChange={setContent} />
                ) : section.type === 'news' ? (
                    <NewsSectionEditor content={content} articles={newsOptions} onChange={setContent} />
                ) : section.type === 'events' ? (
                    <EventsSectionEditor content={content} events={eventOptions} onChange={setContent} />
                ) : section.type === 'careers' ? (
                    <CareersSectionEditor content={content} jobs={careerOptions} onChange={setContent} />
                ) : section.type === 'hero-slider' ? (
                    <HeroSliderEditor content={content} mediaAssets={mediaAssets} onChange={setContent} />
                ) : section.type === 'rich-text' ? (
                    <RichTextSectionEditor
                        content={content}
                        mediaAssets={mediaAssets}
                        onChange={setContent}
                    />
                ) : section.type === 'image-text' ? (
                    <ImageTextSectionEditor
                        content={content}
                        mediaAssets={mediaAssets}
                        onChange={setContent}
                    />
                ) : section.type === 'video' ? (
                    <VideoSectionEditor content={content} mediaAssets={mediaAssets} onChange={setContent} />
                ) : section.type === 'spacer-divider' ? (
                    <SpacerDividerSectionEditor content={content} variant={variant} onChange={setContent} />
                ) : (
                    (definition?.fields ?? []).map((field) => (
                        <label key={field.key} className="block text-xs font-semibold text-slate-600">
                            {field.label}
                            {field.type === 'textarea' ? (
                                <textarea
                                    value={textContent(content[field.key])}
                                    maxLength={field.max}
                                    rows={5}
                                    onChange={(event) =>
                                        setContent({ ...content, [field.key]: event.target.value })
                                    }
                                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                                />
                            ) : (
                                <input
                                    value={textContent(content[field.key])}
                                    maxLength={field.max}
                                    onChange={(event) =>
                                        setContent({ ...content, [field.key]: event.target.value })
                                    }
                                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                                />
                            )}
                        </label>
                    ))
                )}
                <Select
                    label="Variant"
                    value={variant}
                    options={(definition?.variants ?? []).map((item) => item.key)}
                    onChange={setVariant}
                />
                <label className="block text-xs font-semibold text-slate-600">
                    Section anchor
                    <input
                        value={anchor}
                        onChange={(event) => setAnchor(event.target.value.toLowerCase())}
                        className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                    />
                </label>
                <div className="grid grid-cols-2 gap-3">
                    <Select
                        label="Surface"
                        value={design.surface}
                        options={['default', 'muted', 'primary', 'dark']}
                        onChange={(value) => setDesign({ ...design, surface: value })}
                    />
                    {section.type !== 'spacer-divider' && (
                        <Select
                            label="Spacing"
                            value={design.spacing}
                            options={['compact', 'normal', 'spacious']}
                            onChange={(value) => setDesign({ ...design, spacing: value })}
                        />
                    )}
                </div>
                <Select
                    label="Alignment"
                    value={design.alignment}
                    options={['left', 'center', 'right']}
                    onChange={(value) => setDesign({ ...design, alignment: value })}
                />
                <fieldset className="rounded-xl border p-3">
                    <legend className="px-1 text-xs font-bold">Visibility</legend>
                    {(
                        [
                            ['is_enabled', 'Enabled'],
                            ['show_on_desktop', 'Desktop'],
                            ['show_on_tablet', 'Tablet'],
                            ['show_on_mobile', 'Mobile'],
                        ] as const
                    ).map(([key, label]) => (
                        <label key={key} className="flex items-center justify-between py-1 text-xs">
                            {label}
                            <input
                                type="checkbox"
                                checked={visibility[key]}
                                onChange={(event) =>
                                    setVisibility({ ...visibility, [key]: event.target.checked })
                                }
                                className="size-4 accent-slate-950"
                            />
                        </label>
                    ))}
                </fieldset>
                <button
                    onClick={save}
                    disabled={saving}
                    className="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-950 px-3 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                >
                    <Save className="size-4" /> {saving ? 'Saving…' : 'Save draft'}
                </button>
                <div className="grid grid-cols-2 gap-2">
                    <button
                        onClick={() =>
                            router.post(
                                `/dashboard/builder/sections/${section.id}/duplicate`,
                                {},
                                { preserveScroll: true },
                            )
                        }
                        className="flex items-center justify-center gap-1 rounded-lg border px-2 py-2 text-xs font-semibold"
                    >
                        <Copy className="size-3.5" /> Duplicate
                    </button>
                    <button
                        onClick={onDelete}
                        className="flex items-center justify-center gap-1 rounded-lg border border-red-200 px-2 py-2 text-xs font-semibold text-red-600"
                    >
                        <Trash2 className="size-3.5" /> Remove
                    </button>
                </div>
            </div>
        </Panel>
    );
}

function CareersSectionEditor({
    content,
    jobs,
    onChange,
}: {
    content: SectionContent;
    jobs: CareerOption[];
    onChange: (content: SectionContent) => void;
}) {
    const selected = textContent(content.career_slugs).split('\n').filter(Boolean);
    const departments = [...new Set(jobs.map((job) => job.department).filter(Boolean))] as string[];
    const locations = [...new Set(jobs.map((job) => job.location).filter(Boolean))] as string[];
    const openCount = jobs.filter((job) => job.is_open).length;
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const toggle = (slug: string, checked: boolean) =>
        set(
            'career_slugs',
            [...new Set(checked ? [...selected, slug] : selected.filter((item) => item !== slug))].join('\n'),
        );
    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                This section displays active, unexpired positions. Manage job descriptions and application
                details in{' '}
                <Link href="/dashboard/content/careers" className="font-bold text-blue-700">
                    Content → Careers
                </Link>
                .
                <span className="ml-1 font-semibold">
                    {openCount} open · {jobs.length} total
                </span>
            </div>
            <label className="block text-xs font-semibold text-slate-600">
                Eyebrow
                <input
                    value={textContent(content.eyebrow)}
                    onChange={(e) => set('eyebrow', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <label className="block text-xs font-semibold text-slate-600">
                Heading
                <input
                    value={textContent(content.heading)}
                    onChange={(e) => set('heading', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <label className="block text-xs font-semibold text-slate-600">
                Description
                <textarea
                    rows={3}
                    value={textContent(content.description)}
                    onChange={(e) => set('description', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Position selection and filters</legend>
                <div className="grid grid-cols-2 gap-3">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Selection
                        <select
                            value={textContent(content.career_selection_mode) || 'all'}
                            onChange={(e) => set('career_selection_mode', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="all">All open positions</option>
                            <option value="selected">Selected positions</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Department
                        <select
                            value={textContent(content.career_department)}
                            onChange={(e) => set('career_department', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All departments</option>
                            {departments.map((department) => (
                                <option key={department}>{department}</option>
                            ))}
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Location
                        <select
                            value={textContent(content.career_location)}
                            onChange={(e) => set('career_location', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All locations</option>
                            {locations.map((location) => (
                                <option key={location}>{location}</option>
                            ))}
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Employment
                        <select
                            value={textContent(content.career_employment_type)}
                            onChange={(e) => set('career_employment_type', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All types</option>
                            {['full-time', 'part-time', 'contract', 'internship', 'temporary'].map((type) => (
                                <option key={type} value={type}>
                                    {type.replace('-', ' ')}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Workplace
                        <select
                            value={textContent(content.career_workplace_type)}
                            onChange={(e) => set('career_workplace_type', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All workplaces</option>
                            {['on-site', 'hybrid', 'remote'].map((type) => (
                                <option key={type}>{type}</option>
                            ))}
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Sort
                        <select
                            value={textContent(content.career_sort) || 'display-order'}
                            onChange={(e) => set('career_sort', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="display-order">Display order</option>
                            <option value="title">Title A–Z</option>
                            <option value="closing-soonest">Closing soonest</option>
                            <option value="location">Location A–Z</option>
                        </select>
                    </label>
                </div>
                {content.career_selection_mode === 'selected' && (
                    <div className="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-lg border bg-slate-50 p-2">
                        {jobs.length ? (
                            jobs.map((job) => (
                                <label
                                    key={job.slug}
                                    className="flex items-start gap-2 rounded bg-white p-2 text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(job.slug)}
                                        onChange={(e) => toggle(job.slug, e.target.checked)}
                                        className="mt-0.5 size-4"
                                    />
                                    <span>
                                        <strong>{job.title}</strong>
                                        <small className="block text-slate-500">
                                            {job.department || 'No department'} ·{' '}
                                            {job.is_open ? 'Open' : 'Closed / inactive'}
                                        </small>
                                    </span>
                                </label>
                            ))
                        ) : (
                            <p className="p-2 text-xs text-slate-500">No positions yet.</p>
                        )}
                    </div>
                )}
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Maximum
                        <input
                            type="number"
                            min="1"
                            max="50"
                            value={textContent(content.career_limit) || '8'}
                            onChange={(e) => set('career_limit', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.career_columns) || '2'}
                            onChange={(e) => set('career_columns', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Position cards</legend>
                <div className="grid grid-cols-2 gap-2">
                    {(
                        [
                            ['show_summaries', 'Summaries'],
                            ['show_departments', 'Departments'],
                            ['show_locations', 'Locations'],
                            ['show_employment_types', 'Employment types'],
                            ['show_workplace_types', 'Workplace types'],
                            ['show_salaries', 'Salary'],
                            ['show_closing_dates', 'Closing dates'],
                            ['show_apply_button', 'Position button'],
                            ['show_archive_link', 'View-all link'],
                        ] as const
                    ).map(([key, label]) => (
                        <label
                            key={key}
                            className="flex items-center justify-between rounded-lg bg-slate-50 px-2 py-2 text-xs"
                        >
                            {label}
                            <input
                                type="checkbox"
                                checked={content[key] !== '0'}
                                onChange={(e) => set(key, e.target.checked ? '1' : '0')}
                            />
                        </label>
                    ))}
                </div>
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Position button label
                        <input
                            value={textContent(content.apply_label)}
                            onChange={(e) => set('apply_label', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Archive label
                        <input
                            value={textContent(content.archive_label)}
                            onChange={(e) => set('archive_label', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                </div>
            </fieldset>
        </div>
    );
}

function EventsSectionEditor({
    content,
    events,
    onChange,
}: {
    content: SectionContent;
    events: EventOption[];
    onChange: (content: SectionContent) => void;
}) {
    const selected = textContent(content.event_slugs).split('\n').filter(Boolean);
    const categories = [...new Set(events.map((event) => event.category).filter(Boolean))] as string[];
    const publishedCount = events.filter((event) => event.is_published).length;
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const toggle = (slug: string, checked: boolean) =>
        set(
            'event_slugs',
            [...new Set(checked ? [...selected, slug] : selected.filter((item) => item !== slug))].join('\n'),
        );
    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                This section displays published events. Manage dates, venues, online links and registration in{' '}
                <Link href="/dashboard/content/events" className="font-bold text-blue-700">
                    Content → Events
                </Link>
                .
                <span className="ml-1 font-semibold">
                    {publishedCount} published · {events.length} total
                </span>
            </div>
            <label className="block text-xs font-semibold text-slate-600">
                Eyebrow
                <input
                    value={textContent(content.eyebrow)}
                    onChange={(e) => set('eyebrow', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <label className="block text-xs font-semibold text-slate-600">
                Heading
                <input
                    value={textContent(content.heading)}
                    onChange={(e) => set('heading', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <label className="block text-xs font-semibold text-slate-600">
                Description
                <textarea
                    rows={3}
                    value={textContent(content.description)}
                    onChange={(e) => set('description', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Event selection</legend>
                <div className="grid grid-cols-2 gap-3">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Selection
                        <select
                            value={textContent(content.event_selection_mode) || 'all'}
                            onChange={(e) => set('event_selection_mode', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="all">All events</option>
                            <option value="selected">Selected events</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Category
                        <select
                            value={textContent(content.event_category)}
                            onChange={(e) => set('event_category', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All categories</option>
                            {categories.map((category) => (
                                <option key={category}>{category}</option>
                            ))}
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Timeframe
                        <select
                            value={textContent(content.event_timeframe) || 'upcoming'}
                            onChange={(e) => set('event_timeframe', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="upcoming">Upcoming</option>
                            <option value="past">Past</option>
                            <option value="all">All dates</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Sort
                        <select
                            value={textContent(content.event_sort) || 'soonest'}
                            onChange={(e) => set('event_sort', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="soonest">Soonest first</option>
                            <option value="latest">Latest first</option>
                            <option value="title">Title A–Z</option>
                        </select>
                    </label>
                </div>
                {content.event_selection_mode === 'selected' && (
                    <div className="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-lg border bg-slate-50 p-2">
                        {events.length ? (
                            events.map((event) => (
                                <label
                                    key={event.slug}
                                    className="flex items-start gap-2 rounded bg-white p-2 text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(event.slug)}
                                        onChange={(e) => toggle(event.slug, e.target.checked)}
                                        className="mt-0.5 size-4"
                                    />
                                    <span>
                                        <strong>{event.title}</strong>
                                        <small className="block text-slate-500">
                                            {event.event_start_date || 'No date'} · {event.status}
                                        </small>
                                    </span>
                                </label>
                            ))
                        ) : (
                            <p className="p-2 text-xs text-slate-500">No events yet.</p>
                        )}
                    </div>
                )}
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Maximum
                        <input
                            type="number"
                            min="1"
                            max="24"
                            value={textContent(content.event_limit) || '6'}
                            onChange={(e) => set('event_limit', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.event_columns) || '3'}
                            onChange={(e) => set('event_columns', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Event cards</legend>
                <div className="grid grid-cols-2 gap-2">
                    {(
                        [
                            ['show_images', 'Images'],
                            ['show_excerpts', 'Summaries'],
                            ['show_dates', 'Dates'],
                            ['show_times', 'Times'],
                            ['show_venues', 'Venues / online'],
                            ['show_addresses', 'Addresses'],
                            ['show_categories', 'Categories'],
                            ['show_registration', 'Registration button'],
                            ['show_archive_link', 'View-all link'],
                        ] as const
                    ).map(([key, label]) => (
                        <label
                            key={key}
                            className="flex items-center justify-between rounded-lg bg-slate-50 px-2 py-2 text-xs"
                        >
                            {label}
                            <input
                                type="checkbox"
                                checked={content[key] !== '0'}
                                onChange={(e) => set(key, e.target.checked ? '1' : '0')}
                            />
                        </label>
                    ))}
                </div>
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Registration label
                        <input
                            value={textContent(content.registration_label)}
                            onChange={(e) => set('registration_label', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Archive label
                        <input
                            value={textContent(content.archive_label)}
                            onChange={(e) => set('archive_label', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                </div>
                <label className="mt-3 block text-[11px] font-semibold text-slate-600">
                    Image fit
                    <select
                        value={textContent(content.image_fit) || 'cover'}
                        onChange={(e) => set('image_fit', e.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        <option value="cover">Cover</option>
                        <option value="contain">Contain</option>
                    </select>
                </label>
            </fieldset>
        </div>
    );
}

function NewsSectionEditor({
    content,
    articles,
    onChange,
}: {
    content: SectionContent;
    articles: NewsOption[];
    onChange: (content: SectionContent) => void;
}) {
    const selected = textContent(content.news_slugs).split('\n').filter(Boolean);
    const categories = [...new Set(articles.map((article) => article.category).filter(Boolean))] as string[];
    const publishedCount = articles.filter((article) => article.is_published).length;
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const toggle = (slug: string, checked: boolean) =>
        set(
            'news_slugs',
            [...new Set(checked ? [...selected, slug] : selected.filter((item) => item !== slug))].join('\n'),
        );
    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                This section displays published news. Create articles, upload inline images, manage categories
                and scheduling in{' '}
                <Link href="/dashboard/content/news" className="font-bold text-blue-700">
                    Content → News
                </Link>
                .
                <span className="ml-1 font-semibold">
                    {publishedCount} published · {articles.length} total
                </span>
            </div>
            <label className="block text-xs font-semibold text-slate-600">
                Eyebrow
                <input
                    value={textContent(content.eyebrow)}
                    onChange={(e) => set('eyebrow', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <label className="block text-xs font-semibold text-slate-600">
                Heading
                <input
                    value={textContent(content.heading)}
                    onChange={(e) => set('heading', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <label className="block text-xs font-semibold text-slate-600">
                Description
                <textarea
                    rows={3}
                    value={textContent(content.description)}
                    onChange={(e) => set('description', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Article selection</legend>
                <div className="grid grid-cols-2 gap-3">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Selection
                        <select
                            value={textContent(content.news_selection_mode) || 'all'}
                            onChange={(e) => set('news_selection_mode', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="all">All news</option>
                            <option value="selected">Selected articles</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Category
                        <select
                            value={textContent(content.news_category)}
                            onChange={(e) => set('news_category', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All categories</option>
                            {categories.map((category) => (
                                <option key={category}>{category}</option>
                            ))}
                        </select>
                    </label>
                </div>
                {content.news_selection_mode === 'selected' && (
                    <div className="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-lg border bg-slate-50 p-2">
                        {articles.length ? (
                            articles.map((article) => (
                                <label
                                    key={article.slug}
                                    className="flex items-start gap-2 rounded bg-white p-2 text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(article.slug)}
                                        onChange={(e) => toggle(article.slug, e.target.checked)}
                                        className="mt-0.5 size-4"
                                    />
                                    <span>
                                        <strong>{article.title}</strong>
                                        <small className="block text-slate-500">
                                            {article.category || 'Uncategorised'} · {article.status}
                                        </small>
                                    </span>
                                </label>
                            ))
                        ) : (
                            <p className="p-2 text-xs text-slate-500">No news articles yet.</p>
                        )}
                    </div>
                )}
                <div className="mt-3 grid grid-cols-3 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Maximum
                        <input
                            type="number"
                            min="1"
                            max="24"
                            value={textContent(content.news_limit) || '6'}
                            onChange={(e) => set('news_limit', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Sort
                        <select
                            value={textContent(content.news_sort) || 'newest'}
                            onChange={(e) => set('news_sort', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="newest">Newest</option>
                            <option value="oldest">Oldest</option>
                            <option value="title">Title A–Z</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.news_columns) || '3'}
                            onChange={(e) => set('news_columns', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Article cards</legend>
                <div className="grid grid-cols-2 gap-2">
                    {(
                        [
                            ['show_images', 'Images'],
                            ['show_excerpts', 'Summaries'],
                            ['show_authors', 'Bylines'],
                            ['show_dates', 'Dates'],
                            ['show_categories', 'Categories'],
                            ['show_tags', 'Tags'],
                            ['show_reading_time', 'Reading time'],
                            ['show_archive_link', 'View-all link'],
                        ] as const
                    ).map(([key, label]) => (
                        <label
                            key={key}
                            className="flex items-center justify-between rounded-lg bg-slate-50 px-2 py-2 text-xs"
                        >
                            {label}
                            <input
                                type="checkbox"
                                checked={content[key] !== '0'}
                                onChange={(e) => set(key, e.target.checked ? '1' : '0')}
                            />
                        </label>
                    ))}
                </div>
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Read-more label
                        <input
                            value={textContent(content.read_more_label)}
                            onChange={(e) => set('read_more_label', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Archive label
                        <input
                            value={textContent(content.archive_label)}
                            onChange={(e) => set('archive_label', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                </div>
                <label className="mt-3 block text-[11px] font-semibold text-slate-600">
                    Image fit
                    <select
                        value={textContent(content.image_fit) || 'cover'}
                        onChange={(e) => set('image_fit', e.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        <option value="cover">Cover</option>
                        <option value="contain">Contain</option>
                    </select>
                </label>
            </fieldset>
        </div>
    );
}

function BlogSectionEditor({
    content,
    posts,
    onChange,
}: {
    content: SectionContent;
    posts: BlogOption[];
    onChange: (content: SectionContent) => void;
}) {
    const selected = textContent(content.blog_slugs).split('\n').filter(Boolean);
    const categories = [...new Set(posts.map((post) => post.category).filter(Boolean))] as string[];
    const publishedCount = posts.filter((post) => post.is_published).length;
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const toggle = (slug: string, checked: boolean) =>
        set(
            'blog_slugs',
            [...new Set(checked ? [...selected, slug] : selected.filter((item) => item !== slug))].join('\n'),
        );
    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                This section displays published posts. Create articles, upload inline images, manage
                categories and scheduling in{' '}
                <Link href="/dashboard/content/blog" className="font-bold text-blue-700">
                    Content → Blog
                </Link>
                .
                <span className="ml-1 font-semibold">
                    {publishedCount} published · {posts.length} total
                </span>
            </div>
            <label className="block text-xs font-semibold text-slate-600">
                Eyebrow
                <input
                    value={textContent(content.eyebrow)}
                    onChange={(e) => set('eyebrow', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <label className="block text-xs font-semibold text-slate-600">
                Heading
                <input
                    value={textContent(content.heading)}
                    onChange={(e) => set('heading', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <label className="block text-xs font-semibold text-slate-600">
                Description
                <textarea
                    rows={3}
                    value={textContent(content.description)}
                    onChange={(e) => set('description', e.target.value)}
                    className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
                />
            </label>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Post selection</legend>
                <div className="grid grid-cols-2 gap-3">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Selection
                        <select
                            value={textContent(content.blog_selection_mode) || 'all'}
                            onChange={(e) => set('blog_selection_mode', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="all">All posts</option>
                            <option value="selected">Selected posts</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Category
                        <select
                            value={textContent(content.blog_category)}
                            onChange={(e) => set('blog_category', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All categories</option>
                            {categories.map((category) => (
                                <option key={category}>{category}</option>
                            ))}
                        </select>
                    </label>
                </div>
                {content.blog_selection_mode === 'selected' && (
                    <div className="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-lg border bg-slate-50 p-2">
                        {posts.length ? (
                            posts.map((post) => (
                                <label
                                    key={post.slug}
                                    className="flex items-start gap-2 rounded bg-white p-2 text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(post.slug)}
                                        onChange={(e) => toggle(post.slug, e.target.checked)}
                                        className="mt-0.5 size-4"
                                    />
                                    <span>
                                        <strong>{post.title}</strong>
                                        <small className="block text-slate-500">
                                            {post.category || 'Uncategorised'} · {post.status}
                                        </small>
                                    </span>
                                </label>
                            ))
                        ) : (
                            <p className="p-2 text-xs text-slate-500">No blog posts yet.</p>
                        )}
                    </div>
                )}
                <div className="mt-3 grid grid-cols-3 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Maximum
                        <input
                            type="number"
                            min="1"
                            max="24"
                            value={textContent(content.blog_limit) || '6'}
                            onChange={(e) => set('blog_limit', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Sort
                        <select
                            value={textContent(content.blog_sort) || 'newest'}
                            onChange={(e) => set('blog_sort', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="newest">Newest</option>
                            <option value="oldest">Oldest</option>
                            <option value="title">Title A–Z</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.blog_columns) || '3'}
                            onChange={(e) => set('blog_columns', e.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Card content</legend>
                <div className="grid grid-cols-2 gap-2">
                    {(
                        [
                            ['show_images', 'Images'],
                            ['show_excerpts', 'Excerpts'],
                            ['show_authors', 'Authors'],
                            ['show_dates', 'Dates'],
                            ['show_categories', 'Categories'],
                            ['show_tags', 'Tags'],
                            ['show_reading_time', 'Reading time'],
                            ['show_archive_link', 'View-all link'],
                        ] as const
                    ).map(([key, label]) => (
                        <label
                            key={key}
                            className="flex items-center justify-between rounded-lg bg-slate-50 px-2 py-2 text-xs"
                        >
                            {label}
                            <input
                                type="checkbox"
                                checked={content[key] !== '0'}
                                onChange={(e) => set(key, e.target.checked ? '1' : '0')}
                            />
                        </label>
                    ))}
                </div>
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Read-more label
                        <input
                            value={textContent(content.read_more_label)}
                            onChange={(e) => set('read_more_label', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Archive label
                        <input
                            value={textContent(content.archive_label)}
                            onChange={(e) => set('archive_label', e.target.value)}
                            className="mt-1 w-full rounded-lg border px-2 py-2 text-xs font-normal"
                        />
                    </label>
                </div>
                <label className="mt-3 block text-[11px] font-semibold text-slate-600">
                    Image fit
                    <select
                        value={textContent(content.image_fit) || 'cover'}
                        onChange={(e) => set('image_fit', e.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        <option value="cover">Cover</option>
                        <option value="contain">Contain</option>
                    </select>
                </label>
            </fieldset>
        </div>
    );
}

function FaqSectionEditor({
    content,
    faqs,
    onChange,
}: {
    content: SectionContent;
    faqs: FaqOption[];
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const selected = textContent(content.faq_keys).split('\n').filter(Boolean);
    const categories = [...new Set(faqs.map((faq) => faq.category).filter(Boolean))] as string[];
    const activeCount = faqs.filter((faq) => faq.is_active).length;
    const toggleFaq = (key: string, checked: boolean) => {
        const next = checked ? [...selected, key] : selected.filter((item) => item !== key);
        set('faq_keys', [...new Set(next)].join('\n'));
    };

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">FAQ source</legend>
                <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs leading-5 text-slate-600">
                    Add questions, answers, categories, active status, and display order in{' '}
                    <Link href="/dashboard/content/faq" className="font-bold text-blue-700">
                        Content → FAQ
                    </Link>
                    . This section currently has {activeCount} active question{activeCount === 1 ? '' : 's'}.
                </p>
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Question selection
                        <select
                            value={textContent(content.faq_selection_mode) || 'all'}
                            onChange={(event) => set('faq_selection_mode', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="all">All active questions</option>
                            <option value="selected">Selected questions</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Category
                        <select
                            value={textContent(content.faq_category)}
                            onChange={(event) => set('faq_category', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All categories</option>
                            {categories.map((category) => (
                                <option key={category} value={category}>
                                    {category}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>
                {content.faq_selection_mode === 'selected' && (
                    <div className="mt-3 max-h-64 space-y-1 overflow-y-auto rounded-lg border p-2">
                        {faqs.length ? (
                            faqs.map((faq) => (
                                <label
                                    key={faq.key}
                                    className="flex items-start gap-2 rounded-md p-2 text-xs hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(faq.key)}
                                        onChange={(event) => toggleFaq(faq.key, event.target.checked)}
                                        className="mt-0.5"
                                    />
                                    <span>
                                        <strong>{faq.question}</strong>
                                        <span className="block text-[10px] text-slate-500">
                                            {faq.category || 'Uncategorised'}
                                            {faq.is_active ? '' : ' · Inactive'}
                                        </span>
                                    </span>
                                </label>
                            ))
                        ) : (
                            <p className="p-2 text-xs text-slate-500">No FAQs have been added yet.</p>
                        )}
                    </div>
                )}
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Maximum questions
                        <select
                            value={textContent(content.faq_limit) || '12'}
                            onChange={(event) => set('faq_limit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {[1, 2, 3, 4, 6, 8, 10, 12, 16, 20, 24, 30, 40, 50].map((limit) => (
                                <option key={limit} value={limit}>
                                    {limit}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Sort questions
                        <select
                            value={textContent(content.faq_sort) || 'display-order'}
                            onChange={(event) => set('faq_sort', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="display-order">Display order</option>
                            <option value="question">Question A–Z</option>
                            <option value="category">Category A–Z</option>
                        </select>
                    </label>
                </div>
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Questions and accordion</legend>
                <div className="mb-2">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Initially expanded
                        <select
                            value={textContent(content.default_open) || 'first'}
                            onChange={(event) => set('default_open', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="none">None</option>
                            <option value="first">First question</option>
                            <option value="all">All questions</option>
                        </select>
                    </label>
                </div>
                {[
                    ['show_categories', 'Show categories'],
                    ['show_numbers', 'Number the questions'],
                    ['allow_multiple', 'Allow multiple answers open'],
                    ['show_expand_controls', 'Show expand and collapse controls'],
                ].map(([key, label]) => (
                    <Toggle
                        key={key}
                        label={label}
                        checked={content[key] !== '0'}
                        onChange={(checked) => set(key, checked ? '1' : '0')}
                    />
                ))}
                <p className="mt-2 text-[10px] leading-4 text-slate-500">
                    Choose the Accordion or Two column variant below to change the public presentation.
                </p>
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">FAQ search</legend>
                <Toggle
                    label="Let visitors search questions"
                    checked={content.enable_search === '1'}
                    onChange={(checked) => set('enable_search', checked ? '1' : '0')}
                />
                {content.enable_search === '1' && (
                    <div className="mt-2">
                        <SlideField
                            label="Search placeholder"
                            value={textContent(content.search_placeholder)}
                            maxLength={100}
                            onChange={(value) => set('search_placeholder', value)}
                        />
                    </div>
                )}
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Help call to action</legend>
                <Toggle
                    label="Show contact call to action"
                    checked={content.show_cta === '1'}
                    onChange={(checked) => set('show_cta', checked ? '1' : '0')}
                />
                {content.show_cta === '1' && (
                    <div className="mt-2 space-y-2">
                        <SlideField
                            label="Prompt"
                            value={textContent(content.cta_text)}
                            maxLength={160}
                            onChange={(value) => set('cta_text', value)}
                        />
                        <SlideField
                            label="Button label"
                            value={textContent(content.cta_label)}
                            maxLength={60}
                            onChange={(value) => set('cta_label', value)}
                        />
                        <SlideField
                            label="Button URL"
                            value={textContent(content.cta_url)}
                            maxLength={2048}
                            onChange={(value) => set('cta_url', value)}
                        />
                    </div>
                )}
            </fieldset>
        </div>
    );
}

function TeamSectionEditor({
    content,
    members,
    onChange,
}: {
    content: SectionContent;
    members: TeamOption[];
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const selected = textContent(content.team_keys).split('\n').filter(Boolean);
    const roles = [...new Set(members.map((member) => member.role).filter(Boolean))] as string[];
    const activeCount = members.filter((member) => member.is_active).length;
    const featuredCount = members.filter((member) => member.is_active && member.is_featured).length;
    const toggleMember = (key: string, checked: boolean) => {
        const next = checked ? [...selected, key] : selected.filter((item) => item !== key);
        set('team_keys', [...new Set(next)].join('\n'));
    };

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Team source</legend>
                <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs leading-5 text-slate-600">
                    Add names, roles, photos, biographies, contact details, LinkedIn links, and featured
                    status in{' '}
                    <Link href="/dashboard/content/team" className="font-bold text-blue-700">
                        Content → Team
                    </Link>
                    . This section currently has {activeCount} active member{activeCount === 1 ? '' : 's'}
                    {featuredCount > 0 ? `, including ${featuredCount} featured` : ''}.
                </p>
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Member selection
                        <select
                            value={textContent(content.team_selection_mode) || 'all'}
                            onChange={(event) => set('team_selection_mode', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="all">All active members</option>
                            <option value="selected">Selected members</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Role
                        <select
                            value={textContent(content.team_role)}
                            onChange={(event) => set('team_role', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All roles</option>
                            {roles.map((role) => (
                                <option key={role} value={role}>
                                    {role}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>
                {content.team_selection_mode === 'selected' && (
                    <div className="mt-3 max-h-64 space-y-1 overflow-y-auto rounded-lg border p-2">
                        {members.length ? (
                            members.map((member) => (
                                <label
                                    key={member.key}
                                    className="flex items-center gap-2 rounded-md p-2 text-xs hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(member.key)}
                                        onChange={(event) => toggleMember(member.key, event.target.checked)}
                                    />
                                    {member.photo ? (
                                        <img
                                            src={member.photo}
                                            alt=""
                                            className="size-10 rounded-full object-cover"
                                        />
                                    ) : (
                                        <span className="grid size-10 place-items-center rounded-full bg-slate-100 font-bold">
                                            {member.name.charAt(0)}
                                        </span>
                                    )}
                                    <span className="min-w-0">
                                        <strong className="block truncate">{member.name}</strong>
                                        <span className="block text-[10px] text-slate-500">
                                            {member.role || 'No role'}
                                            {member.is_active ? '' : ' · Inactive'}
                                        </span>
                                    </span>
                                </label>
                            ))
                        ) : (
                            <p className="p-2 text-xs text-slate-500">No team members have been added yet.</p>
                        )}
                    </div>
                )}
                <div className="mt-3">
                    <Toggle
                        label="Only featured team members"
                        checked={content.team_featured_only === '1'}
                        onChange={(checked) => set('team_featured_only', checked ? '1' : '0')}
                    />
                </div>
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Maximum members
                        <select
                            value={textContent(content.team_limit) || '8'}
                            onChange={(event) => set('team_limit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {[1, 2, 3, 4, 6, 8, 9, 12, 16, 20, 24].map((limit) => (
                                <option key={limit} value={limit}>
                                    {limit}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Sort members
                        <select
                            value={textContent(content.team_sort) || 'display-order'}
                            onChange={(event) => set('team_sort', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="display-order">Display order</option>
                            <option value="featured-first">Featured first</option>
                            <option value="name">Name A–Z</option>
                            <option value="role">Role A–Z</option>
                        </select>
                    </label>
                </div>
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Layout and profiles</legend>
                <div className="mb-2 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.team_columns) || '4'}
                            onChange={(event) => set('team_columns', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2 columns</option>
                            <option value="3">3 columns</option>
                            <option value="4">4 columns</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Photo shape
                        <select
                            value={textContent(content.image_shape) || 'portrait'}
                            onChange={(event) => set('image_shape', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="portrait">Portrait</option>
                            <option value="square">Square</option>
                            <option value="circle">Circle</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Photo fit
                        <select
                            value={textContent(content.image_fit) || 'cover'}
                            onChange={(event) => set('image_fit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="cover">Crop to fill</option>
                            <option value="contain">Show full photo</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Card alignment
                        <select
                            value={textContent(content.card_alignment) || 'left'}
                            onChange={(event) => set('card_alignment', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="left">Left</option>
                            <option value="center">Centre</option>
                        </select>
                    </label>
                </div>
                {[
                    ['show_photos', 'Show profile photos'],
                    ['show_roles', 'Show roles'],
                    ['show_bios', 'Show biographies'],
                    ['show_email', 'Show email addresses'],
                    ['show_phone', 'Show phone numbers'],
                    ['show_linkedin', 'Show LinkedIn links'],
                ].map(([key, label]) => (
                    <Toggle
                        key={key}
                        label={label}
                        checked={content[key] !== '0'}
                        onChange={(checked) => set(key, checked ? '1' : '0')}
                    />
                ))}
                {(content.show_email !== '0' || content.show_phone !== '0') && (
                    <p className="mt-2 text-[10px] leading-4 text-amber-700">
                        Only enable direct contact details when each team member has agreed to publish them.
                    </p>
                )}
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Featured label</legend>
                <Toggle
                    label="Show badge on featured members"
                    checked={content.show_featured_badge === '1'}
                    onChange={(checked) => set('show_featured_badge', checked ? '1' : '0')}
                />
                {content.show_featured_badge === '1' && (
                    <div className="mt-2">
                        <SlideField
                            label="Badge label"
                            value={textContent(content.featured_label)}
                            maxLength={40}
                            onChange={(value) => set('featured_label', value)}
                        />
                    </div>
                )}
            </fieldset>
        </div>
    );
}

function GallerySectionEditor({
    content,
    images,
    onChange,
}: {
    content: SectionContent;
    images: GalleryOption[];
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const selected = textContent(content.gallery_images).split('\n').filter(Boolean);
    const categories = [...new Set(images.map((item) => item.category).filter(Boolean))] as string[];
    const activeCount = images.filter((item) => item.is_active).length;
    const toggleImage = (image: string, checked: boolean) => {
        const next = checked ? [...selected, image] : selected.filter((item) => item !== image);
        set('gallery_images', [...new Set(next)].join('\n'));
    };

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Gallery source</legend>
                <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs leading-5 text-slate-600">
                    Add images with titles, accessible alt text, captions, categories, aspect ratios, and
                    display order in{' '}
                    <Link href="/dashboard/content/gallery" className="font-bold text-blue-700">
                        Content → Gallery
                    </Link>
                    . This section currently has {activeCount} active image{activeCount === 1 ? '' : 's'}.
                </p>
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Image selection
                        <select
                            value={textContent(content.gallery_selection_mode) || 'all'}
                            onChange={(event) => set('gallery_selection_mode', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="all">All active images</option>
                            <option value="selected">Selected images</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Category
                        <select
                            value={textContent(content.gallery_category)}
                            onChange={(event) => set('gallery_category', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All categories</option>
                            {categories.map((category) => (
                                <option key={category} value={category}>
                                    {category}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>
                {content.gallery_selection_mode === 'selected' && (
                    <div className="mt-3 max-h-64 space-y-1 overflow-y-auto rounded-lg border p-2">
                        {images.length ? (
                            images.map((item, index) => (
                                <label
                                    key={`${item.image}-${index}`}
                                    className="flex items-center gap-2 rounded-md p-2 text-xs hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(item.image)}
                                        onChange={(event) => toggleImage(item.image, event.target.checked)}
                                    />
                                    <img src={item.image} alt="" className="size-10 rounded object-cover" />
                                    <span className="min-w-0">
                                        <strong className="block truncate">{item.title}</strong>
                                        <span className="block text-[10px] text-slate-500">
                                            {item.category || 'Uncategorised'}
                                            {item.is_active ? '' : ' · Inactive'}
                                        </span>
                                    </span>
                                </label>
                            ))
                        ) : (
                            <p className="p-2 text-xs text-slate-500">
                                No gallery images have been added yet.
                            </p>
                        )}
                    </div>
                )}
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Maximum images
                        <select
                            value={textContent(content.gallery_limit) || '12'}
                            onChange={(event) => set('gallery_limit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {[1, 2, 3, 4, 6, 8, 9, 12, 16, 20, 24, 32, 40, 48].map((limit) => (
                                <option key={limit} value={limit}>
                                    {limit}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Sort images
                        <select
                            value={textContent(content.gallery_sort) || 'display-order'}
                            onChange={(event) => set('gallery_sort', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="display-order">Display order</option>
                            <option value="title">Title A–Z</option>
                            <option value="category">Category A–Z</option>
                        </select>
                    </label>
                </div>
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Layout and image details</legend>
                <div className="mb-2 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.gallery_columns) || '3'}
                            onChange={(event) => set('gallery_columns', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2 columns</option>
                            <option value="3">3 columns</option>
                            <option value="4">4 columns</option>
                            <option value="5">5 columns</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Spacing
                        <select
                            value={textContent(content.gallery_gap) || 'normal'}
                            onChange={(event) => set('gallery_gap', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="compact">Compact</option>
                            <option value="normal">Normal</option>
                            <option value="spacious">Spacious</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Image fit
                        <select
                            value={textContent(content.image_fit) || 'cover'}
                            onChange={(event) => set('image_fit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="cover">Crop to fill</option>
                            <option value="contain">Show full image</option>
                        </select>
                    </label>
                </div>
                {[
                    ['show_titles', 'Show image titles'],
                    ['show_captions', 'Show captions'],
                    ['show_categories', 'Show categories'],
                    ['respect_aspect_ratios', 'Use each image’s aspect ratio'],
                ].map(([key, label]) => (
                    <Toggle
                        key={key}
                        label={label}
                        checked={content[key] !== '0'}
                        onChange={(checked) => set(key, checked ? '1' : '0')}
                    />
                ))}
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Image lightbox</legend>
                <Toggle
                    label="Open images in a full-size lightbox"
                    checked={content.enable_lightbox !== '0'}
                    onChange={(checked) => set('enable_lightbox', checked ? '1' : '0')}
                />
                {content.enable_lightbox !== '0' && (
                    <Toggle
                        label="Show title and caption in lightbox"
                        checked={content.lightbox_captions !== '0'}
                        onChange={(checked) => set('lightbox_captions', checked ? '1' : '0')}
                    />
                )}
            </fieldset>
        </div>
    );
}

function PortfolioSectionEditor({
    content,
    projects,
    onChange,
}: {
    content: SectionContent;
    projects: PortfolioOption[];
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const selected = textContent(content.portfolio_slugs).split(',').filter(Boolean);
    const categories = [...new Set(projects.map((project) => project.category).filter(Boolean))] as string[];
    const activeCount = projects.filter((project) => project.is_active).length;
    const toggleProject = (slug: string, checked: boolean) => {
        const next = checked ? [...selected, slug] : selected.filter((item) => item !== slug);
        set('portfolio_slugs', [...new Set(next)].join(','));
    };

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Portfolio source</legend>
                <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs leading-5 text-slate-600">
                    Add project titles, categories, clients, dates, locations, images, descriptions, case
                    studies, links, and featured status in{' '}
                    <Link href="/dashboard/content/portfolio" className="font-bold text-blue-700">
                        Content → Portfolio
                    </Link>
                    . This section currently has {activeCount} active project{activeCount === 1 ? '' : 's'}.
                </p>
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Project selection
                        <select
                            value={textContent(content.portfolio_selection_mode) || 'all'}
                            onChange={(event) => set('portfolio_selection_mode', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="all">All active projects</option>
                            <option value="selected">Selected projects</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Category
                        <select
                            value={textContent(content.portfolio_category)}
                            onChange={(event) => set('portfolio_category', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="">All categories</option>
                            {categories.map((category) => (
                                <option key={category} value={category}>
                                    {category}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>
                {content.portfolio_selection_mode === 'selected' && (
                    <div className="mt-3 max-h-52 space-y-1 overflow-y-auto rounded-lg border p-2">
                        {projects.length ? (
                            projects.map((project) => (
                                <label
                                    key={project.slug}
                                    className="flex items-start gap-2 rounded-md p-2 text-xs hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(project.slug)}
                                        onChange={(event) =>
                                            toggleProject(project.slug, event.target.checked)
                                        }
                                        className="mt-0.5"
                                    />
                                    <span>
                                        <strong>{project.title}</strong>
                                        <span className="block text-[10px] text-slate-500">
                                            {project.category || 'Uncategorised'}
                                            {project.is_active ? '' : ' · Inactive'}
                                        </span>
                                    </span>
                                </label>
                            ))
                        ) : (
                            <p className="p-2 text-xs text-slate-500">
                                No portfolio projects have been added yet.
                            </p>
                        )}
                    </div>
                )}
                <div className="mt-3">
                    <Toggle
                        label="Only featured projects"
                        checked={content.portfolio_featured_only === '1'}
                        onChange={(checked) => set('portfolio_featured_only', checked ? '1' : '0')}
                    />
                </div>
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Maximum projects
                        <select
                            value={textContent(content.portfolio_limit) || '6'}
                            onChange={(event) => set('portfolio_limit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {[1, 2, 3, 4, 6, 8, 9, 12, 16, 20, 24].map((limit) => (
                                <option key={limit} value={limit}>
                                    {limit}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Sort projects
                        <select
                            value={textContent(content.portfolio_sort) || 'display-order'}
                            onChange={(event) => set('portfolio_sort', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="display-order">Display order</option>
                            <option value="featured-first">Featured first</option>
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                            <option value="title">Title A–Z</option>
                        </select>
                    </label>
                </div>
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Layout and project details</legend>
                <div className="mb-2 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.portfolio_columns) || '3'}
                            onChange={(event) => set('portfolio_columns', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2 columns</option>
                            <option value="3">3 columns</option>
                            <option value="4">4 columns</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Image fit
                        <select
                            value={textContent(content.image_fit) || 'cover'}
                            onChange={(event) => set('image_fit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="cover">Crop to fill</option>
                            <option value="contain">Show full image</option>
                        </select>
                    </label>
                </div>
                {[
                    ['show_images', 'Show project images'],
                    ['show_descriptions', 'Show descriptions'],
                    ['show_categories', 'Show categories'],
                    ['show_clients', 'Show clients'],
                    ['show_dates', 'Show project dates'],
                    ['show_locations', 'Show locations'],
                ].map(([key, label]) => (
                    <Toggle
                        key={key}
                        label={label}
                        checked={content[key] !== '0'}
                        onChange={(checked) => set(key, checked ? '1' : '0')}
                    />
                ))}
            </fieldset>

            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Project links</legend>
                <Toggle
                    label="Show project button"
                    checked={content.show_button !== '0'}
                    onChange={(checked) => set('show_button', checked ? '1' : '0')}
                />
                {content.show_button !== '0' && (
                    <div className="mt-2">
                        <SlideField
                            label="Button label"
                            value={textContent(content.button_label)}
                            maxLength={80}
                            onChange={(value) => set('button_label', value)}
                        />
                    </div>
                )}
                <Toggle
                    label="Open external project links in a new tab"
                    checked={content.open_new_tab === '1'}
                    onChange={(checked) => set('open_new_tab', checked ? '1' : '0')}
                />
            </fieldset>
        </div>
    );
}

function TestimonialsSectionEditor({
    content,
    testimonials,
    onChange,
}: {
    content: SectionContent;
    testimonials: TestimonialOption[];
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const activeCount = testimonials.filter((item) => item.is_active).length;
    const featuredCount = testimonials.filter((item) => item.is_active && item.is_featured).length;

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Testimonial source</legend>
                <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs leading-5 text-slate-600">
                    Add client names, quotes, photos, roles, companies, ratings, and featured status in{' '}
                    <Link href="/dashboard/content/testimonials" className="font-bold text-blue-700">
                        Content → Testimonials
                    </Link>
                    . This section currently has {activeCount} active testimonial
                    {activeCount === 1 ? '' : 's'}
                    {featuredCount > 0 ? `, including ${featuredCount} featured` : ''}.
                </p>
                <div className="mt-3">
                    <Toggle
                        label="Only featured testimonials"
                        checked={content.testimonial_featured_only === '1'}
                        onChange={(checked) => set('testimonial_featured_only', checked ? '1' : '0')}
                    />
                </div>
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Maximum testimonials
                        <select
                            value={textContent(content.testimonial_limit) || '6'}
                            onChange={(event) => set('testimonial_limit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {[1, 2, 3, 4, 6, 8, 10, 12, 16, 20].map((limit) => (
                                <option key={limit} value={limit}>
                                    {limit}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Sort testimonials
                        <select
                            value={textContent(content.testimonial_sort) || 'display-order'}
                            onChange={(event) => set('testimonial_sort', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="display-order">Display order</option>
                            <option value="featured-first">Featured first</option>
                            <option value="rating-high">Highest rating</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Testimonial details</legend>
                <Toggle
                    label="Show client photos"
                    checked={content.show_photos !== '0'}
                    onChange={(checked) => set('show_photos', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show client roles"
                    checked={content.show_roles !== '0'}
                    onChange={(checked) => set('show_roles', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show company names"
                    checked={content.show_companies !== '0'}
                    onChange={(checked) => set('show_companies', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show star ratings"
                    checked={content.show_ratings !== '0'}
                    onChange={(checked) => set('show_ratings', checked ? '1' : '0')}
                />
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Carousel behaviour</legend>
                <p className="mb-2 text-[11px] leading-4 text-slate-500">
                    Select the <strong>Carousel</strong> variant below to activate these controls.
                </p>
                <Toggle
                    label="Autoplay carousel"
                    checked={content.autoplay !== '0'}
                    onChange={(checked) => set('autoplay', checked ? '1' : '0')}
                />
                <Toggle
                    label="Pause on hover or focus"
                    checked={content.pause_on_hover !== '0'}
                    onChange={(checked) => set('pause_on_hover', checked ? '1' : '0')}
                />
                <Toggle
                    label="Navigation arrows"
                    checked={content.show_arrows !== '0'}
                    onChange={(checked) => set('show_arrows', checked ? '1' : '0')}
                />
                <Toggle
                    label="Navigation dots"
                    checked={content.show_dots !== '0'}
                    onChange={(checked) => set('show_dots', checked ? '1' : '0')}
                />
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Autoplay interval
                        <select
                            value={textContent(content.interval) || '6'}
                            onChange={(event) => set('interval', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {[3, 4, 5, 6, 8, 10, 12, 15].map((seconds) => (
                                <option key={seconds} value={seconds}>
                                    {seconds} seconds
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Testimonials per view
                        <select
                            value={textContent(content.slides_per_view) || '1'}
                            onChange={(event) => set('slides_per_view', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="1">1 testimonial</option>
                            <option value="2">2 testimonials</option>
                            <option value="3">3 testimonials</option>
                        </select>
                    </label>
                </div>
            </fieldset>
        </div>
    );
}

function ProductsSectionEditor({
    content,
    products,
    onChange,
}: {
    content: SectionContent;
    products: ProductOption[];
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const selected = textContent(content.product_slugs).split(',').filter(Boolean);
    const toggleProduct = (slug: string, checked: boolean) => {
        const next = checked ? [...selected, slug] : selected.filter((item) => item !== slug);
        set('product_slugs', [...new Set(next)].join(','));
    };

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Catalogue products</legend>
                <p className="mb-3 text-[11px] leading-4 text-slate-500">
                    Product details, prices, images, and availability are managed in{' '}
                    <Link href="/dashboard/content/products" className="font-bold text-blue-700">
                        Content → Products
                    </Link>
                    .
                </p>
                <label className="block text-[11px] font-semibold text-slate-600">
                    Products to display
                    <select
                        value={textContent(content.selection_mode) || 'all'}
                        onChange={(event) => set('selection_mode', event.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        <option value="all">All active products</option>
                        <option value="selected">Only selected products</option>
                    </select>
                </label>
                {content.selection_mode === 'selected' && (
                    <div className="mt-3 max-h-56 space-y-1 overflow-y-auto rounded-lg border bg-slate-50 p-2">
                        {products.length === 0 ? (
                            <p className="p-2 text-xs text-slate-500">No products have been created yet.</p>
                        ) : (
                            products.map((product) => (
                                <label
                                    key={product.slug}
                                    className="flex items-center gap-2 rounded px-2 py-1.5 text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(product.slug)}
                                        disabled={!product.is_active}
                                        onChange={(event) =>
                                            toggleProduct(product.slug, event.target.checked)
                                        }
                                        className="size-4 accent-slate-950"
                                    />
                                    <span className="min-w-0 flex-1 truncate">{product.name}</span>
                                    {!product.is_active && (
                                        <span className="text-[9px] font-bold text-amber-700">Inactive</span>
                                    )}
                                    {product.is_featured && (
                                        <span className="text-[9px] font-bold text-blue-700">Featured</span>
                                    )}
                                </label>
                            ))
                        )}
                    </div>
                )}
                <div className="mt-3">
                    <Toggle
                        label="Only featured products"
                        checked={content.featured_only === '1'}
                        onChange={(checked) => set('featured_only', checked ? '1' : '0')}
                    />
                </div>
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Maximum products
                        <select
                            value={textContent(content.limit) || '6'}
                            onChange={(event) => set('limit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {[3, 4, 6, 8, 9, 12, 16, 20, 24].map((limit) => (
                                <option key={limit} value={limit}>
                                    {limit}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Sort products
                        <select
                            value={textContent(content.sort) || 'display-order'}
                            onChange={(event) => set('sort', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="display-order">Display order</option>
                            <option value="featured-first">Featured first</option>
                            <option value="name">Name A–Z</option>
                            <option value="price-low">Price low–high</option>
                            <option value="price-high">Price high–low</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Card presentation</legend>
                <label className="mb-2 block text-[11px] font-semibold text-slate-600">
                    Desktop columns
                    <select
                        value={textContent(content.columns) || '3'}
                        onChange={(event) => set('columns', event.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        <option value="2">2 columns</option>
                        <option value="3">3 columns</option>
                        <option value="4">4 columns</option>
                    </select>
                </label>
                <Toggle
                    label="Show images"
                    checked={content.show_images !== '0'}
                    onChange={(checked) => set('show_images', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show descriptions"
                    checked={content.show_descriptions !== '0'}
                    onChange={(checked) => set('show_descriptions', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show available prices"
                    checked={content.show_prices !== '0'}
                    onChange={(checked) => set('show_prices', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show featured badge"
                    checked={content.show_featured_badge !== '0'}
                    onChange={(checked) => set('show_featured_badge', checked ? '1' : '0')}
                />
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <SlideField
                        label="Price prefix"
                        value={textContent(content.price_prefix)}
                        maxLength={8}
                        onChange={(value) => set('price_prefix', value)}
                    />
                    <SlideField
                        label="Featured label"
                        value={textContent(content.featured_label)}
                        maxLength={40}
                        onChange={(value) => set('featured_label', value)}
                    />
                </div>
                <label className="mt-3 block text-[11px] font-semibold text-slate-600">
                    Product image fit
                    <select
                        value={textContent(content.image_fit) || 'cover'}
                        onChange={(event) => set('image_fit', event.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        <option value="cover">Fill image frame</option>
                        <option value="contain">Show complete image</option>
                    </select>
                </label>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Enquiry button</legend>
                <Toggle
                    label="Show enquiry buttons"
                    checked={content.show_button !== '0'}
                    onChange={(checked) => set('show_button', checked ? '1' : '0')}
                />
                {content.show_button !== '0' && (
                    <div className="mt-3 space-y-3">
                        <SlideField
                            label="Button label override (optional)"
                            value={textContent(content.button_label)}
                            maxLength={80}
                            onChange={(value) => set('button_label', value)}
                        />
                        <SlideField
                            label="Button destination"
                            value={textContent(content.button_url) || '#contact'}
                            maxLength={2048}
                            onChange={(value) => set('button_url', value)}
                        />
                    </div>
                )}
            </fieldset>
        </div>
    );
}

function BrandCarouselSectionEditor({
    content,
    mediaAssets,
    onChange,
    mode = 'carousel',
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
    mode?: 'carousel' | 'cloud';
}) {
    const isCloud = mode === 'cloud';
    const logos = brandLogos(content);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const updateLogos = (next: BrandLogo[]) => onChange({ ...content, logos: next });
    const updateLogo = (index: number, changes: Partial<BrandLogo>) =>
        updateLogos(
            logos.map((logo, position) =>
                position === index ? ({ ...logo, ...changes } as BrandLogo) : logo,
            ),
        );
    const addLogo = () => {
        const asset =
            mediaAssets.find((item) => !logos.some((logo) => logo.image_id === item.public_id)) ??
            mediaAssets[0];
        if (!asset || logos.length >= 20) return;
        updateLogos([
            ...logos,
            {
                image_id: asset.public_id,
                name: asset.title,
                alt: asset.alt_text ?? asset.title,
                url: '',
            },
        ]);
    };
    const chooseLogo = (index: number, publicId: string) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        updateLogo(index, {
            image_id: publicId,
            ...(!logos[index].name && asset ? { name: asset.title } : {}),
            ...(!logos[index].alt && asset ? { alt: asset.alt_text ?? asset.title } : {}),
        });
    };
    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= logos.length) return;
        updateLogos(arrayMove(logos, index, target));
    };

    return (
        <div className="space-y-4">
            {isCloud && (
                <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                    Upload client, partner, or certification logo images in the{' '}
                    <Link href="/dashboard/media" className="font-bold text-blue-700">
                        Media library
                    </Link>
                    , then add and order them below. Give each logo a descriptive name and alt text; links are
                    optional. Choose Grid, Band, or Default under Variant, adjust the display options, then
                    publish to update the live site. Logo Cloud is a static layout; use Brand Carousel for a
                    moving row.
                </div>
            )}
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-xs font-bold text-slate-700">
                        {isCloud ? 'Logo cloud images' : 'Brand and partner logos'}
                    </p>
                    <p className="text-[10px] text-slate-500">Add and order up to 20 logos</p>
                </div>
                <button
                    type="button"
                    onClick={addLogo}
                    disabled={logos.length >= 20 || mediaAssets.length === 0}
                    className="rounded-lg border px-2 py-1.5 text-[11px] font-semibold disabled:opacity-40"
                >
                    <Plus className="mr-1 inline size-3" /> Add logo
                </button>
            </div>
            {mediaAssets.length === 0 && (
                <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                    Upload logo images in{' '}
                    <Link href="/dashboard/media" className="font-bold text-blue-700">
                        Media library
                    </Link>
                    , then return here.
                </p>
            )}
            {logos.map((logo, index) => {
                const selected = mediaAssets.find((asset) => asset.public_id === logo.image_id);
                return (
                    <fieldset key={index} className="rounded-xl border bg-slate-50 p-3">
                        <legend className="px-1 text-xs font-bold">Logo {index + 1}</legend>
                        <div className="mb-3 flex items-center gap-2">
                            {selected && (
                                <img
                                    src={selected.thumbnail_url}
                                    alt=""
                                    className="h-12 w-20 rounded border bg-white object-contain p-1"
                                />
                            )}
                            <span className="min-w-0 flex-1 truncate text-xs font-semibold">
                                {logo.name || selected?.title || `Logo ${index + 1}`}
                            </span>
                            <button
                                type="button"
                                disabled={index === 0}
                                onClick={() => move(index, -1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move logo ${index + 1} up`}
                            >
                                <ArrowUp className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={index === logos.length - 1}
                                onClick={() => move(index, 1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move logo ${index + 1} down`}
                            >
                                <ArrowDown className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                onClick={() => updateLogos(logos.filter((_, position) => position !== index))}
                                className="rounded p-1 text-red-600"
                                aria-label={`Remove logo ${index + 1}`}
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </div>
                        <div className="space-y-3">
                            <SlideSelect
                                label="Logo image"
                                value={logo.image_id}
                                assets={mediaAssets}
                                onChange={(value) => chooseLogo(index, value)}
                            />
                            <SlideField
                                label={
                                    isCloud
                                        ? 'Client, partner, or certification name'
                                        : 'Brand or partner name'
                                }
                                value={logo.name}
                                maxLength={100}
                                onChange={(value) => updateLogo(index, { name: value })}
                            />
                            <SlideField
                                label="Image alt text"
                                value={logo.alt}
                                maxLength={255}
                                onChange={(value) => updateLogo(index, { alt: value })}
                            />
                            <SlideField
                                label="Destination URL (optional)"
                                value={logo.url}
                                maxLength={2048}
                                onChange={(value) => updateLogo(index, { url: value })}
                            />
                        </div>
                    </fieldset>
                );
            })}
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Display options</legend>
                <Toggle
                    label="Show brand names"
                    checked={content.show_names === '1'}
                    onChange={(checked) => set('show_names', checked ? '1' : '0')}
                />
                <Toggle
                    label="Display logos in grayscale"
                    checked={content.grayscale === '1'}
                    onChange={(checked) => set('grayscale', checked ? '1' : '0')}
                />
                <Toggle
                    label="Open links in a new tab"
                    checked={content.open_new_tab === '1'}
                    onChange={(checked) => set('open_new_tab', checked ? '1' : '0')}
                />
                {isCloud && (
                    <div className="mt-3 space-y-3">
                        <Toggle
                            label="Show cards behind logos"
                            checked={content.show_cards === '1'}
                            onChange={(checked) => set('show_cards', checked ? '1' : '0')}
                        />
                        <div className="grid grid-cols-2 gap-2">
                            <label className="block text-[11px] font-semibold text-slate-600">
                                Grid columns (Grid variant)
                                <select
                                    value={textContent(content.logo_columns) || '4'}
                                    onChange={(event) => set('logo_columns', event.target.value)}
                                    className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                                >
                                    {[2, 3, 4, 5, 6].map((count) => (
                                        <option key={count} value={count}>
                                            {count}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="block text-[11px] font-semibold text-slate-600">
                                Logo size
                                <select
                                    value={textContent(content.logo_size) || 'standard'}
                                    onChange={(event) => set('logo_size', event.target.value)}
                                    className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                                >
                                    <option value="compact">Compact</option>
                                    <option value="standard">Standard</option>
                                    <option value="large">Large</option>
                                </select>
                            </label>
                        </div>
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Space between logos
                            <select
                                value={textContent(content.logo_spacing) || 'standard'}
                                onChange={(event) => set('logo_spacing', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="compact">Compact</option>
                                <option value="standard">Standard</option>
                                <option value="loose">Loose</option>
                            </select>
                        </label>
                    </div>
                )}
            </fieldset>
            {!isCloud && (
                <fieldset className="rounded-xl border p-3">
                    <legend className="px-1 text-xs font-bold">Carousel behaviour</legend>
                    <Toggle
                        label="Autoplay carousel"
                        checked={content.autoplay !== '0'}
                        onChange={(checked) => set('autoplay', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Pause on hover or focus"
                        checked={content.pause_on_hover !== '0'}
                        onChange={(checked) => set('pause_on_hover', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Navigation arrows"
                        checked={content.show_arrows !== '0'}
                        onChange={(checked) => set('show_arrows', checked ? '1' : '0')}
                    />
                    <label className="mt-2 block text-[11px] font-semibold text-slate-600">
                        Autoplay interval
                        <select
                            value={textContent(content.interval) || '4'}
                            onChange={(event) => set('interval', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {[3, 4, 5, 6, 8, 10, 12, 15].map((seconds) => (
                                <option key={seconds} value={seconds}>
                                    {seconds} seconds
                                </option>
                            ))}
                        </select>
                        <span className="mt-1 block font-normal text-slate-400">
                            These controls apply when the Carousel variant is selected.
                        </span>
                    </label>
                </fieldset>
            )}
        </div>
    );
}

function NewsletterSectionEditor({
    content,
    onChange,
}: {
    content: SectionContent;
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                This is a working signup form. Subscribers are saved securely for this website and can be
                reviewed, activated, or exported in{' '}
                <Link href="/dashboard/contact" className="font-bold text-blue-700">
                    Contact &amp; enquiries → Newsletter
                </Link>
                .
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Description"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Signup fields</legend>
                <div className="space-y-3">
                    <Toggle
                        label="Ask for the subscriber's name"
                        checked={content.show_name_field === '1'}
                        onChange={(checked) => set('show_name_field', checked ? '1' : '0')}
                    />
                    <div className="grid grid-cols-2 gap-2">
                        <SlideField
                            label="Name label"
                            value={textContent(content.name_label)}
                            maxLength={60}
                            onChange={(value) => set('name_label', value)}
                        />
                        <SlideField
                            label="Name placeholder"
                            value={textContent(content.name_placeholder)}
                            maxLength={120}
                            onChange={(value) => set('name_placeholder', value)}
                        />
                        <SlideField
                            label="Email label"
                            value={textContent(content.email_field_label)}
                            maxLength={60}
                            onChange={(value) => set('email_field_label', value)}
                        />
                        <SlideField
                            label="Email placeholder"
                            value={textContent(content.email_placeholder)}
                            maxLength={120}
                            onChange={(value) => set('email_placeholder', value)}
                        />
                    </div>
                    <SlideField
                        label="Subscribe button"
                        value={textContent(content.button_label)}
                        maxLength={60}
                        onChange={(value) => set('button_label', value)}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Form introduction</legend>
                <div className="grid gap-2 sm:grid-cols-2">
                    <Toggle
                        label="Show form heading"
                        checked={content.show_form_heading === '1'}
                        onChange={(checked) => set('show_form_heading', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Show form description"
                        checked={content.show_form_description === '1'}
                        onChange={(checked) => set('show_form_description', checked ? '1' : '0')}
                    />
                </div>
                <div className="mt-3 space-y-3">
                    <SlideField
                        label="Form heading"
                        value={textContent(content.form_heading)}
                        maxLength={120}
                        onChange={(value) => set('form_heading', value)}
                    />
                    <SlideField
                        label="Form description"
                        value={textContent(content.form_description)}
                        maxLength={1000}
                        multiline
                        onChange={(value) => set('form_description', value)}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Consent and confirmation</legend>
                <div className="space-y-3">
                    <SlideField
                        label="Required consent text"
                        value={textContent(content.consent_label)}
                        maxLength={500}
                        multiline
                        onChange={(value) => set('consent_label', value)}
                    />
                    <div className="grid grid-cols-2 gap-2">
                        <SlideField
                            label="Privacy-link label"
                            value={textContent(content.privacy_label)}
                            maxLength={60}
                            onChange={(value) => set('privacy_label', value)}
                        />
                        <SlideField
                            label="Privacy-policy URL"
                            value={textContent(content.privacy_url)}
                            maxLength={2048}
                            onChange={(value) => set('privacy_url', value)}
                        />
                    </div>
                    <Toggle
                        label="Open privacy link in a new tab"
                        checked={content.privacy_new_tab === '1'}
                        onChange={(checked) => set('privacy_new_tab', checked ? '1' : '0')}
                    />
                    <SlideField
                        label="Success message"
                        value={textContent(content.success_message)}
                        maxLength={500}
                        multiline
                        onChange={(value) => set('success_message', value)}
                    />
                    <Toggle
                        label="Show privacy note"
                        checked={content.show_privacy_note !== '0'}
                        onChange={(checked) => set('show_privacy_note', checked ? '1' : '0')}
                    />
                    <SlideField
                        label="Privacy note"
                        value={textContent(content.privacy_note)}
                        maxLength={500}
                        multiline
                        onChange={(value) => set('privacy_note', value)}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Form layout</legend>
                <div className="grid grid-cols-2 gap-2">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Width
                        <select
                            value={textContent(content.form_width) || 'medium'}
                            onChange={(event) => set('form_width', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="compact">Compact</option>
                            <option value="medium">Medium</option>
                            <option value="wide">Wide</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Alignment
                        <select
                            value={textContent(content.form_alignment) || 'left'}
                            onChange={(event) => set('form_alignment', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="left">Left</option>
                            <option value="center">Centre</option>
                        </select>
                    </label>
                </div>
            </fieldset>
        </div>
    );
}

function MapSectionEditor({
    content,
    onChange,
}: {
    content: SectionContent;
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                This section uses the secure Google Maps embed URL, address, and working hours configured in{' '}
                <Link href="/dashboard/contact" className="font-bold text-blue-700">
                    Contact &amp; enquiries
                </Link>
                . Add the map there first, then manage its page copy, layout, directions, and visibility here.
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Location details</legend>
                <div className="space-y-3">
                    <SlideField
                        label="Location heading"
                        value={textContent(content.location_heading)}
                        maxLength={120}
                        onChange={(value) => set('location_heading', value)}
                    />
                    <div className="grid grid-cols-2 gap-2">
                        <SlideField
                            label="Address label"
                            value={textContent(content.address_label)}
                            maxLength={60}
                            onChange={(value) => set('address_label', value)}
                        />
                        <SlideField
                            label="Working-hours label"
                            value={textContent(content.hours_label)}
                            maxLength={60}
                            onChange={(value) => set('hours_label', value)}
                        />
                    </div>
                    <SlideField
                        label="Location note"
                        value={textContent(content.location_note)}
                        maxLength={500}
                        multiline
                        onChange={(value) => set('location_note', value)}
                    />
                    <div className="grid gap-2 sm:grid-cols-2">
                        <Toggle
                            label="Location details"
                            checked={content.show_location_details !== '0'}
                            onChange={(checked) => set('show_location_details', checked ? '1' : '0')}
                        />
                        <Toggle
                            label="Address"
                            checked={content.show_address !== '0'}
                            onChange={(checked) => set('show_address', checked ? '1' : '0')}
                        />
                        <Toggle
                            label="Working hours"
                            checked={content.show_working_hours !== '0'}
                            onChange={(checked) => set('show_working_hours', checked ? '1' : '0')}
                        />
                    </div>
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Map presentation</legend>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Toggle
                        label="Show embedded map"
                        checked={content.show_map !== '0'}
                        onChange={(checked) => set('show_map', checked ? '1' : '0')}
                    />
                    <label className="text-[11px] font-semibold text-slate-600">
                        Map position
                        <select
                            value={textContent(content.map_position) || 'right'}
                            onChange={(event) => set('map_position', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Map height
                        <select
                            value={textContent(content.map_height) || 'medium'}
                            onChange={(event) => set('map_height', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="small">Small</option>
                            <option value="medium">Medium</option>
                            <option value="large">Large</option>
                        </select>
                    </label>
                    <SlideField
                        label="Map accessibility title"
                        value={textContent(content.map_title)}
                        maxLength={160}
                        onChange={(value) => set('map_title', value)}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Location actions</legend>
                <div className="grid gap-2 sm:grid-cols-2">
                    <Toggle
                        label="Directions button"
                        checked={content.show_directions_button !== '0'}
                        onChange={(checked) => set('show_directions_button', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Contact button"
                        checked={content.show_contact_button === '1'}
                        onChange={(checked) => set('show_contact_button', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Open actions in new tab"
                        checked={content.open_new_tab !== '0'}
                        onChange={(checked) => set('open_new_tab', checked ? '1' : '0')}
                    />
                </div>
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <SlideField
                        label="Directions label"
                        value={textContent(content.directions_label)}
                        maxLength={60}
                        onChange={(value) => set('directions_label', value)}
                    />
                    <SlideField
                        label="Directions URL (optional)"
                        value={textContent(content.directions_url)}
                        maxLength={2048}
                        onChange={(value) => set('directions_url', value)}
                    />
                    <SlideField
                        label="Contact label"
                        value={textContent(content.contact_label)}
                        maxLength={60}
                        onChange={(value) => set('contact_label', value)}
                    />
                    <SlideField
                        label="Contact URL"
                        value={textContent(content.contact_url)}
                        maxLength={2048}
                        onChange={(value) => set('contact_url', value)}
                    />
                </div>
                <p className="mt-2 text-[10px] leading-4 text-slate-500">
                    Leave the directions URL empty to generate Google Maps directions from your configured
                    address.
                </p>
            </fieldset>
        </div>
    );
}

function ContactSectionEditor({
    content,
    onChange,
}: {
    content: SectionContent;
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Manage this section&apos;s layout, labels, placeholders, and visible contact details here. Add
                your address, phone numbers, public email addresses, working hours, map, form fields, and
                enquiry notifications in{' '}
                <Link href="/dashboard/contact" className="font-bold text-blue-700">
                    Contact &amp; enquiries
                </Link>
                .
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Contact details</legend>
                <SlideField
                    label="Details heading"
                    value={textContent(content.details_heading)}
                    maxLength={120}
                    onChange={(value) => set('details_heading', value)}
                />
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <SlideField
                        label="Address label"
                        value={textContent(content.address_label)}
                        maxLength={60}
                        onChange={(value) => set('address_label', value)}
                    />
                    <SlideField
                        label="Phone label"
                        value={textContent(content.phone_label)}
                        maxLength={60}
                        onChange={(value) => set('phone_label', value)}
                    />
                    <SlideField
                        label="Email label"
                        value={textContent(content.email_label)}
                        maxLength={60}
                        onChange={(value) => set('email_label', value)}
                    />
                    <SlideField
                        label="Hours label"
                        value={textContent(content.hours_label)}
                        maxLength={60}
                        onChange={(value) => set('hours_label', value)}
                    />
                </div>
                <div className="mt-3 grid gap-2 sm:grid-cols-2">
                    <Toggle
                        label="Details heading"
                        checked={content.show_details_heading !== '0'}
                        onChange={(checked) => set('show_details_heading', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Address"
                        checked={content.show_address !== '0'}
                        onChange={(checked) => set('show_address', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Phone numbers"
                        checked={content.show_phone_numbers !== '0'}
                        onChange={(checked) => set('show_phone_numbers', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Public email addresses"
                        checked={content.show_email_addresses !== '0'}
                        onChange={(checked) => set('show_email_addresses', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Working hours"
                        checked={content.show_working_hours !== '0'}
                        onChange={(checked) => set('show_working_hours', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Embedded map"
                        checked={content.show_map !== '0'}
                        onChange={(checked) => set('show_map', checked ? '1' : '0')}
                    />
                </div>
                <div className="mt-3 grid grid-cols-2 gap-2">
                    <SlideField
                        label="Map accessibility title"
                        value={textContent(content.map_title)}
                        maxLength={160}
                        onChange={(value) => set('map_title', value)}
                    />
                    <label className="text-[11px] font-semibold text-slate-600">
                        Map height
                        <select
                            value={textContent(content.map_height) || 'medium'}
                            onChange={(event) => set('map_height', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="small">Small</option>
                            <option value="medium">Medium</option>
                            <option value="large">Large</option>
                        </select>
                    </label>
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Enquiry form</legend>
                <div className="grid gap-2 sm:grid-cols-2">
                    <Toggle
                        label="Show enquiry form"
                        checked={content.show_form !== '0'}
                        onChange={(checked) => set('show_form', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Form heading"
                        checked={content.show_form_heading !== '0'}
                        onChange={(checked) => set('show_form_heading', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Form description"
                        checked={content.show_form_description !== '0'}
                        onChange={(checked) => set('show_form_description', checked ? '1' : '0')}
                    />
                    <label className="text-[11px] font-semibold text-slate-600">
                        Form position
                        <select
                            value={textContent(content.form_position) || 'right'}
                            onChange={(event) => set('form_position', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="left">Left</option>
                            <option value="right">Right</option>
                        </select>
                    </label>
                </div>
                <div className="mt-3 space-y-3">
                    <SlideField
                        label="Form heading"
                        value={textContent(content.form_heading)}
                        maxLength={120}
                        onChange={(value) => set('form_heading', value)}
                    />
                    <SlideField
                        label="Form description"
                        value={textContent(content.form_description)}
                        maxLength={1000}
                        multiline
                        onChange={(value) => set('form_description', value)}
                    />
                    <div className="grid grid-cols-2 gap-2">
                        <SlideField
                            label="Name label"
                            value={textContent(content.name_label)}
                            maxLength={60}
                            onChange={(value) => set('name_label', value)}
                        />
                        <SlideField
                            label="Name placeholder"
                            value={textContent(content.name_placeholder)}
                            maxLength={120}
                            onChange={(value) => set('name_placeholder', value)}
                        />
                        <SlideField
                            label="Email label"
                            value={textContent(content.email_field_label)}
                            maxLength={60}
                            onChange={(value) => set('email_field_label', value)}
                        />
                        <SlideField
                            label="Email placeholder"
                            value={textContent(content.email_placeholder)}
                            maxLength={120}
                            onChange={(value) => set('email_placeholder', value)}
                        />
                        <SlideField
                            label="Phone label"
                            value={textContent(content.phone_field_label)}
                            maxLength={60}
                            onChange={(value) => set('phone_field_label', value)}
                        />
                        <SlideField
                            label="Phone placeholder"
                            value={textContent(content.phone_placeholder)}
                            maxLength={120}
                            onChange={(value) => set('phone_placeholder', value)}
                        />
                        <SlideField
                            label="Subject label"
                            value={textContent(content.subject_label)}
                            maxLength={60}
                            onChange={(value) => set('subject_label', value)}
                        />
                        <SlideField
                            label="Subject placeholder"
                            value={textContent(content.subject_placeholder)}
                            maxLength={160}
                            onChange={(value) => set('subject_placeholder', value)}
                        />
                        <SlideField
                            label="Message label"
                            value={textContent(content.message_label)}
                            maxLength={60}
                            onChange={(value) => set('message_label', value)}
                        />
                        <SlideField
                            label="Message placeholder"
                            value={textContent(content.message_placeholder)}
                            maxLength={200}
                            onChange={(value) => set('message_placeholder', value)}
                        />
                    </div>
                    <SlideField
                        label="Submit button label"
                        value={textContent(content.submit_label)}
                        maxLength={60}
                        onChange={(value) => set('submit_label', value)}
                    />
                    <SlideField
                        label="Privacy or response note"
                        value={textContent(content.privacy_note)}
                        maxLength={500}
                        multiline
                        onChange={(value) => set('privacy_note', value)}
                    />
                </div>
            </fieldset>
        </div>
    );
}

function PricingSectionEditor({
    content,
    onChange,
}: {
    content: SectionContent;
    onChange: (content: SectionContent) => void;
}) {
    const items = pricingItems(content);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const updateItems = (next: PricingItem[]) => onChange({ ...content, pricing_items: next });
    const updateItem = (index: number, changes: Partial<PricingItem>) =>
        updateItems(
            items.map((item, position) =>
                position === index ? ({ ...item, ...changes } as PricingItem) : item,
            ),
        );
    const addItem = () => {
        if (items.length >= 8) return;
        updateItems([
            ...items,
            {
                name: `Plan ${items.length + 1}`,
                badge: '',
                description: '',
                currency: '₹',
                price: '999',
                original_price: '',
                period: '/ month',
                features: '+ Included feature\n- Unavailable feature',
                button_label: 'Get started',
                button_url: '/contact',
                is_featured: '0',
            },
        ]);
    };
    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= items.length) return;
        updateItems(arrayMove(items, index, target));
    };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Use this section to advertise your business&apos;s packages or service tiers. These cards are
                separate from the Ezyweb subscription plans in Admin → Plans. Enter one feature per line;
                prefix it with <strong>+</strong> for included or <strong>-</strong> for unavailable.
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-xs font-bold text-slate-700">Pricing plans</p>
                    <p className="text-[10px] text-slate-500">Add between 1 and 8 plans</p>
                </div>
                <button
                    type="button"
                    onClick={addItem}
                    disabled={items.length >= 8}
                    className="rounded-lg border px-2 py-1.5 text-[11px] font-semibold disabled:opacity-40"
                >
                    <Plus className="mr-1 inline size-3" /> Add plan
                </button>
            </div>
            {items.map((item, index) => (
                <fieldset key={index} className="rounded-xl border bg-slate-50 p-3">
                    <legend className="px-1 text-xs font-bold">Plan {index + 1}</legend>
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <Toggle
                            label="Highlight this plan"
                            checked={item.is_featured === '1'}
                            onChange={(checked) => updateItem(index, { is_featured: checked ? '1' : '0' })}
                        />
                        <div className="flex gap-1">
                            <button
                                type="button"
                                disabled={index === 0}
                                onClick={() => move(index, -1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move plan ${index + 1} up`}
                            >
                                <ArrowUp className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={index === items.length - 1}
                                onClick={() => move(index, 1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move plan ${index + 1} down`}
                            >
                                <ArrowDown className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={items.length === 1}
                                onClick={() => updateItems(items.filter((_, position) => position !== index))}
                                className="rounded p-1 text-red-600 disabled:opacity-25"
                                aria-label={`Remove plan ${index + 1}`}
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </div>
                    </div>
                    <div className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <SlideField
                                label="Plan name"
                                value={item.name}
                                maxLength={100}
                                onChange={(value) => updateItem(index, { name: value })}
                            />
                            <SlideField
                                label="Badge (optional)"
                                value={item.badge}
                                maxLength={40}
                                onChange={(value) => updateItem(index, { badge: value })}
                            />
                        </div>
                        <SlideField
                            label="Plan description"
                            value={item.description}
                            maxLength={500}
                            multiline
                            onChange={(value) => updateItem(index, { description: value })}
                        />
                        <div className="grid grid-cols-[4rem_1fr_1fr] gap-2">
                            <SlideField
                                label="Currency"
                                value={item.currency}
                                maxLength={10}
                                onChange={(value) => updateItem(index, { currency: value })}
                            />
                            <SlideField
                                label="Price"
                                value={item.price}
                                maxLength={30}
                                onChange={(value) => updateItem(index, { price: value })}
                            />
                            <SlideField
                                label="Original price"
                                value={item.original_price}
                                maxLength={30}
                                onChange={(value) => updateItem(index, { original_price: value })}
                            />
                        </div>
                        <SlideField
                            label="Billing period"
                            value={item.period}
                            maxLength={60}
                            onChange={(value) => updateItem(index, { period: value })}
                        />
                        <SlideField
                            label="Features (one per line)"
                            value={item.features}
                            maxLength={3000}
                            multiline
                            onChange={(value) => updateItem(index, { features: value })}
                        />
                        <p className="-mt-2 text-[10px] text-slate-500">
                            Example: “+ Unlimited projects” or “- Priority support”.
                        </p>
                        <div className="grid grid-cols-2 gap-2">
                            <SlideField
                                label="Button label"
                                value={item.button_label}
                                maxLength={60}
                                onChange={(value) => updateItem(index, { button_label: value })}
                            />
                            <SlideField
                                label="Button URL"
                                value={item.button_url}
                                maxLength={2048}
                                onChange={(value) => updateItem(index, { button_url: value })}
                            />
                        </div>
                    </div>
                </fieldset>
            ))}
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Pricing presentation</legend>
                <div className="grid grid-cols-2 gap-3">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.pricing_columns) || '3'}
                            onChange={(event) => set('pricing_columns', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2 columns</option>
                            <option value="3">3 columns</option>
                            <option value="4">4 columns</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Currency position
                        <select
                            value={textContent(content.currency_position) || 'before'}
                            onChange={(event) => set('currency_position', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="before">Before price</option>
                            <option value="after">After price</option>
                        </select>
                    </label>
                </div>
                <div className="mt-3 grid gap-2 sm:grid-cols-2">
                    <Toggle
                        label="Plan descriptions"
                        checked={content.show_plan_descriptions !== '0'}
                        onChange={(checked) => set('show_plan_descriptions', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Feature lists"
                        checked={content.show_plan_features !== '0'}
                        onChange={(checked) => set('show_plan_features', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Buttons"
                        checked={content.show_plan_buttons !== '0'}
                        onChange={(checked) => set('show_plan_buttons', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Badges"
                        checked={content.show_plan_badges !== '0'}
                        onChange={(checked) => set('show_plan_badges', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Original prices"
                        checked={content.show_original_prices === '1'}
                        onChange={(checked) => set('show_original_prices', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Open buttons in new tab"
                        checked={content.open_new_tab === '1'}
                        onChange={(checked) => set('open_new_tab', checked ? '1' : '0')}
                    />
                </div>
            </fieldset>
            <SlideField
                label="Pricing note"
                value={textContent(content.pricing_note)}
                maxLength={300}
                multiline
                onChange={(value) => set('pricing_note', value)}
            />
        </div>
    );
}

function ProcessSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const items = processItems(content);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const updateItems = (next: ProcessItem[]) => onChange({ ...content, process_items: next });
    const updateItem = (index: number, changes: Partial<ProcessItem>) =>
        updateItems(
            items.map((item, position) =>
                position === index ? ({ ...item, ...changes } as ProcessItem) : item,
            ),
        );
    const addItem = () => {
        if (items.length >= 12) return;
        const position = items.length + 1;
        updateItems([
            ...items,
            {
                number: String(position).padStart(2, '0'),
                icon: '✓',
                image_id: '',
                image_alt: '',
                title: `Step ${position}`,
                description: '',
                meta: '',
                link_label: '',
                link_url: '',
            },
        ]);
    };
    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= items.length) return;
        updateItems(arrayMove(items, index, target));
    };
    const chooseImage = (index: number, publicId: string) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        updateItem(index, {
            image_id: publicId,
            ...(asset && !items[index].image_alt ? { image_alt: asset.alt_text ?? asset.title } : {}),
        });
    };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Break the journey into clear steps and order them as visitors will experience them. Add an
                optional icon or artwork from the{' '}
                <Link href="/dashboard/media" className="font-bold text-blue-700">
                    Media library
                </Link>
                ; use the timing/note field for details such as “1–2 days” or “Free consultation”.
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-xs font-bold text-slate-700">Process steps</p>
                    <p className="text-[10px] text-slate-500">Add between 1 and 12 ordered steps</p>
                </div>
                <button
                    type="button"
                    onClick={addItem}
                    disabled={items.length >= 12}
                    className="rounded-lg border px-2 py-1.5 text-[11px] font-semibold disabled:opacity-40"
                >
                    <Plus className="mr-1 inline size-3" /> Add step
                </button>
            </div>
            {items.map((item, index) => {
                const selected = mediaAssets.find((asset) => asset.public_id === item.image_id);
                return (
                    <fieldset key={index} className="rounded-xl border bg-slate-50 p-3">
                        <legend className="px-1 text-xs font-bold">Step {index + 1}</legend>
                        <div className="mb-3 flex justify-end gap-1">
                            <button
                                type="button"
                                disabled={index === 0}
                                onClick={() => move(index, -1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move step ${index + 1} up`}
                            >
                                <ArrowUp className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={index === items.length - 1}
                                onClick={() => move(index, 1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move step ${index + 1} down`}
                            >
                                <ArrowDown className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={items.length === 1}
                                onClick={() => updateItems(items.filter((_, position) => position !== index))}
                                className="rounded p-1 text-red-600 disabled:opacity-25"
                                aria-label={`Remove step ${index + 1}`}
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </div>
                        <div className="space-y-3">
                            <div className="grid grid-cols-[5rem_1fr] gap-2">
                                <SlideField
                                    label="Number"
                                    value={item.number}
                                    maxLength={12}
                                    onChange={(value) => updateItem(index, { number: value })}
                                />
                                <SlideField
                                    label="Title"
                                    value={item.title}
                                    maxLength={120}
                                    onChange={(value) => updateItem(index, { title: value })}
                                />
                            </div>
                            <SlideField
                                label="Description"
                                value={item.description}
                                maxLength={800}
                                multiline
                                onChange={(value) => updateItem(index, { description: value })}
                            />
                            <div className="grid grid-cols-2 gap-2">
                                <SlideField
                                    label="Icon or emoji"
                                    value={item.icon}
                                    maxLength={12}
                                    onChange={(value) => updateItem(index, { icon: value })}
                                />
                                <SlideField
                                    label="Timing or note"
                                    value={item.meta}
                                    maxLength={80}
                                    onChange={(value) => updateItem(index, { meta: value })}
                                />
                            </div>
                            {selected && (
                                <img
                                    src={selected.thumbnail_url}
                                    alt=""
                                    className="aspect-video w-full rounded-lg object-cover"
                                />
                            )}
                            {mediaAssets.length > 0 ? (
                                <SlideSelect
                                    label="Step image (optional)"
                                    value={item.image_id}
                                    assets={mediaAssets}
                                    onChange={(value) => chooseImage(index, value)}
                                />
                            ) : (
                                <p className="rounded-lg border border-dashed bg-white p-2 text-[10px] text-slate-500">
                                    Upload images in Media library to add step artwork.
                                </p>
                            )}
                            {item.image_id && (
                                <>
                                    <SlideField
                                        label="Image alt text"
                                        value={item.image_alt}
                                        maxLength={255}
                                        onChange={(value) => updateItem(index, { image_alt: value })}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => updateItem(index, { image_id: '' })}
                                        className="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                                    >
                                        Remove image
                                    </button>
                                </>
                            )}
                            <div className="grid grid-cols-2 gap-2">
                                <SlideField
                                    label="Link label (optional)"
                                    value={item.link_label}
                                    maxLength={60}
                                    onChange={(value) => updateItem(index, { link_label: value })}
                                />
                                <SlideField
                                    label="Link URL"
                                    value={item.link_url}
                                    maxLength={2048}
                                    onChange={(value) => updateItem(index, { link_url: value })}
                                />
                            </div>
                        </div>
                    </fieldset>
                );
            })}
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Process presentation</legend>
                <div className="grid grid-cols-2 gap-3">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.process_columns) || '3'}
                            onChange={(event) => set('process_columns', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Step alignment
                        <select
                            value={textContent(content.process_alignment) || 'left'}
                            onChange={(event) => set('process_alignment', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Connector
                        <select
                            value={textContent(content.process_connector) || 'solid'}
                            onChange={(event) => set('process_connector', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="solid">Solid</option>
                            <option value="dashed">Dashed</option>
                            <option value="none">None</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Image fit
                        <select
                            value={textContent(content.image_fit) || 'cover'}
                            onChange={(event) => set('image_fit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="cover">Cover</option>
                            <option value="contain">Contain</option>
                        </select>
                    </label>
                </div>
                <div className="mt-3 grid grid-cols-2 gap-x-4">
                    <Toggle
                        label="Numbers"
                        checked={content.show_step_numbers !== '0'}
                        onChange={(checked) => set('show_step_numbers', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Icons"
                        checked={content.show_step_icons === '1'}
                        onChange={(checked) => set('show_step_icons', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Images"
                        checked={content.show_step_images !== '0'}
                        onChange={(checked) => set('show_step_images', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Descriptions"
                        checked={content.show_step_descriptions !== '0'}
                        onChange={(checked) => set('show_step_descriptions', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Timing / notes"
                        checked={content.show_step_meta === '1'}
                        onChange={(checked) => set('show_step_meta', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Links"
                        checked={content.show_step_links !== '0'}
                        onChange={(checked) => set('show_step_links', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Open links in new tab"
                        checked={content.open_new_tab === '1'}
                        onChange={(checked) => set('open_new_tab', checked ? '1' : '0')}
                    />
                </div>
            </fieldset>
        </div>
    );
}

function FeaturesSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const items = featureItems(content);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const updateItems = (next: FeatureItem[]) => onChange({ ...content, feature_items: next });
    const updateItem = (index: number, changes: Partial<FeatureItem>) =>
        updateItems(
            items.map((item, position) =>
                position === index ? ({ ...item, ...changes } as FeatureItem) : item,
            ),
        );
    const addItem = () => {
        if (items.length >= 12) return;
        updateItems([
            ...items,
            {
                icon: '✓',
                image_id: '',
                image_alt: '',
                title: `Feature ${items.length + 1}`,
                description: '',
                link_label: '',
                link_url: '',
                is_featured: '0',
            },
        ]);
    };
    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= items.length) return;
        updateItems(arrayMove(items, index, target));
    };
    const chooseImage = (index: number, publicId: string) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        updateItem(index, {
            image_id: publicId,
            ...(asset && !items[index].image_alt ? { image_alt: asset.alt_text ?? asset.title } : {}),
        });
    };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Add one card for each customer benefit or capability. Use a short symbol or emoji for simple
                icons, or select an image from the{' '}
                <Link href="/dashboard/media" className="font-bold text-blue-700">
                    Media library
                </Link>
                . Keep titles outcome-focused and descriptions concise.
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-xs font-bold text-slate-700">Feature cards</p>
                    <p className="text-[10px] text-slate-500">Add between 1 and 12 features</p>
                </div>
                <button
                    type="button"
                    onClick={addItem}
                    disabled={items.length >= 12}
                    className="rounded-lg border px-2 py-1.5 text-[11px] font-semibold disabled:opacity-40"
                >
                    <Plus className="mr-1 inline size-3" /> Add feature
                </button>
            </div>
            {items.map((item, index) => {
                const selected = mediaAssets.find((asset) => asset.public_id === item.image_id);
                return (
                    <fieldset key={index} className="rounded-xl border bg-slate-50 p-3">
                        <legend className="px-1 text-xs font-bold">Feature {index + 1}</legend>
                        <div className="mb-3 flex items-center justify-end gap-1">
                            <label className="mr-auto flex items-center gap-2 text-[11px] font-semibold text-slate-600">
                                <input
                                    type="checkbox"
                                    checked={item.is_featured === '1'}
                                    onChange={(event) =>
                                        updateItem(index, { is_featured: event.target.checked ? '1' : '0' })
                                    }
                                />{' '}
                                Highlight
                            </label>
                            <button
                                type="button"
                                disabled={index === 0}
                                onClick={() => move(index, -1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move feature ${index + 1} up`}
                            >
                                <ArrowUp className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={index === items.length - 1}
                                onClick={() => move(index, 1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move feature ${index + 1} down`}
                            >
                                <ArrowDown className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={items.length === 1}
                                onClick={() => updateItems(items.filter((_, position) => position !== index))}
                                className="rounded p-1 text-red-600 disabled:opacity-25"
                                aria-label={`Remove feature ${index + 1}`}
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </div>
                        <div className="space-y-3">
                            <SlideField
                                label="Title"
                                value={item.title}
                                maxLength={120}
                                onChange={(value) => updateItem(index, { title: value })}
                            />
                            <SlideField
                                label="Description"
                                value={item.description}
                                maxLength={600}
                                multiline
                                onChange={(value) => updateItem(index, { description: value })}
                            />
                            <SlideField
                                label="Icon or emoji"
                                value={item.icon}
                                maxLength={12}
                                onChange={(value) => updateItem(index, { icon: value })}
                            />
                            {selected && (
                                <img
                                    src={selected.thumbnail_url}
                                    alt=""
                                    className="aspect-video w-full rounded-lg object-cover"
                                />
                            )}
                            {mediaAssets.length > 0 ? (
                                <SlideSelect
                                    label="Feature image (overrides icon)"
                                    value={item.image_id}
                                    assets={mediaAssets}
                                    onChange={(value) => chooseImage(index, value)}
                                />
                            ) : (
                                <p className="rounded-lg border border-dashed bg-white p-2 text-[10px] text-slate-500">
                                    Upload images in Media library to use feature artwork.
                                </p>
                            )}
                            {item.image_id && (
                                <>
                                    <SlideField
                                        label="Image alt text"
                                        value={item.image_alt}
                                        maxLength={255}
                                        onChange={(value) => updateItem(index, { image_alt: value })}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => updateItem(index, { image_id: '' })}
                                        className="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                                    >
                                        Remove image
                                    </button>
                                </>
                            )}
                            <div className="grid grid-cols-2 gap-2">
                                <SlideField
                                    label="Link label (optional)"
                                    value={item.link_label}
                                    maxLength={60}
                                    onChange={(value) => updateItem(index, { link_label: value })}
                                />
                                <SlideField
                                    label="Link URL"
                                    value={item.link_url}
                                    maxLength={2048}
                                    onChange={(value) => updateItem(index, { link_url: value })}
                                />
                            </div>
                        </div>
                    </fieldset>
                );
            })}
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Feature presentation</legend>
                <div className="grid grid-cols-2 gap-3">
                    <label className="text-[11px] font-semibold text-slate-600">
                        Columns
                        <select
                            value={textContent(content.columns) || '3'}
                            onChange={(event) => set('columns', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Card alignment
                        <select
                            value={textContent(content.card_alignment) || 'left'}
                            onChange={(event) => set('card_alignment', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                        </select>
                    </label>
                    <label className="text-[11px] font-semibold text-slate-600">
                        Image fit
                        <select
                            value={textContent(content.image_fit) || 'cover'}
                            onChange={(event) => set('image_fit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="cover">Cover</option>
                            <option value="contain">Contain</option>
                        </select>
                    </label>
                    <SlideField
                        label="Highlight badge"
                        value={textContent(content.featured_label)}
                        maxLength={40}
                        onChange={(value) => set('featured_label', value)}
                    />
                </div>
                <div className="mt-3 grid grid-cols-2 gap-x-4">
                    <Toggle
                        label="Icons"
                        checked={content.show_icons !== '0'}
                        onChange={(checked) => set('show_icons', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Images"
                        checked={content.show_images !== '0'}
                        onChange={(checked) => set('show_images', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Descriptions"
                        checked={content.show_descriptions !== '0'}
                        onChange={(checked) => set('show_descriptions', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Links"
                        checked={content.show_links !== '0'}
                        onChange={(checked) => set('show_links', checked ? '1' : '0')}
                    />
                    <Toggle
                        label="Open links in new tab"
                        checked={content.open_new_tab === '1'}
                        onChange={(checked) => set('open_new_tab', checked ? '1' : '0')}
                    />
                </div>
            </fieldset>
        </div>
    );
}

function StatisticsSectionEditor({
    content,
    onChange,
}: {
    content: SectionContent;
    onChange: (content: SectionContent) => void;
}) {
    const stats = Array.isArray(content.stats) ? (content.stats as StatisticItem[]) : [];
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const updateStats = (next: StatisticItem[]) => onChange({ ...content, stats: next });
    const updateStat = (index: number, changes: Partial<StatisticItem>) =>
        updateStats(
            stats.map((item, position) =>
                position === index ? ({ ...item, ...changes } as StatisticItem) : item,
            ),
        );
    const addStat = () => {
        if (stats.length >= 8) return;
        updateStats([
            ...stats,
            {
                value: '0',
                prefix: '',
                suffix: '',
                label: `Statistic ${stats.length + 1}`,
                description: '',
                trend_text: '',
                trend_direction: 'neutral',
                source_label: '',
                source_url: '',
            },
        ]);
    };
    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= stats.length) return;
        updateStats(arrayMove(stats, index, target));
    };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Add your verified figures directly below; no separate content manager is needed. Enter a
                number with up to two decimal places, then add a label, optional comparison trend, and a
                source note or link. Use Variant for Default, Cards, or Split layout, and publish your draft
                to update the live site. Empty statistics never show fabricated numbers.
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-xs font-bold text-slate-700">Statistics</p>
                    <p className="text-[10px] text-slate-500">Add and order up to 8 metrics</p>
                </div>
                <button
                    type="button"
                    onClick={addStat}
                    disabled={stats.length >= 8}
                    className="rounded-lg border px-2 py-1.5 text-[11px] font-semibold disabled:opacity-40"
                >
                    <Plus className="mr-1 inline size-3" /> Add statistic
                </button>
            </div>
            {stats.length === 0 && (
                <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                    No statistics yet. Select “Add statistic” to enter your first verified result.
                </p>
            )}
            {stats.map((item, index) => (
                <fieldset key={index} className="rounded-xl border bg-slate-50 p-3">
                    <legend className="px-1 text-xs font-bold">Statistic {index + 1}</legend>
                    <div className="mb-3 flex justify-end gap-1">
                        <button
                            type="button"
                            disabled={index === 0}
                            onClick={() => move(index, -1)}
                            className="rounded p-1 disabled:opacity-25"
                            aria-label={`Move statistic ${index + 1} up`}
                        >
                            <ArrowUp className="size-3.5" />
                        </button>
                        <button
                            type="button"
                            disabled={index === stats.length - 1}
                            onClick={() => move(index, 1)}
                            className="rounded p-1 disabled:opacity-25"
                            aria-label={`Move statistic ${index + 1} down`}
                        >
                            <ArrowDown className="size-3.5" />
                        </button>
                        <button
                            type="button"
                            onClick={() => updateStats(stats.filter((_, position) => position !== index))}
                            className="rounded p-1 text-red-600"
                            aria-label={`Remove statistic ${index + 1}`}
                        >
                            <Trash2 className="size-3.5" />
                        </button>
                    </div>
                    <div className="grid grid-cols-[1fr_2fr_1fr] gap-2">
                        <SlideField
                            label="Prefix"
                            value={item.prefix}
                            maxLength={12}
                            onChange={(value) => updateStat(index, { prefix: value })}
                        />
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Number
                            <input
                                value={item.value}
                                inputMode="decimal"
                                maxLength={12}
                                onChange={(event) => updateStat(index, { value: event.target.value })}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            />
                        </label>
                        <SlideField
                            label="Suffix"
                            value={item.suffix}
                            maxLength={12}
                            onChange={(value) => updateStat(index, { suffix: value })}
                        />
                    </div>
                    <div className="mt-3 space-y-3">
                        <SlideField
                            label="Label"
                            value={item.label}
                            maxLength={100}
                            onChange={(value) => updateStat(index, { label: value })}
                        />
                        <SlideField
                            label="Description (optional)"
                            value={item.description}
                            maxLength={300}
                            multiline
                            onChange={(value) => updateStat(index, { description: value })}
                        />
                        <div className="grid grid-cols-2 gap-2">
                            <SlideField
                                label="Comparison or trend text"
                                value={item.trend_text}
                                maxLength={80}
                                onChange={(value) => updateStat(index, { trend_text: value })}
                            />
                            <label className="block text-[11px] font-semibold text-slate-600">
                                Trend direction
                                <select
                                    value={item.trend_direction || 'neutral'}
                                    onChange={(event) =>
                                        updateStat(index, { trend_direction: event.target.value })
                                    }
                                    className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                                >
                                    <option value="neutral">Neutral</option>
                                    <option value="up">Up</option>
                                    <option value="down">Down</option>
                                </select>
                            </label>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <SlideField
                                label="Source name"
                                value={item.source_label}
                                maxLength={120}
                                onChange={(value) => updateStat(index, { source_label: value })}
                            />
                            <SlideField
                                label="Source URL (optional)"
                                value={item.source_url}
                                maxLength={2048}
                                onChange={(value) => updateStat(index, { source_url: value })}
                            />
                        </div>
                    </div>
                </fieldset>
            ))}
            <fieldset className="space-y-2 rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Display and animation</legend>
                <label className="block text-[11px] font-semibold text-slate-600">
                    Columns (Default and Cards)
                    <select
                        value={textContent(content.stats_columns) || '4'}
                        onChange={(event) => set('stats_columns', event.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        <option value="2">2 columns</option>
                        <option value="3">3 columns</option>
                        <option value="4">4 columns</option>
                    </select>
                </label>
                <Toggle
                    label="Highlight first statistic"
                    checked={content.highlight_first === '1'}
                    onChange={(checked) => set('highlight_first', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show descriptions"
                    checked={content.show_descriptions !== '0'}
                    onChange={(checked) => set('show_descriptions', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show comparison trends"
                    checked={content.show_trends !== '0'}
                    onChange={(checked) => set('show_trends', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show source notes"
                    checked={content.show_sources !== '0'}
                    onChange={(checked) => set('show_sources', checked ? '1' : '0')}
                />
                <Toggle
                    label="Animate numbers when visible"
                    checked={content.animate !== '0'}
                    onChange={(checked) => set('animate', checked ? '1' : '0')}
                />
                <Toggle
                    label="Use thousands separators"
                    checked={content.use_grouping !== '0'}
                    onChange={(checked) => set('use_grouping', checked ? '1' : '0')}
                />
                <label className="block text-[11px] font-semibold text-slate-600">
                    Animation duration
                    <select
                        value={textContent(content.duration) || '2'}
                        onChange={(event) => set('duration', event.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        {[1, 2, 3, 4, 5].map((seconds) => (
                            <option key={seconds} value={seconds}>
                                {seconds} {seconds === 1 ? 'second' : 'seconds'}
                            </option>
                        ))}
                    </select>
                </label>
            </fieldset>
        </div>
    );
}

function CountersSectionEditor({
    content,
    onChange,
}: {
    content: SectionContent;
    onChange: (content: SectionContent) => void;
}) {
    const items = counterItems(content);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const updateItems = (next: CounterItem[]) => onChange({ ...content, items: next });
    const updateItem = (index: number, changes: Partial<CounterItem>) =>
        updateItems(
            items.map((item, position) =>
                position === index ? ({ ...item, ...changes } as CounterItem) : item,
            ),
        );
    const addItem = () => {
        if (items.length >= 8) return;
        updateItems([
            ...items,
            {
                value: '0',
                prefix: '',
                suffix: '+',
                label: `Counter ${items.length + 1}`,
                description: '',
            },
        ]);
    };
    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= items.length) return;
        updateItems(arrayMove(items, index, target));
    };

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={1000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-xs font-bold text-slate-700">Counters</p>
                    <p className="text-[10px] text-slate-500">Add between 1 and 8 measurable results</p>
                </div>
                <button
                    type="button"
                    onClick={addItem}
                    disabled={items.length >= 8}
                    className="rounded-lg border px-2 py-1.5 text-[11px] font-semibold disabled:opacity-40"
                >
                    <Plus className="mr-1 inline size-3" /> Add counter
                </button>
            </div>
            {items.map((item, index) => (
                <fieldset key={index} className="rounded-xl border bg-slate-50 p-3">
                    <legend className="px-1 text-xs font-bold">Counter {index + 1}</legend>
                    <div className="mb-3 flex justify-end gap-1">
                        <button
                            type="button"
                            disabled={index === 0}
                            onClick={() => move(index, -1)}
                            className="rounded p-1 disabled:opacity-25"
                            aria-label={`Move counter ${index + 1} up`}
                        >
                            <ArrowUp className="size-3.5" />
                        </button>
                        <button
                            type="button"
                            disabled={index === items.length - 1}
                            onClick={() => move(index, 1)}
                            className="rounded p-1 disabled:opacity-25"
                            aria-label={`Move counter ${index + 1} down`}
                        >
                            <ArrowDown className="size-3.5" />
                        </button>
                        <button
                            type="button"
                            disabled={items.length === 1}
                            onClick={() => updateItems(items.filter((_, position) => position !== index))}
                            className="rounded p-1 text-red-600 disabled:opacity-25"
                            aria-label={`Remove counter ${index + 1}`}
                        >
                            <Trash2 className="size-3.5" />
                        </button>
                    </div>
                    <div className="grid grid-cols-[1fr_2fr_1fr] gap-2">
                        <SlideField
                            label="Prefix"
                            value={item.prefix}
                            maxLength={12}
                            onChange={(value) => updateItem(index, { prefix: value })}
                        />
                        <SlideField
                            label="Number"
                            value={item.value}
                            maxLength={9}
                            type="number"
                            min={0}
                            max={999999999}
                            onChange={(value) => updateItem(index, { value })}
                        />
                        <SlideField
                            label="Suffix"
                            value={item.suffix}
                            maxLength={12}
                            onChange={(value) => updateItem(index, { suffix: value })}
                        />
                    </div>
                    <div className="mt-3 space-y-3">
                        <SlideField
                            label="Label"
                            value={item.label}
                            maxLength={100}
                            onChange={(value) => updateItem(index, { label: value })}
                        />
                        <SlideField
                            label="Description (optional)"
                            value={item.description}
                            maxLength={300}
                            multiline
                            onChange={(value) => updateItem(index, { description: value })}
                        />
                    </div>
                </fieldset>
            ))}
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Counter behaviour</legend>
                <Toggle
                    label="Animate numbers when visible"
                    checked={content.animate !== '0'}
                    onChange={(checked) => set('animate', checked ? '1' : '0')}
                />
                <Toggle
                    label="Use thousands separators"
                    checked={content.use_grouping !== '0'}
                    onChange={(checked) => set('use_grouping', checked ? '1' : '0')}
                />
                <label className="mt-2 block text-[11px] font-semibold text-slate-600">
                    Animation duration
                    <select
                        value={textContent(content.duration) || '2'}
                        onChange={(event) => set('duration', event.target.value)}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        {[1, 2, 3, 4, 5].map((seconds) => (
                            <option key={seconds} value={seconds}>
                                {seconds} {seconds === 1 ? 'second' : 'seconds'}
                            </option>
                        ))}
                    </select>
                </label>
            </fieldset>
        </div>
    );
}

function MissionVisionSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Section heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            {(['story', 'mission', 'vision'] as const).map((area) => (
                <PurposeContentEditor
                    key={area}
                    area={area}
                    content={content}
                    mediaAssets={mediaAssets}
                    onChange={onChange}
                />
            ))}
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Image presentation</legend>
                <div className="space-y-3">
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Story image position
                        <select
                            value={textContent(content.image_position) || 'left'}
                            onChange={(event) => set('image_position', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="left">Left of story</option>
                            <option value="right">Right of story</option>
                        </select>
                    </label>
                    <label className="block text-[11px] font-semibold text-slate-600">
                        Image fit
                        <select
                            value={textContent(content.image_fit) || 'cover'}
                            onChange={(event) => set('image_fit', event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            <option value="cover">Fill frame</option>
                            <option value="contain">Show complete image</option>
                        </select>
                    </label>
                    <p className="text-[11px] leading-4 text-slate-400">
                        Side-by-side and Cards variants control the overall arrangement. This position applies
                        to the Story layout in the Default variant.
                    </p>
                </div>
            </fieldset>
        </div>
    );
}

function PurposeContentEditor({
    area,
    content,
    mediaAssets,
    onChange,
}: {
    area: 'story' | 'mission' | 'vision';
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const label = area.charAt(0).toUpperCase() + area.slice(1);
    const imageKey = `${area}_image_id`;
    const mobileImageKey = `${area}_mobile_image_id`;
    const altKey = `${area}_image_alt`;
    const captionKey = `${area}_image_caption`;
    const imageId = textContent(content[imageKey]);
    const selected = mediaAssets.find((asset) => asset.public_id === imageId);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const chooseImage = (publicId: string, mobile = false) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        if (mobile) {
            set(mobileImageKey, publicId);
            return;
        }
        onChange({
            ...content,
            [imageKey]: publicId,
            [altKey]:
                publicId && !textContent(content[altKey])
                    ? (asset?.alt_text ?? asset?.title ?? '')
                    : textContent(content[altKey]),
        });
    };

    return (
        <fieldset className="rounded-xl border p-3">
            <legend className="px-1 text-xs font-bold">{label}</legend>
            <div className="space-y-3">
                <SlideField
                    label={`${label} heading`}
                    value={textContent(content[`${area}_heading`]) || `Our ${area}`}
                    maxLength={160}
                    onChange={(value) => set(`${area}_heading`, value)}
                />
                <label className="block text-xs font-semibold text-slate-600">
                    {label} text
                    <BuilderRichText
                        value={
                            textContent(content[`${area}_body`]) ||
                            (area === 'story' ? textContent(content.description) : '')
                        }
                        onChange={(value) => set(`${area}_body`, value)}
                    />
                </label>
                {selected ? (
                    <img
                        src={selected.thumbnail_url}
                        alt=""
                        className="aspect-[16/9] w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="grid aspect-[16/9] place-items-center rounded-lg bg-slate-100 text-slate-400">
                        <ImageIcon className="size-7" />
                    </div>
                )}
                {mediaAssets.length === 0 ? (
                    <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                        Upload images in{' '}
                        <Link href="/dashboard/media" className="font-bold text-blue-700">
                            Media library
                        </Link>
                        , then return here.
                    </p>
                ) : (
                    <div className="space-y-3">
                        <SlideSelect
                            label={`${label} desktop image (optional)`}
                            value={imageId}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(value)}
                        />
                        <SlideSelect
                            label={`${label} mobile image (optional)`}
                            value={textContent(content[mobileImageKey])}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(value, true)}
                        />
                    </div>
                )}
                {imageId && (
                    <div className="space-y-3">
                        <SlideField
                            label="Image alt text"
                            value={textContent(content[altKey])}
                            maxLength={255}
                            onChange={(value) => set(altKey, value)}
                        />
                        <SlideField
                            label="Image caption (optional)"
                            value={textContent(content[captionKey])}
                            maxLength={500}
                            onChange={(value) => set(captionKey, value)}
                        />
                        <button
                            type="button"
                            onClick={() => onChange({ ...content, [imageKey]: '', [mobileImageKey]: '' })}
                            className="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                        >
                            Remove {area} images
                        </button>
                    </div>
                )}
            </div>
        </fieldset>
    );
}

function CtaSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const imageId = textContent(content.image_id);
    const selected = mediaAssets.find((asset) => asset.public_id === imageId);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const chooseImage = (publicId: string, mobile = false) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        if (mobile) {
            set('mobile_image_id', publicId);
            return;
        }
        onChange({
            ...content,
            image_id: publicId,
            image_alt:
                publicId && !textContent(content.image_alt)
                    ? (asset?.alt_text ?? asset?.title ?? '')
                    : textContent(content.image_alt),
        });
    };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Use this section for one focused next step—an enquiry, booking, purchase, signup, or phone
                call. Add optional responsive artwork from the{' '}
                <Link href="/dashboard/media" className="font-bold text-blue-700">
                    Media library
                </Link>
                .
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Description"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Calls to action</legend>
                <div className="space-y-3">
                    <SlideField
                        label="Primary button label"
                        value={textContent(content.primary_label)}
                        maxLength={60}
                        onChange={(value) => set('primary_label', value)}
                    />
                    <SlideField
                        label="Primary button URL"
                        value={textContent(content.primary_url)}
                        maxLength={2048}
                        onChange={(value) => set('primary_url', value)}
                    />
                    <Toggle
                        label="Open primary link in a new tab"
                        checked={content.primary_new_tab === '1'}
                        onChange={(checked) => set('primary_new_tab', checked ? '1' : '0')}
                    />
                    <SlideField
                        label="Secondary button label (optional)"
                        value={textContent(content.secondary_label)}
                        maxLength={60}
                        onChange={(value) => set('secondary_label', value)}
                    />
                    <SlideField
                        label="Secondary button URL"
                        value={textContent(content.secondary_url)}
                        maxLength={2048}
                        onChange={(value) => set('secondary_url', value)}
                    />
                    <Toggle
                        label="Open secondary link in a new tab"
                        checked={content.secondary_new_tab === '1'}
                        onChange={(checked) => set('secondary_new_tab', checked ? '1' : '0')}
                    />
                    <SlideField
                        label="Reassurance text (optional)"
                        value={textContent(content.supporting_text)}
                        maxLength={300}
                        multiline
                        onChange={(value) => set('supporting_text', value)}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Optional CTA image</legend>
                {selected ? (
                    <img
                        src={selected.thumbnail_url}
                        alt=""
                        className="mb-3 aspect-video w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="mb-3 grid aspect-video place-items-center rounded-lg bg-slate-100 text-slate-400">
                        <ImageIcon className="size-7" />
                    </div>
                )}
                {mediaAssets.length === 0 ? (
                    <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                        Upload an image in{' '}
                        <Link href="/dashboard/media" className="font-bold text-blue-700">
                            Media library
                        </Link>
                        , then return here.
                    </p>
                ) : (
                    <div className="space-y-3">
                        <SlideSelect
                            label="Desktop image"
                            value={imageId}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(value)}
                        />
                        <SlideSelect
                            label="Mobile image (optional)"
                            value={textContent(content.mobile_image_id)}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(value, true)}
                        />
                    </div>
                )}
                {imageId && (
                    <div className="mt-3 space-y-3">
                        <SlideField
                            label="Image alt text"
                            value={textContent(content.image_alt)}
                            maxLength={255}
                            onChange={(value) => set('image_alt', value)}
                        />
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image layout
                            <select
                                value={textContent(content.media_layout) || 'right'}
                                onChange={(event) => set('media_layout', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="right">Right of text</option>
                                <option value="left">Left of text</option>
                                <option value="background">Full background</option>
                            </select>
                        </label>
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image fit
                            <select
                                value={textContent(content.image_fit) || 'cover'}
                                onChange={(event) => set('image_fit', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="cover">Fill frame</option>
                                <option value="contain">Show complete image</option>
                            </select>
                        </label>
                        {content.media_layout === 'background' && (
                            <label className="block text-[11px] font-semibold text-slate-600">
                                Background darkness ({textContent(content.overlay_strength) || '55'}%)
                                <input
                                    type="range"
                                    min="20"
                                    max="85"
                                    step="5"
                                    value={textContent(content.overlay_strength) || '55'}
                                    onChange={(event) => set('overlay_strength', event.target.value)}
                                    className="mt-1 w-full"
                                />
                            </label>
                        )}
                        <button
                            type="button"
                            onClick={() => onChange({ ...content, image_id: '', mobile_image_id: '' })}
                            className="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                        >
                            Remove images
                        </button>
                    </div>
                )}
            </fieldset>
        </div>
    );
}

function AboutSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const imageId = textContent(content.image_id);
    const selected = mediaAssets.find((asset) => asset.public_id === imageId);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const chooseImage = (publicId: string, mobile = false) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        if (mobile) {
            set('mobile_image_id', publicId);
            return;
        }
        onChange({
            ...content,
            image_id: publicId,
            image_alt:
                publicId && !textContent(content.image_alt)
                    ? (asset?.alt_text ?? asset?.title ?? '')
                    : textContent(content.image_alt),
        });
    };

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <label className="block text-xs font-semibold text-slate-600">
                About story
                <BuilderRichText
                    value={textContent(content.body) || textContent(content.description)}
                    onChange={(value) => set('body', value)}
                />
            </label>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Optional button</legend>
                <div className="space-y-3">
                    <SlideField
                        label="Button label"
                        value={textContent(content.primary_label)}
                        maxLength={60}
                        onChange={(value) => set('primary_label', value)}
                    />
                    <SlideField
                        label="Button URL"
                        value={textContent(content.primary_url)}
                        maxLength={2048}
                        onChange={(value) => set('primary_url', value)}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Optional about image</legend>
                {selected ? (
                    <img
                        src={selected.thumbnail_url}
                        alt=""
                        className="mb-3 aspect-[4/3] w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="mb-3 grid aspect-[4/3] place-items-center rounded-lg bg-slate-100 text-slate-400">
                        <ImageIcon className="size-7" />
                    </div>
                )}
                {mediaAssets.length === 0 ? (
                    <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                        Upload images in{' '}
                        <Link href="/dashboard/media" className="font-bold text-blue-700">
                            Media library
                        </Link>
                        , then return here.
                    </p>
                ) : (
                    <div className="space-y-3">
                        <SlideSelect
                            label="Desktop image"
                            value={imageId}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(value)}
                        />
                        <SlideSelect
                            label="Mobile image (optional)"
                            value={textContent(content.mobile_image_id)}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(value, true)}
                        />
                    </div>
                )}
                {imageId && (
                    <div className="mt-3 space-y-3">
                        <SlideField
                            label="Image alt text"
                            value={textContent(content.image_alt)}
                            maxLength={255}
                            onChange={(value) => set('image_alt', value)}
                        />
                        <SlideField
                            label="Image caption (optional)"
                            value={textContent(content.image_caption)}
                            maxLength={500}
                            onChange={(value) => set('image_caption', value)}
                        />
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Default image position
                            <select
                                value={textContent(content.image_position) || 'right'}
                                onChange={(event) => set('image_position', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="right">Right of text</option>
                                <option value="left">Left of text</option>
                            </select>
                            <span className="mt-1 block font-normal text-slate-400">
                                Image Left, Image Right, and Centered variants override this setting.
                            </span>
                        </label>
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image fit
                            <select
                                value={textContent(content.image_fit) || 'cover'}
                                onChange={(event) => set('image_fit', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="cover">Fill frame</option>
                                <option value="contain">Show complete image</option>
                            </select>
                        </label>
                        <button
                            type="button"
                            onClick={() => onChange({ ...content, image_id: '', mobile_image_id: '' })}
                            className="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                        >
                            Remove images
                        </button>
                    </div>
                )}
            </fieldset>
        </div>
    );
}

function HeroSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const imageId = textContent(content.image_id);
    const selected = mediaAssets.find((asset) => asset.public_id === imageId);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const chooseImage = (publicId: string, mobile = false) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        if (mobile) {
            set('mobile_image_id', publicId);
            return;
        }
        onChange({
            ...content,
            image_id: publicId,
            image_alt:
                publicId && !textContent(content.image_alt)
                    ? (asset?.alt_text ?? asset?.title ?? '')
                    : textContent(content.image_alt),
        });
    };

    return (
        <div className="space-y-4">
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Headline"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Description"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Calls to action</legend>
                <div className="space-y-3">
                    <SlideField
                        label="Primary button label"
                        value={textContent(content.primary_label)}
                        maxLength={60}
                        onChange={(value) => set('primary_label', value)}
                    />
                    <SlideField
                        label="Primary button URL"
                        value={textContent(content.primary_url)}
                        maxLength={2048}
                        onChange={(value) => set('primary_url', value)}
                    />
                    <SlideField
                        label="Secondary button label"
                        value={textContent(content.secondary_label)}
                        maxLength={60}
                        onChange={(value) => set('secondary_label', value)}
                    />
                    <SlideField
                        label="Secondary button URL"
                        value={textContent(content.secondary_url)}
                        maxLength={2048}
                        onChange={(value) => set('secondary_url', value)}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Optional hero image</legend>
                {selected ? (
                    <img
                        src={selected.thumbnail_url}
                        alt=""
                        className="mb-3 aspect-video w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="mb-3 grid aspect-video place-items-center rounded-lg bg-slate-100 text-slate-400">
                        <ImageIcon className="size-7" />
                    </div>
                )}
                {mediaAssets.length === 0 ? (
                    <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                        Upload images in{' '}
                        <Link href="/dashboard/media" className="font-bold text-blue-700">
                            Media library
                        </Link>
                        , then return here.
                    </p>
                ) : (
                    <div className="space-y-3">
                        <SlideSelect
                            label="Desktop image"
                            value={imageId}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(value)}
                        />
                        <SlideSelect
                            label="Mobile image (optional)"
                            value={textContent(content.mobile_image_id)}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(value, true)}
                        />
                    </div>
                )}
                {imageId && (
                    <div className="mt-3 space-y-3">
                        <SlideField
                            label="Image alt text"
                            value={textContent(content.image_alt)}
                            maxLength={255}
                            onChange={(value) => set('image_alt', value)}
                        />
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image position
                            <select
                                value={textContent(content.image_position) || 'right'}
                                onChange={(event) => set('image_position', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="right">Right of text</option>
                                <option value="left">Left of text</option>
                            </select>
                        </label>
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image fit
                            <select
                                value={textContent(content.image_fit) || 'cover'}
                                onChange={(event) => set('image_fit', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="cover">Fill frame</option>
                                <option value="contain">Show complete image</option>
                            </select>
                        </label>
                        <button
                            type="button"
                            onClick={() => onChange({ ...content, image_id: '', mobile_image_id: '' })}
                            className="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                        >
                            Remove images
                        </button>
                    </div>
                )}
            </fieldset>
        </div>
    );
}

function SpacerDividerSectionEditor({
    content,
    variant,
    onChange,
}: {
    content: SectionContent;
    variant: string;
    onChange: (content: SectionContent) => void;
}) {
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const spaces = [0, 16, 32, 48, 64, 96, 128];
    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Use this section between two content sections. Default draws a simple rule, Line adds a
                decorative centre mark or optional label, and Space adds only whitespace. Choose the Variant
                below, then set space before and after here. The internal label helps you identify the section
                in Website Design and is not shown to visitors. Publish your draft to update the live site.
            </div>
            <SlideField
                label="Internal label (editor only)"
                value={textContent(content.heading)}
                maxLength={80}
                onChange={(value) => set('heading', value)}
            />
            <div className="grid grid-cols-2 gap-3">
                {(['space_before', 'space_after'] as const).map((key) => (
                    <label key={key} className="block text-[11px] font-semibold text-slate-600">
                        {key === 'space_before' ? 'Space before' : 'Space after'}
                        <select
                            value={textContent(content[key]) || '48'}
                            onChange={(event) => set(key, event.target.value)}
                            className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                        >
                            {spaces.map((value) => (
                                <option key={value} value={value}>
                                    {value === 0 ? 'None' : `${value} px`}
                                </option>
                            ))}
                        </select>
                    </label>
                ))}
            </div>
            {variant !== 'space' && (
                <fieldset className="space-y-3 rounded-xl border p-3">
                    <legend className="px-1 text-xs font-bold">Divider appearance</legend>
                    <SlideField
                        label="Visible centre label (optional)"
                        value={textContent(content.line_label)}
                        maxLength={80}
                        onChange={(value) => set('line_label', value)}
                    />
                    <div className="grid grid-cols-2 gap-3">
                        <Select
                            label="Line width"
                            value={textContent(content.line_width) || 'full'}
                            options={['narrow', 'medium', 'wide', 'full']}
                            onChange={(value) => set('line_width', value)}
                        />
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Line weight
                            <select
                                value={textContent(content.line_weight) || '1'}
                                onChange={(event) => set('line_weight', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                {[1, 2, 3, 4].map((value) => (
                                    <option key={value} value={value}>
                                        {value} px
                                    </option>
                                ))}
                            </select>
                        </label>
                        <Select
                            label="Line style"
                            value={textContent(content.line_style) || 'solid'}
                            options={['solid', 'dashed', 'dotted']}
                            onChange={(value) => set('line_style', value)}
                        />
                        <Select
                            label="Theme colour"
                            value={textContent(content.line_color) || 'border'}
                            options={['border', 'primary', 'accent']}
                            onChange={(value) => set('line_color', value)}
                        />
                    </div>
                </fieldset>
            )}
        </div>
    );
}

function VideoSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const posterId = textContent(content.poster_image_id);
    const selected = mediaAssets.find((asset) => asset.public_id === posterId);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const choosePoster = (publicId: string) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        onChange({
            ...content,
            poster_image_id: publicId,
            poster_alt:
                publicId && !textContent(content.poster_alt)
                    ? (asset?.alt_text ?? asset?.title ?? '')
                    : textContent(content.poster_alt),
        });
    };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Add a YouTube or Vimeo watch URL, or a public HTTPS .mp4, .webm, or .ogg URL on
                storage.googleapis.com. YouTube and Vimeo load only after a visitor clicks play; GCS files
                play in the page. For the poster, upload an image to the{' '}
                <Link href="/dashboard/media" className="font-bold text-blue-700">
                    Media library
                </Link>{' '}
                first. The media library currently uploads images only, so GCS video files need a public URL.
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <fieldset className="space-y-3 rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Video link</legend>
                <SlideField
                    label="Video URL"
                    value={textContent(content.video_url)}
                    maxLength={2048}
                    onChange={(value) => set('video_url', value)}
                />
                <SlideField
                    label="Accessible video title"
                    value={textContent(content.video_title)}
                    maxLength={160}
                    onChange={(value) => set('video_title', value)}
                />
                <SlideField
                    label="Caption"
                    value={textContent(content.video_caption)}
                    maxLength={500}
                    multiline
                    onChange={(value) => set('video_caption', value)}
                />
                <Toggle
                    label="Show caption"
                    checked={content.show_caption !== '0'}
                    onChange={(checked) => set('show_caption', checked ? '1' : '0')}
                />
                <label className="block text-xs font-semibold text-slate-600">
                    Poster shape
                    <select
                        value={textContent(content.aspect_ratio) || 'wide'}
                        onChange={(event) => set('aspect_ratio', event.target.value)}
                        className="mt-1.5 w-full rounded-lg border bg-white px-3 py-2 text-sm font-normal"
                    >
                        <option value="wide">Wide (16:9)</option>
                        <option value="standard">Standard (4:3)</option>
                        <option value="vertical">Vertical (9:16)</option>
                    </select>
                </label>
                <Toggle
                    label="Mute audio by default"
                    checked={content.muted === '1'}
                    onChange={(checked) => set('muted', checked ? '1' : '0')}
                />
                <Toggle
                    label="Loop video"
                    checked={content.loop === '1'}
                    onChange={(checked) => set('loop', checked ? '1' : '0')}
                />
                <Toggle
                    label="Show YouTube/Vimeo player controls"
                    checked={content.show_controls !== '0'}
                    onChange={(checked) => set('show_controls', checked ? '1' : '0')}
                />
                <Toggle
                    label="Autoplay direct GCS video files"
                    checked={content.autoplay === '1'}
                    onChange={(checked) => set('autoplay', checked ? '1' : '0')}
                />
                <p className="text-[11px] leading-5 text-slate-500">
                    External videos always wait for a click before loading, then attempt to play. Direct GCS
                    files always have controls. Autoplay applies only to GCS files and automatically mutes
                    them; browser settings may still prevent autoplay.
                </p>
            </fieldset>
            <fieldset className="space-y-3 rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Poster image</legend>
                {selected ? (
                    <img
                        src={selected.thumbnail_url}
                        alt=""
                        className="aspect-video w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="grid aspect-video place-items-center rounded-lg bg-slate-100 text-slate-400">
                        <ImageIcon className="size-7" />
                    </div>
                )}
                {mediaAssets.length ? (
                    <SlideSelect
                        label="Poster"
                        value={posterId}
                        assets={mediaAssets}
                        onChange={choosePoster}
                    />
                ) : (
                    <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                        No images yet. Upload one in the Media library.
                    </p>
                )}
                {posterId && (
                    <SlideField
                        label="Poster alt text"
                        value={textContent(content.poster_alt)}
                        maxLength={255}
                        onChange={(value) => set('poster_alt', value)}
                    />
                )}
            </fieldset>
            <fieldset className="space-y-3 rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Optional links</legend>
                <SlideField
                    label="Transcript URL"
                    value={textContent(content.transcript_url)}
                    maxLength={2048}
                    onChange={(value) => set('transcript_url', value)}
                />
                <SlideField
                    label="Transcript link text"
                    value={textContent(content.transcript_label)}
                    maxLength={60}
                    onChange={(value) => set('transcript_label', value)}
                />
                <SlideField
                    label="Button text"
                    value={textContent(content.primary_label)}
                    maxLength={60}
                    onChange={(value) => set('primary_label', value)}
                />
                <SlideField
                    label="Button URL"
                    value={textContent(content.primary_url)}
                    maxLength={2048}
                    onChange={(value) => set('primary_url', value)}
                />
                <Toggle
                    label="Open button in new tab"
                    checked={content.primary_new_tab === '1'}
                    onChange={(checked) => set('primary_new_tab', checked ? '1' : '0')}
                />
            </fieldset>
        </div>
    );
}

function ImageTextSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const imageId = textContent(content.image_id);
    const selected = mediaAssets.find((asset) => asset.public_id === imageId);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const chooseImage = (publicId: string) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        onChange({
            ...content,
            image_id: publicId,
            image_alt:
                publicId && !textContent(content.image_alt)
                    ? (asset?.alt_text ?? asset?.title ?? '')
                    : textContent(content.image_alt),
            mobile_image_id: publicId ? textContent(content.mobile_image_id) : '',
        });
    };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Pair your text with an image from the{' '}
                <Link href="/dashboard/media" className="font-bold text-blue-700">
                    Media library
                </Link>
                . Upload desktop and optional mobile images there, then select them below. Set descriptive alt
                text, choose the layout and appearance here, and publish your draft to update the live site.
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction"
                value={textContent(content.description)}
                maxLength={2000}
                multiline
                onChange={(value) => set('description', value)}
            />
            <div className="text-xs font-semibold text-slate-600">
                <p>Formatted text</p>
                <BuilderRichText value={textContent(content.body)} onChange={(value) => set('body', value)} />
            </div>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Optional links</legend>
                <div className="space-y-3">
                    <div className="grid grid-cols-2 gap-2">
                        <SlideField
                            label="Primary button text"
                            value={textContent(content.primary_label)}
                            maxLength={60}
                            onChange={(value) => set('primary_label', value)}
                        />
                        <SlideField
                            label="Primary button URL"
                            value={textContent(content.primary_url)}
                            maxLength={2048}
                            onChange={(value) => set('primary_url', value)}
                        />
                    </div>
                    <Toggle
                        label="Open primary link in new tab"
                        checked={content.primary_new_tab === '1'}
                        onChange={(checked) => set('primary_new_tab', checked ? '1' : '0')}
                    />
                    <div className="grid grid-cols-2 gap-2">
                        <SlideField
                            label="Secondary button text"
                            value={textContent(content.secondary_label)}
                            maxLength={60}
                            onChange={(value) => set('secondary_label', value)}
                        />
                        <SlideField
                            label="Secondary button URL"
                            value={textContent(content.secondary_url)}
                            maxLength={2048}
                            onChange={(value) => set('secondary_url', value)}
                        />
                    </div>
                    <Toggle
                        label="Open secondary link in new tab"
                        checked={content.secondary_new_tab === '1'}
                        onChange={(checked) => set('secondary_new_tab', checked ? '1' : '0')}
                    />
                    <SlideField
                        label="Supporting note"
                        value={textContent(content.supporting_text)}
                        maxLength={300}
                        multiline
                        onChange={(value) => set('supporting_text', value)}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Image</legend>
                {selected ? (
                    <img
                        src={selected.thumbnail_url}
                        alt=""
                        className="mb-3 aspect-[4/3] w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="mb-3 grid aspect-[4/3] place-items-center rounded-lg bg-slate-100 text-slate-400">
                        <ImageIcon className="size-7" />
                    </div>
                )}
                {mediaAssets.length ? (
                    <SlideSelect
                        label="Desktop image"
                        value={imageId}
                        assets={mediaAssets}
                        onChange={chooseImage}
                    />
                ) : (
                    <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                        No images yet. Upload one in the Media library.
                    </p>
                )}
                {imageId && (
                    <div className="mt-3 space-y-3">
                        <SlideSelect
                            label="Mobile image (optional)"
                            value={textContent(content.mobile_image_id)}
                            assets={mediaAssets}
                            onChange={(value) => set('mobile_image_id', value)}
                        />
                        <SlideField
                            label="Alt text"
                            value={textContent(content.image_alt)}
                            maxLength={255}
                            onChange={(value) => set('image_alt', value)}
                        />
                        <SlideField
                            label="Caption"
                            value={textContent(content.image_caption)}
                            maxLength={500}
                            onChange={(value) => set('image_caption', value)}
                        />
                        <Toggle
                            label="Show caption"
                            checked={content.show_image_caption !== '0'}
                            onChange={(checked) => set('show_image_caption', checked ? '1' : '0')}
                        />
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image position for Default layout
                            <select
                                value={textContent(content.image_position) || 'right'}
                                onChange={(event) => set('image_position', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="right">Right of text</option>
                                <option value="left">Left of text</option>
                            </select>
                        </label>
                        <div className="grid grid-cols-2 gap-2">
                            <label className="block text-[11px] font-semibold text-slate-600">
                                Image size
                                <select
                                    value={textContent(content.image_size) || 'balanced'}
                                    onChange={(event) => set('image_size', event.target.value)}
                                    className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                                >
                                    <option value="compact">Compact</option>
                                    <option value="balanced">Balanced</option>
                                    <option value="large">Large</option>
                                </select>
                            </label>
                            <label className="block text-[11px] font-semibold text-slate-600">
                                Image ratio
                                <select
                                    value={textContent(content.image_ratio) || 'landscape'}
                                    onChange={(event) => set('image_ratio', event.target.value)}
                                    className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                                >
                                    <option value="landscape">Landscape</option>
                                    <option value="square">Square</option>
                                    <option value="portrait">Portrait</option>
                                </select>
                            </label>
                        </div>
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image fit
                            <select
                                value={textContent(content.image_fit) || 'cover'}
                                onChange={(event) => set('image_fit', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="cover">Fill frame</option>
                                <option value="contain">Show full image</option>
                            </select>
                        </label>
                        <button
                            type="button"
                            onClick={() => onChange({ ...content, image_id: '', mobile_image_id: '' })}
                            className="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                        >
                            Remove images
                        </button>
                    </div>
                )}
            </fieldset>
        </div>
    );
}

function RichTextSectionEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const imageId = textContent(content.image_id);
    const selected = mediaAssets.find((asset) => asset.public_id === imageId);
    const set = (key: string, value: string) => onChange({ ...content, [key]: value });
    const chooseImage = (publicId: string) => {
        const asset = mediaAssets.find((item) => item.public_id === publicId);
        onChange({
            ...content,
            image_id: publicId,
            image_alt:
                publicId && !textContent(content.image_alt)
                    ? (asset?.alt_text ?? asset?.title ?? '')
                    : textContent(content.image_alt),
        });
    };
    const chooseMobileImage = (publicId: string) => set('mobile_image_id', publicId);

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs leading-5 text-blue-900">
                Add formatted paragraphs, headings, lists, quotes, and links below. For a supporting image,
                upload it to the{' '}
                <Link href="/dashboard/media" className="font-bold text-blue-700">
                    Media library
                </Link>
                , then select it here. Adjust the section variant and design controls for its width,
                background, spacing, and alignment. Changes appear on your live website after publishing.
            </div>
            <SlideField
                label="Eyebrow"
                value={textContent(content.eyebrow)}
                maxLength={80}
                onChange={(value) => set('eyebrow', value)}
            />
            <SlideField
                label="Heading"
                value={textContent(content.heading)}
                maxLength={160}
                onChange={(value) => set('heading', value)}
            />
            <SlideField
                label="Introduction (optional)"
                value={textContent(content.introduction)}
                maxLength={1000}
                multiline
                onChange={(value) => set('introduction', value)}
            />
            <div className="text-xs font-semibold text-slate-600">
                <p>Formatted content</p>
                <BuilderRichText
                    value={textContent(content.body) || textContent(content.description)}
                    onChange={(value) => set('body', value)}
                />
            </div>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Optional action link</legend>
                <div className="space-y-3">
                    <SlideField
                        label="Button text"
                        value={textContent(content.primary_label)}
                        maxLength={60}
                        onChange={(value) => set('primary_label', value)}
                    />
                    <SlideField
                        label="Button URL"
                        value={textContent(content.primary_url)}
                        maxLength={2048}
                        onChange={(value) => set('primary_url', value)}
                    />
                    <Toggle
                        label="Open button in new tab"
                        checked={content.primary_new_tab === '1'}
                        onChange={(checked) => set('primary_new_tab', checked ? '1' : '0')}
                    />
                </div>
            </fieldset>
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Supporting image</legend>
                {selected ? (
                    <img
                        src={selected.thumbnail_url}
                        alt=""
                        className="mb-3 aspect-[4/3] w-full rounded-lg object-cover"
                    />
                ) : (
                    <div className="mb-3 grid aspect-[4/3] place-items-center rounded-lg bg-slate-100 text-slate-400">
                        <ImageIcon className="size-7" />
                    </div>
                )}
                {mediaAssets.length === 0 ? (
                    <p className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                        Upload an image in{' '}
                        <Link href="/dashboard/media" className="font-bold text-blue-700">
                            Media library
                        </Link>
                        , then return here.
                    </p>
                ) : (
                    <SlideSelect
                        label="Desktop image"
                        value={imageId}
                        assets={mediaAssets}
                        onChange={chooseImage}
                    />
                )}
                {imageId && (
                    <div className="mt-3 space-y-3">
                        <SlideSelect
                            label="Mobile image (optional)"
                            value={textContent(content.mobile_image_id)}
                            assets={mediaAssets}
                            onChange={chooseMobileImage}
                        />
                        <SlideField
                            label="Alt text"
                            value={textContent(content.image_alt)}
                            maxLength={255}
                            onChange={(value) => set('image_alt', value)}
                        />
                        <SlideField
                            label="Caption (optional)"
                            value={textContent(content.image_caption)}
                            maxLength={500}
                            onChange={(value) => set('image_caption', value)}
                        />
                        <Toggle
                            label="Show image caption"
                            checked={content.show_image_caption !== '0'}
                            onChange={(checked) => set('show_image_caption', checked ? '1' : '0')}
                        />
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image position
                            <select
                                value={textContent(content.image_position) || 'right'}
                                onChange={(event) => set('image_position', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="right">Right of text</option>
                                <option value="left">Left of text</option>
                            </select>
                        </label>
                        <div className="grid grid-cols-2 gap-2">
                            <label className="block text-[11px] font-semibold text-slate-600">
                                Image size
                                <select
                                    value={textContent(content.image_size) || 'balanced'}
                                    onChange={(event) => set('image_size', event.target.value)}
                                    className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                                >
                                    <option value="compact">Compact</option>
                                    <option value="balanced">Balanced</option>
                                    <option value="large">Large</option>
                                </select>
                            </label>
                            <label className="block text-[11px] font-semibold text-slate-600">
                                Image ratio
                                <select
                                    value={textContent(content.image_ratio) || 'landscape'}
                                    onChange={(event) => set('image_ratio', event.target.value)}
                                    className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                                >
                                    <option value="landscape">Landscape</option>
                                    <option value="square">Square</option>
                                    <option value="portrait">Portrait</option>
                                </select>
                            </label>
                        </div>
                        <label className="block text-[11px] font-semibold text-slate-600">
                            Image fit
                            <select
                                value={textContent(content.image_fit) || 'cover'}
                                onChange={(event) => set('image_fit', event.target.value)}
                                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                            >
                                <option value="cover">Fill frame</option>
                                <option value="contain">Show full image</option>
                            </select>
                        </label>
                        <button
                            type="button"
                            onClick={() => onChange({ ...content, image_id: '', mobile_image_id: '' })}
                            className="w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                        >
                            Remove images
                        </button>
                    </div>
                )}
            </fieldset>
        </div>
    );
}

function BuilderRichText({ value, onChange }: { value: string; onChange: (value: string) => void }) {
    const editor = useRef<HTMLDivElement>(null);
    useEffect(() => {
        if (
            editor.current &&
            document.activeElement !== editor.current &&
            editor.current.innerHTML !== value
        ) {
            editor.current.innerHTML = value;
        }
    }, [value]);
    const command = (name: string, commandValue?: string) => {
        editor.current?.focus();
        document.execCommand(name, false, commandValue);
        onChange(editor.current?.innerHTML ?? '');
    };
    const addLink = () => {
        const url = window.prompt('Link URL (https://, /page, #section, mailto: or tel:)')?.trim();
        if (!url) return;
        if (!/^(?:https?:\/\/[^\s]+|\/(?!\/)[^\s]*|#[^\s]+|mailto:[^\s]+|tel:[^\s]+)$/i.test(url)) {
            toast.error('Enter a valid website, page, email, or telephone URL.');
            return;
        }
        command('createLink', url);
    };

    return (
        <div className="mt-1.5 overflow-hidden rounded-lg border bg-white">
            <div className="flex flex-wrap gap-1 border-b bg-slate-50 p-2" aria-label="Text formatting">
                {[
                    ['bold', 'Bold'],
                    ['italic', 'Italic'],
                    ['insertUnorderedList', 'Bullets'],
                    ['insertOrderedList', 'Numbers'],
                    ['formatBlock', 'Paragraph', 'p'],
                    ['formatBlock', 'Heading 2', 'h2'],
                    ['formatBlock', 'Heading 3', 'h3'],
                    ['formatBlock', 'Quote', 'blockquote'],
                ].map(([name, label, commandValue]) => (
                    <button
                        key={`${name}-${label}`}
                        type="button"
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => command(name, commandValue)}
                        className="rounded border bg-white px-2 py-1 text-[10px] font-semibold"
                    >
                        {label}
                    </button>
                ))}
                <button
                    type="button"
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={addLink}
                    className="rounded border bg-white px-2 py-1 text-[10px] font-semibold"
                >
                    Link
                </button>
                <button
                    type="button"
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => command('unlink')}
                    className="rounded border bg-white px-2 py-1 text-[10px] font-semibold"
                >
                    Unlink
                </button>
                <button
                    type="button"
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => command('insertHorizontalRule')}
                    className="rounded border bg-white px-2 py-1 text-[10px] font-semibold"
                >
                    Divider
                </button>
            </div>
            <div
                ref={editor}
                contentEditable
                suppressContentEditableWarning
                role="textbox"
                aria-multiline="true"
                onInput={(event) => onChange(event.currentTarget.innerHTML)}
                className="min-h-48 px-3 py-2 text-sm leading-6 font-normal outline-none"
            />
        </div>
    );
}

function HeroSliderEditor({
    content,
    mediaAssets,
    onChange,
}: {
    content: SectionContent;
    mediaAssets: MediaAssetOption[];
    onChange: (content: SectionContent) => void;
}) {
    const slides = heroSlides(content);
    const updateSlides = (next: HeroSlide[]) => onChange({ ...content, slides: next });
    const updateSlide = (index: number, changes: Partial<HeroSlide>) =>
        updateSlides(
            slides.map((slide, position) =>
                position === index ? ({ ...slide, ...changes } as HeroSlide) : slide,
            ),
        );
    const addSlide = () => {
        if (slides.length >= 10) return;
        updateSlides([
            ...slides,
            {
                image_id: '',
                mobile_image_id: '',
                alt: '',
                eyebrow: '',
                heading: `Slide ${slides.length + 1}`,
                description: '',
                primary_label: '',
                primary_url: '',
            },
        ]);
    };
    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;
        if (target < 0 || target >= slides.length) return;
        updateSlides(arrayMove(slides, index, target));
    };
    const chooseImage = (index: number, assetId: string, mobile = false) => {
        const asset = mediaAssets.find((item) => item.public_id === assetId);
        if (mobile) {
            updateSlide(index, { mobile_image_id: assetId });
            return;
        }
        updateSlide(index, {
            image_id: assetId,
            ...(asset && !slides[index].alt ? { alt: asset.alt_text ?? asset.title } : {}),
        });
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-xs font-bold text-slate-700">Slides</p>
                    <p className="text-[10px] text-slate-500">Up to 10 images · drag-free arrow ordering</p>
                </div>
                <button
                    type="button"
                    onClick={addSlide}
                    disabled={slides.length >= 10}
                    className="rounded-lg border px-2 py-1.5 text-[11px] font-semibold disabled:opacity-40"
                >
                    <Plus className="mr-1 inline size-3" /> Add slide
                </button>
            </div>
            {mediaAssets.length === 0 && (
                <div className="rounded-lg border border-dashed bg-slate-50 p-3 text-xs text-slate-600">
                    Upload images in{' '}
                    <Link href="/dashboard/media" className="font-bold text-blue-700">
                        Media library
                    </Link>
                    , then return here.
                </div>
            )}
            {slides.map((slide, index) => {
                const selected = mediaAssets.find((asset) => asset.public_id === slide.image_id);
                return (
                    <div key={`${index}-${slide.image_id}`} className="rounded-xl border bg-slate-50 p-3">
                        <div className="mb-3 flex items-center gap-2">
                            <span className="grid size-6 place-items-center rounded bg-slate-900 text-[10px] font-bold text-white">
                                {index + 1}
                            </span>
                            <span className="min-w-0 flex-1 truncate text-xs font-bold">
                                {slide.heading || `Slide ${index + 1}`}
                            </span>
                            <button
                                type="button"
                                disabled={index === 0}
                                onClick={() => move(index, -1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move slide ${index + 1} up`}
                            >
                                <ArrowUp className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={index === slides.length - 1}
                                onClick={() => move(index, 1)}
                                className="rounded p-1 disabled:opacity-25"
                                aria-label={`Move slide ${index + 1} down`}
                            >
                                <ArrowDown className="size-3.5" />
                            </button>
                            <button
                                type="button"
                                disabled={slides.length === 1}
                                onClick={() =>
                                    updateSlides(slides.filter((_, position) => position !== index))
                                }
                                className="rounded p-1 text-red-600 disabled:opacity-25"
                                aria-label={`Remove slide ${index + 1}`}
                            >
                                <Trash2 className="size-3.5" />
                            </button>
                        </div>
                        {selected ? (
                            <img
                                src={selected.thumbnail_url}
                                alt=""
                                className="mb-3 aspect-video w-full rounded-lg object-cover"
                            />
                        ) : (
                            <div className="mb-3 grid aspect-video place-items-center rounded-lg bg-slate-200 text-slate-500">
                                <ImageIcon className="size-6" />
                            </div>
                        )}
                        <SlideSelect
                            label="Desktop image"
                            value={slide.image_id}
                            assets={mediaAssets}
                            onChange={(value) => chooseImage(index, value)}
                        />
                        <div className="mt-3">
                            <SlideSelect
                                label="Mobile image (optional)"
                                value={slide.mobile_image_id}
                                assets={mediaAssets}
                                onChange={(value) => chooseImage(index, value, true)}
                            />
                        </div>
                        <div className="mt-3 space-y-3">
                            <SlideField
                                label="Alt text"
                                value={slide.alt}
                                maxLength={255}
                                onChange={(value) => updateSlide(index, { alt: value })}
                            />
                            <SlideField
                                label="Eyebrow"
                                value={slide.eyebrow}
                                maxLength={80}
                                onChange={(value) => updateSlide(index, { eyebrow: value })}
                            />
                            <SlideField
                                label="Heading"
                                value={slide.heading}
                                maxLength={160}
                                onChange={(value) => updateSlide(index, { heading: value })}
                            />
                            <SlideField
                                label="Description"
                                value={slide.description}
                                maxLength={1000}
                                multiline
                                onChange={(value) => updateSlide(index, { description: value })}
                            />
                            <SlideField
                                label="Button label"
                                value={slide.primary_label}
                                maxLength={60}
                                onChange={(value) => updateSlide(index, { primary_label: value })}
                            />
                            <SlideField
                                label="Button URL"
                                value={slide.primary_url}
                                maxLength={2048}
                                onChange={(value) => updateSlide(index, { primary_url: value })}
                            />
                        </div>
                    </div>
                );
            })}
            <fieldset className="rounded-xl border p-3">
                <legend className="px-1 text-xs font-bold">Slider behavior</legend>
                <Toggle
                    label="Autoplay"
                    checked={content.autoplay !== '0'}
                    onChange={(checked) => onChange({ ...content, autoplay: checked ? '1' : '0' })}
                />
                <Toggle
                    label="Navigation arrows"
                    checked={content.show_arrows !== '0'}
                    onChange={(checked) => onChange({ ...content, show_arrows: checked ? '1' : '0' })}
                />
                <Toggle
                    label="Navigation dots"
                    checked={content.show_dots !== '0'}
                    onChange={(checked) => onChange({ ...content, show_dots: checked ? '1' : '0' })}
                />
                <label className="mt-2 block text-[11px] font-semibold text-slate-600">
                    Autoplay interval
                    <select
                        value={textContent(content.interval) || '5'}
                        onChange={(event) => onChange({ ...content, interval: event.target.value })}
                        className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
                    >
                        {[3, 4, 5, 6, 8, 10, 12, 15].map((seconds) => (
                            <option key={seconds} value={seconds}>
                                {seconds} seconds
                            </option>
                        ))}
                    </select>
                </label>
            </fieldset>
        </div>
    );
}

function SlideSelect({
    label,
    value,
    assets,
    onChange,
}: {
    label: string;
    value: string;
    assets: MediaAssetOption[];
    onChange: (value: string) => void;
}) {
    return (
        <label className="block text-[11px] font-semibold text-slate-600">
            {label}
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal"
            >
                <option value="">Select from media library…</option>
                {assets.map((asset) => (
                    <option key={asset.public_id} value={asset.public_id}>
                        {asset.title}
                    </option>
                ))}
            </select>
        </label>
    );
}

function SlideField({
    label,
    value,
    maxLength,
    multiline = false,
    type = 'text',
    min,
    max,
    onChange,
}: {
    label: string;
    value: string;
    maxLength: number;
    multiline?: boolean;
    type?: 'text' | 'number';
    min?: number;
    max?: number;
    onChange: (value: string) => void;
}) {
    const classes = 'mt-1 w-full rounded-lg border bg-white px-2 py-2 text-xs font-normal';
    return (
        <label className="block text-[11px] font-semibold text-slate-600">
            {label}
            {multiline ? (
                <textarea
                    rows={3}
                    value={value}
                    maxLength={maxLength}
                    onChange={(event) => onChange(event.target.value)}
                    className={classes}
                />
            ) : (
                <input
                    type={type}
                    min={min}
                    max={max}
                    value={value}
                    maxLength={maxLength}
                    onChange={(event) => onChange(event.target.value)}
                    className={classes}
                />
            )}
        </label>
    );
}

function Toggle({
    label,
    checked,
    onChange,
}: {
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <label className="flex items-center justify-between py-1 text-[11px] font-semibold text-slate-600">
            {label}
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="size-4"
            />
        </label>
    );
}

function textContent(
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

function brandLogos(content: SectionContent): BrandLogo[] {
    return Array.isArray(content.logos) ? (content.logos as BrandLogo[]) : [];
}

function counterItems(content: SectionContent): CounterItem[] {
    if (Array.isArray(content.items)) return content.items as CounterItem[];

    return [
        { value: '0', prefix: '', suffix: '+', label: 'Clients served', description: '' },
        { value: '0', prefix: '', suffix: '+', label: 'Projects delivered', description: '' },
    ];
}

function featureItems(content: SectionContent): FeatureItem[] {
    if (Array.isArray(content.feature_items)) return content.feature_items as FeatureItem[];

    return [
        {
            icon: '✓',
            image_id: '',
            image_alt: '',
            title: 'Reliable by design',
            description: '',
            link_label: '',
            link_url: '',
            is_featured: '0',
        },
    ];
}

function processItems(content: SectionContent): ProcessItem[] {
    if (Array.isArray(content.process_items)) return content.process_items as ProcessItem[];

    return [
        {
            number: '01',
            icon: '✓',
            image_id: '',
            image_alt: '',
            title: 'First step',
            description: '',
            meta: '',
            link_label: '',
            link_url: '',
        },
    ];
}

function pricingItems(content: SectionContent): PricingItem[] {
    if (Array.isArray(content.pricing_items)) return content.pricing_items as PricingItem[];

    return [
        {
            name: 'Essential',
            badge: '',
            description: 'Everything you need to get started.',
            currency: '₹',
            price: '999',
            original_price: '',
            period: '/ month',
            features: '+ Core features\n+ Email support',
            button_label: 'Get started',
            button_url: '/contact',
            is_featured: '0',
        },
    ];
}

function heroSlides(content: SectionContent): HeroSlide[] {
    if (Array.isArray(content.slides) && content.slides.length > 0) return content.slides as HeroSlide[];

    return [
        {
            image_id: '',
            mobile_image_id: '',
            alt: '',
            eyebrow: textContent(content.eyebrow),
            heading: textContent(content.heading) || 'Hero slide',
            description: textContent(content.description),
            primary_label: textContent(content.primary_label),
            primary_url: textContent(content.primary_url),
        },
    ];
}

function PageEditor({ page, confirm }: { page: BuilderPage; confirm: ReturnType<typeof useConfirm> }) {
    const [title, setTitle] = useState(page.title);
    const [slug, setSlug] = useState(page.slug);
    const [navigationLabel, setNavigationLabel] = useState(page.navigation_label ?? '');
    const [enabled, setEnabled] = useState(page.is_enabled);
    return (
        <Panel title="Page settings" description="Select a section to edit its content">
            <div className="space-y-4">
                <TextField label="Page title" value={title} onChange={setTitle} />
                <TextField label="URL slug" value={slug} onChange={setSlug} />
                <TextField label="Navigation label" value={navigationLabel} onChange={setNavigationLabel} />
                <label className="flex items-center justify-between text-xs font-semibold text-slate-600">
                    Page enabled
                    <input
                        type="checkbox"
                        checked={enabled}
                        disabled={page.is_home}
                        onChange={(event) => setEnabled(event.target.checked)}
                        className="size-4"
                    />
                </label>
                <button
                    onClick={() =>
                        router.put(
                            `/dashboard/builder/pages/${page.id}`,
                            { title, slug, navigation_label: navigationLabel, is_enabled: enabled },
                            { preserveScroll: true },
                        )
                    }
                    className="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-950 px-3 py-2.5 text-sm font-semibold text-white"
                >
                    <Save className="size-4" /> Save page
                </button>
                {!page.is_home && (
                    <button
                        onClick={async () =>
                            (await confirm({
                                title: `Delete ${page.title}?`,
                                description: 'Its draft sections will also be removed.',
                                confirmLabel: 'Delete page',
                            })) && router.delete(`/dashboard/builder/pages/${page.id}`)
                        }
                        className="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600"
                    >
                        <Trash2 className="size-3.5" /> Delete page
                    </button>
                )}
            </div>
        </Panel>
    );
}

function Select({
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
        <label className="block text-xs font-semibold text-slate-600">
            {label}
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal capitalize"
            >
                {options.map((option) => (
                    <option key={option} value={option}>
                        {option.replaceAll('-', ' ')}
                    </option>
                ))}
            </select>
        </label>
    );
}

function TextField({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="block text-xs font-semibold text-slate-600">
            {label}
            <input
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm font-normal"
            />
        </label>
    );
}

function saveSection(section: BuilderSection, changes: Partial<BuilderSection>, onFinish: () => void) {
    router.put(
        `/dashboard/builder/sections/${section.id}`,
        {
            variant: changes.variant ?? section.variant,
            anchor: changes.anchor ?? section.anchor,
            content: changes.content ?? section.content,
            design_settings: changes.design_settings ?? section.design_settings,
            is_enabled: changes.is_enabled ?? section.is_enabled,
            show_on_desktop: changes.show_on_desktop ?? section.show_on_desktop,
            show_on_tablet: changes.show_on_tablet ?? section.show_on_tablet,
            show_on_mobile: changes.show_on_mobile ?? section.show_on_mobile,
        },
        {
            preserveScroll: true,
            onFinish,
            onError: () => toast.error('Draft could not be saved. Check the highlighted fields.'),
        },
    );
}

function visibilityLabel(section: BuilderSection) {
    if (!section.is_enabled) return 'Disabled';
    const hidden = [
        !section.show_on_desktop && 'desktop',
        !section.show_on_tablet && 'tablet',
        !section.show_on_mobile && 'mobile',
    ].filter(Boolean);
    return hidden.length ? `Hidden on ${hidden.join(', ')}` : 'All devices';
}
