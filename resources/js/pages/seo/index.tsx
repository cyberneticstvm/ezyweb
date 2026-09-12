import { Head, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    FileSearch,
    Globe2,
    Link2,
    Plus,
    SearchCheck,
    Trash2,
} from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { useConfirm } from '../../components/confirm-provider';
import { DashboardLayout } from '../../layouts/dashboard-layout';

interface SiteSettings {
    site_title: string;
    default_meta_description: string;
    default_social_image: string;
    business_name: string;
    business_type: 'Organization' | 'LocalBusiness';
    primary_language: string;
    search_engine_visibility: boolean;
    google_site_verification: string;
    google_analytics_id: string;
    social_profiles_text: string;
    not_found_title: string;
    not_found_message: string;
    not_found_button_label: string;
}
interface PageSeo {
    seo_title: string | null;
    meta_description: string | null;
    canonical_url: string | null;
    open_graph_title: string | null;
    open_graph_description: string | null;
    open_graph_image: string | null;
    is_indexed: boolean;
    is_followed: boolean;
    schema_type: 'WebPage' | 'AboutPage' | 'ContactPage';
}
interface SeoPage {
    id: number;
    title: string;
    slug: string;
    is_home: boolean;
    is_enabled: boolean;
    seo: PageSeo;
}
interface Redirect {
    public_id: string;
    source_path: string;
    target_path: string;
    status_code: 301 | 302;
    is_active: boolean;
    hit_count: number;
    last_hit_at?: string | null;
}
interface AuditIssue {
    severity: 'critical' | 'warning' | 'notice';
    label: string;
    detail: string;
}
interface Props {
    siteSettings: SiteSettings;
    pages: SeoPage[];
    redirects: Redirect[];
    audit: { score: number; issues: AuditIssue[] };
}
type Tab = 'overview' | 'site' | 'pages' | 'redirects';

export default function SeoIndex(props: Props) {
    const [tab, setTab] = useState<Tab>('overview');
    return (
        <DashboardLayout
            title="Search engine optimisation"
            description="Control how the published site is indexed, previewed, and understood by search engines."
        >
            <Head title="SEO" />
            <div className="flex flex-wrap gap-2 rounded-2xl border bg-white p-2 shadow-sm">
                <TabButton active={tab === 'overview'} onClick={() => setTab('overview')} icon={SearchCheck}>
                    Audit
                </TabButton>
                <TabButton active={tab === 'site'} onClick={() => setTab('site')} icon={Globe2}>
                    Site defaults
                </TabButton>
                <TabButton active={tab === 'pages'} onClick={() => setTab('pages')} icon={FileSearch}>
                    Page SEO
                </TabButton>
                <TabButton active={tab === 'redirects'} onClick={() => setTab('redirects')} icon={Link2}>
                    Redirects
                </TabButton>
            </div>
            {tab === 'overview' && <AuditPanel audit={props.audit} />}
            {tab === 'site' && <SiteForm settings={props.siteSettings} />}
            {tab === 'pages' && <PagesPanel pages={props.pages} site={props.siteSettings} />}
            {tab === 'redirects' && <RedirectsPanel redirects={props.redirects} />}
        </DashboardLayout>
    );
}

function AuditPanel({ audit }: Pick<Props, 'audit'>) {
    const ring =
        audit.score >= 80 ? 'text-emerald-600' : audit.score >= 50 ? 'text-amber-600' : 'text-red-600';
    return (
        <div className="mt-6 grid gap-6 lg:grid-cols-[18rem_1fr]">
            <section className="rounded-2xl border bg-white p-6 text-center shadow-sm">
                <div
                    className={`mx-auto grid size-36 place-items-center rounded-full border-12 border-current ${ring}`}
                >
                    <div>
                        <strong className="text-4xl text-slate-950">{audit.score}</strong>
                        <p className="text-xs font-semibold text-slate-500">out of 100</p>
                    </div>
                </div>
                <h2 className="mt-5 font-semibold">SEO health score</h2>
                <p className="mt-2 text-sm leading-6 text-slate-500">
                    This audit checks metadata, visibility, social previews, duplicate titles, and redirect
                    chains.
                </p>
            </section>
            <section className="overflow-hidden rounded-2xl border bg-white shadow-sm">
                <div className="border-b p-5">
                    <h2 className="font-semibold">Recommended improvements</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Changes remain in draft until you publish the website.
                    </p>
                </div>
                {audit.issues.length === 0 ? (
                    <div className="flex items-center gap-3 p-8 text-emerald-700">
                        <CheckCircle2 className="size-6" />
                        <p className="font-semibold">No issues detected.</p>
                    </div>
                ) : (
                    <div className="divide-y">
                        {audit.issues.map((issue, index) => (
                            <div key={`${issue.label}-${index}`} className="flex gap-3 p-5">
                                <AlertTriangle
                                    className={`mt-0.5 size-5 shrink-0 ${issue.severity === 'critical' ? 'text-red-600' : issue.severity === 'warning' ? 'text-amber-600' : 'text-blue-600'}`}
                                />
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="text-sm font-semibold">{issue.label}</h3>
                                        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 uppercase">
                                            {issue.severity}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">{issue.detail}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}

function SiteForm({ settings }: { settings: SiteSettings }) {
    const form = useForm<SiteSettings>(settings);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/dashboard/seo/site', { preserveScroll: true });
    };
    return (
        <form onSubmit={submit} className="mt-6 space-y-6">
            <Card
                title="Search and social defaults"
                description="Fallback metadata is used when a page does not provide an override."
            >
                <div className="grid gap-5 sm:grid-cols-2">
                    <Field
                        label="Site title"
                        value={form.data.site_title}
                        onChange={(v) => form.setData('site_title', v)}
                        error={form.errors.site_title}
                        count={70}
                    />
                    <Field
                        label="Default social image URL"
                        type="url"
                        value={form.data.default_social_image}
                        onChange={(v) => form.setData('default_social_image', v)}
                        error={form.errors.default_social_image}
                    />
                    <Area
                        label="Default meta description"
                        value={form.data.default_meta_description}
                        onChange={(v) => form.setData('default_meta_description', v)}
                        error={form.errors.default_meta_description}
                        count={320}
                    />
                    <Area
                        label="Social profile URLs (one HTTPS URL per line)"
                        value={form.data.social_profiles_text}
                        onChange={(v) => form.setData('social_profiles_text', v)}
                        error={form.errors.social_profiles_text}
                    />
                </div>
            </Card>
            <Card
                title="Business identity and integrations"
                description="Used for Organization, LocalBusiness, WebSite, verification, and analytics markup."
            >
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <Field
                        label="Business name"
                        value={form.data.business_name}
                        onChange={(v) => form.setData('business_name', v)}
                        error={form.errors.business_name}
                    />
                    <Select
                        label="Business type"
                        value={form.data.business_type}
                        onChange={(v) => form.setData('business_type', v as SiteSettings['business_type'])}
                    >
                        <option>Organization</option>
                        <option>LocalBusiness</option>
                    </Select>
                    <Field
                        label="Primary language"
                        value={form.data.primary_language}
                        onChange={(v) => form.setData('primary_language', v)}
                        error={form.errors.primary_language}
                        placeholder="en or en-IN"
                    />
                    <Field
                        label="Google verification token"
                        value={form.data.google_site_verification}
                        onChange={(v) => form.setData('google_site_verification', v)}
                        error={form.errors.google_site_verification}
                    />
                    <Field
                        label="Google Analytics ID"
                        value={form.data.google_analytics_id}
                        onChange={(v) => form.setData('google_analytics_id', v)}
                        error={form.errors.google_analytics_id}
                        placeholder="G-XXXXXXXXXX"
                    />
                    <Check
                        label="Allow search engines to index the published site"
                        checked={form.data.search_engine_visibility}
                        onChange={(v) => form.setData('search_engine_visibility', v)}
                    />
                </div>
            </Card>
            <Card
                title="Custom 404 page"
                description="Shown for unknown pages, content, and nested paths on this tenant website."
            >
                <div className="grid gap-5 sm:grid-cols-2">
                    <Field
                        label="404 title"
                        value={form.data.not_found_title}
                        onChange={(v) => form.setData('not_found_title', v)}
                        error={form.errors.not_found_title}
                    />
                    <Field
                        label="Home button label"
                        value={form.data.not_found_button_label}
                        onChange={(v) => form.setData('not_found_button_label', v)}
                        error={form.errors.not_found_button_label}
                    />
                    <Area
                        label="404 message"
                        value={form.data.not_found_message}
                        onChange={(v) => form.setData('not_found_message', v)}
                        error={form.errors.not_found_message}
                    />
                </div>
            </Card>
            <Save processing={form.processing} />
        </form>
    );
}

function PagesPanel({ pages, site }: { pages: SeoPage[]; site: SiteSettings }) {
    const [selectedId, setSelectedId] = useState(pages[0]?.id ?? 0);
    const page = pages.find((item) => item.id === selectedId) ?? pages[0];
    if (!page)
        return (
            <div className="mt-6 rounded-2xl border bg-white p-10 text-center text-sm text-slate-500">
                Create a page before configuring page SEO.
            </div>
        );
    return (
        <div className="mt-6 grid gap-6 lg:grid-cols-[16rem_1fr]">
            <aside className="h-fit rounded-2xl border bg-white p-2 shadow-sm">
                {pages.map((item) => (
                    <button
                        key={item.id}
                        onClick={() => setSelectedId(item.id)}
                        className={`flex w-full items-center justify-between rounded-xl px-3 py-3 text-left text-sm ${item.id === page.id ? 'bg-slate-950 text-white' : 'hover:bg-slate-50'}`}
                    >
                        <span>
                            <strong className="block">{item.title}</strong>
                            <span className={item.id === page.id ? 'text-slate-300' : 'text-slate-500'}>
                                {item.is_home ? '/' : `/${item.slug}`}
                            </span>
                        </span>
                        {!item.is_enabled && <span className="text-xs">Disabled</span>}
                    </button>
                ))}
            </aside>
            <PageForm key={page.id} page={page} site={site} />
        </div>
    );
}

function PageForm({ page, site }: { page: SeoPage; site: SiteSettings }) {
    const form = useForm<PageSeo>({ ...page.seo });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/dashboard/seo/pages/${page.id}`, { preserveScroll: true });
    };
    const title = form.data.seo_title || page.title;
    const description =
        form.data.meta_description ||
        site.default_meta_description ||
        'Add a meta description to improve this search preview.';
    return (
        <form onSubmit={submit} className="space-y-6">
            <Card title={`${page.title} metadata`} description="Overrides affect only this published page.">
                <div className="grid gap-5 sm:grid-cols-2">
                    <Field
                        label="SEO title"
                        value={form.data.seo_title ?? ''}
                        onChange={(v) => form.setData('seo_title', v)}
                        error={form.errors.seo_title}
                        count={70}
                    />
                    <Field
                        label="Canonical URL"
                        type="url"
                        value={form.data.canonical_url ?? ''}
                        onChange={(v) => form.setData('canonical_url', v)}
                        error={form.errors.canonical_url}
                        placeholder="https://www.ezyweb.in/page"
                    />
                    <Area
                        label="Meta description"
                        value={form.data.meta_description ?? ''}
                        onChange={(v) => form.setData('meta_description', v)}
                        error={form.errors.meta_description}
                        count={320}
                    />
                    <Select
                        label="Structured data page type"
                        value={form.data.schema_type}
                        onChange={(v) => form.setData('schema_type', v as PageSeo['schema_type'])}
                    >
                        <option>WebPage</option>
                        <option>AboutPage</option>
                        <option>ContactPage</option>
                    </Select>
                </div>
                <div className="mt-5 flex flex-wrap gap-5">
                    <Check
                        label="Index this page"
                        checked={form.data.is_indexed}
                        onChange={(v) => form.setData('is_indexed', v)}
                    />
                    <Check
                        label="Follow links on this page"
                        checked={form.data.is_followed}
                        onChange={(v) => form.setData('is_followed', v)}
                    />
                </div>
            </Card>
            <Card
                title="Social preview overrides"
                description="Open Graph and Twitter cards use these values, then fall back to page and site metadata."
            >
                <div className="grid gap-5 sm:grid-cols-2">
                    <Field
                        label="Social title"
                        value={form.data.open_graph_title ?? ''}
                        onChange={(v) => form.setData('open_graph_title', v)}
                        error={form.errors.open_graph_title}
                    />
                    <Field
                        label="Social image URL"
                        type="url"
                        value={form.data.open_graph_image ?? ''}
                        onChange={(v) => form.setData('open_graph_image', v)}
                        error={form.errors.open_graph_image}
                    />
                    <Area
                        label="Social description"
                        value={form.data.open_graph_description ?? ''}
                        onChange={(v) => form.setData('open_graph_description', v)}
                        error={form.errors.open_graph_description}
                    />
                </div>
            </Card>
            <div className="rounded-2xl border bg-white p-5 shadow-sm">
                <p className="text-xs font-semibold text-slate-500 uppercase">Google preview</p>
                <p className="mt-3 text-xl text-blue-700">{title}</p>
                <p className="mt-1 text-sm text-emerald-700">
                    {form.data.canonical_url || `https://www.ezyweb.in/${page.is_home ? '' : page.slug}`}
                </p>
                <p className="mt-1 max-w-2xl text-sm text-slate-600">{description}</p>
            </div>
            <Save processing={form.processing} />
        </form>
    );
}

function RedirectsPanel({ redirects }: { redirects: Redirect[] }) {
    const form = useForm({
        source_path: '',
        target_path: '',
        status_code: 301 as 301 | 302,
        is_active: true,
    });
    const confirm = useConfirm();
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/dashboard/seo/redirects', { preserveScroll: true, onSuccess: () => form.reset() });
    };
    const remove = async (redirect: Redirect) => {
        if (
            await confirm({
                title: 'Delete this redirect?',
                description: `${redirect.source_path} will stop forwarding after the next publish.`,
                confirmLabel: 'Delete redirect',
                destructive: true,
            })
        )
            router.delete(`/dashboard/seo/redirects/${redirect.public_id}`, { preserveScroll: true });
    };
    return (
        <div className="mt-6 space-y-6">
            <form onSubmit={submit} className="rounded-2xl border bg-white p-6 shadow-sm">
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_9rem_auto]">
                    <Field
                        label="Source path"
                        value={form.data.source_path}
                        onChange={(v) => form.setData('source_path', v)}
                        error={form.errors.source_path}
                        placeholder="/old-page"
                    />
                    <Field
                        label="Target path"
                        value={form.data.target_path}
                        onChange={(v) => form.setData('target_path', v)}
                        error={form.errors.target_path}
                        placeholder="/new-page"
                    />
                    <Select
                        label="Status"
                        value={String(form.data.status_code)}
                        onChange={(v) => form.setData('status_code', Number(v) as 301 | 302)}
                    >
                        <option value="301">301 permanent</option>
                        <option value="302">302 temporary</option>
                    </Select>
                    <div className="flex items-end">
                        <button
                            disabled={form.processing}
                            className="bg-brand-700 hover:bg-brand-600 inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            <Plus className="size-4" />
                            Add
                        </button>
                    </div>
                </div>
                <div className="mt-4">
                    <Check
                        label="Redirect is active"
                        checked={form.data.is_active}
                        onChange={(v) => form.setData('is_active', v)}
                    />
                </div>
            </form>
            <section className="overflow-hidden rounded-2xl border bg-white shadow-sm">
                <div className="border-b p-5">
                    <h2 className="font-semibold">Draft redirects</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Redirects become public only after publishing. Targets are restricted to this website.
                    </p>
                </div>
                {redirects.length === 0 ? (
                    <p className="p-10 text-center text-sm text-slate-500">No redirects configured.</p>
                ) : (
                    <div className="divide-y">
                        {redirects.map((redirect) => (
                            <div key={redirect.public_id} className="flex flex-wrap items-center gap-4 p-5">
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <code className="text-sm font-semibold">{redirect.source_path}</code>
                                        <span>→</span>
                                        <code className="text-sm">{redirect.target_path}</code>
                                    </div>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {redirect.status_code} · {redirect.is_active ? 'Active' : 'Inactive'}{' '}
                                        · {redirect.hit_count} hits
                                        {redirect.last_hit_at
                                            ? ` · Last ${new Date(redirect.last_hit_at).toLocaleString()}`
                                            : ''}
                                    </p>
                                </div>
                                <button
                                    onClick={() => void remove(redirect)}
                                    aria-label={`Delete redirect ${redirect.source_path}`}
                                    className="rounded-lg p-2 text-red-600 hover:bg-red-50"
                                >
                                    <Trash2 className="size-4" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}

function Card({ title, description, children }: { title: string; description: string; children: ReactNode }) {
    return (
        <section className="rounded-2xl border bg-white p-6 shadow-sm">
            <h2 className="font-semibold">{title}</h2>
            <p className="mt-1 mb-5 text-sm text-slate-500">{description}</p>
            {children}
        </section>
    );
}
function TabButton({
    active,
    onClick,
    icon: Icon,
    children,
}: {
    active: boolean;
    onClick: () => void;
    icon: typeof SearchCheck;
    children: ReactNode;
}) {
    return (
        <button
            onClick={onClick}
            className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ${active ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50'}`}
        >
            <Icon className="size-4" />
            {children}
        </button>
    );
}
function Field({
    label,
    value,
    onChange,
    type = 'text',
    error,
    placeholder,
    count,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    type?: string;
    error?: string;
    placeholder?: string;
    count?: number;
}) {
    return (
        <label>
            <span className="mb-1.5 flex justify-between text-sm font-semibold">
                <span>{label}</span>
                {count && (
                    <span className="font-normal text-slate-400">
                        {value.length}/{count}
                    </span>
                )}
            </span>
            <input
                type={type}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className="w-full rounded-xl border px-3 py-2.5 text-sm"
            />
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </label>
    );
}
function Area({
    label,
    value,
    onChange,
    error,
    count,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    count?: number;
}) {
    return (
        <label>
            <span className="mb-1.5 flex justify-between text-sm font-semibold">
                <span>{label}</span>
                {count && (
                    <span className="font-normal text-slate-400">
                        {value.length}/{count}
                    </span>
                )}
            </span>
            <textarea
                value={value}
                onChange={(e) => onChange(e.target.value)}
                rows={4}
                className="w-full rounded-xl border px-3 py-2.5 text-sm"
            />
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </label>
    );
}
function Select({
    label,
    value,
    onChange,
    children,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    children: ReactNode;
}) {
    return (
        <label>
            <span className="mb-1.5 block text-sm font-semibold">{label}</span>
            <select
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="w-full rounded-xl border px-3 py-2.5 text-sm"
            >
                {children}
            </select>
        </label>
    );
}
function Check({
    label,
    checked,
    onChange,
}: {
    label: string;
    checked: boolean;
    onChange: (value: boolean) => void;
}) {
    return (
        <label className="flex items-center gap-2 text-sm font-medium">
            <input
                type="checkbox"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
                className="size-4 rounded"
            />
            {label}
        </label>
    );
}
function Save({ processing }: { processing: boolean }) {
    return (
        <button
            disabled={processing}
            className="bg-brand-700 hover:bg-brand-600 rounded-xl px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
        >
            {processing ? 'Saving…' : 'Save changes'}
        </button>
    );
}
