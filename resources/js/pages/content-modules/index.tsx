import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, ImagePlus, Pencil, Plus, Search, Trash2, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react';
import { useConfirm } from '../../components/confirm-provider';
import { EmptyState } from '../../components/empty-state';
import { DashboardLayout } from '../../layouts/dashboard-layout';

type FieldType =
    | 'text'
    | 'textarea'
    | 'richtext'
    | 'url'
    | 'email'
    | 'slug'
    | 'boolean'
    | 'number'
    | 'decimal'
    | 'select'
    | 'date'
    | 'time'
    | 'datetime'
    | 'lines';
interface Field {
    key: string;
    label: string;
    type: FieldType;
    required: boolean;
    options: string[];
}
interface Entry {
    id: number;
    title: string;
    description: string;
    active: boolean;
    featured: boolean;
    data: Record<string, string | number | boolean | null>;
    updated_at: string;
}
interface Pagination {
    data: Entry[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
}
interface Props {
    module: { key: string; label: string; singular: string; fields: Field[]; limit: number };
    entries: Pagination;
    filters: { search: string };
    mediaAssets: MediaAssetOption[];
    mediaUploadUrl: string;
}
interface MediaAssetOption {
    public_id: string;
    title: string;
    alt_text: string | null;
    url: string;
    thumbnail_url: string;
}

export default function ContentModuleIndex({ module, entries, filters, mediaAssets, mediaUploadUrl }: Props) {
    const confirm = useConfirm();
    const [editing, setEditing] = useState<Entry | null>(null);
    const [editorOpen, setEditorOpen] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');
    const defaults = useMemo(() => initialData(module.fields), [module.fields]);
    const form = useForm<Record<string, string | number | boolean | null>>(defaults);

    const openCreate = () => {
        setEditing(null);
        form.setData(defaults);
        form.clearErrors();
        setEditorOpen(true);
    };
    const openEdit = (entry: Entry) => {
        setEditing(entry);
        form.setData({ ...defaults, ...entry.data });
        form.clearErrors();
        setEditorOpen(true);
    };
    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setEditorOpen(false) };
        if (editing) form.put(`/dashboard/content/${module.key}/${editing.id}`, options);
        else form.post(`/dashboard/content/${module.key}`, options);
    };
    const remove = async (entry: Entry) => {
        if (
            await confirm({
                title: `Remove ${entry.title}?`,
                description: 'This removes the draft entry. Published versions remain immutable.',
                confirmLabel: 'Remove',
                destructive: true,
            })
        ) {
            router.delete(`/dashboard/content/${module.key}/${entry.id}`, { preserveScroll: true });
        }
    };
    const runSearch = (event: FormEvent) => {
        event.preventDefault();
        router.get(`/dashboard/content/${module.key}`, search ? { search } : {}, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <DashboardLayout
            title={module.label}
            description={`Manage tenant-scoped ${module.label.toLowerCase()} used by your website sections.`}
        >
            <Head title={module.label} />
            <div className="flex flex-col gap-3 rounded-2xl border bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <form onSubmit={runSearch} className="flex min-w-0 flex-1 gap-2">
                    <label className="relative max-w-md flex-1">
                        <span className="sr-only">Search {module.label}</span>
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                        <input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={`Search ${module.label.toLowerCase()}`}
                            className="w-full rounded-xl border py-2.5 pr-3 pl-9 text-sm"
                        />
                    </label>
                    <button className="rounded-xl border px-4 py-2.5 text-sm font-semibold">Search</button>
                </form>
                <div className="flex items-center gap-3">
                    <span className="text-xs text-slate-500">
                        {entries.total} of {module.limit}
                    </span>
                    <button
                        onClick={openCreate}
                        className="bg-brand-700 inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        <Plus className="size-4" /> Add {module.singular}
                    </button>
                </div>
            </div>

            {entries.data.length === 0 ? (
                <div className="mt-6">
                    <EmptyState
                        icon={Plus}
                        title={`No ${module.label.toLowerCase()} yet`}
                        description={`Create your first ${module.singular} and connect it to a controlled website section.`}
                        action={
                            <button
                                onClick={openCreate}
                                className="bg-brand-700 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                Add {module.singular}
                            </button>
                        }
                    />
                </div>
            ) : (
                <div className="mt-6 overflow-hidden rounded-2xl border bg-white shadow-sm">
                    <div className="divide-y">
                        {entries.data.map((entry) => (
                            <article key={entry.id} className="flex items-start gap-4 p-5">
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-semibold text-slate-950">{entry.title}</h2>
                                        <Status active={entry.active} />
                                        {entry.featured && (
                                            <span className="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                                Featured
                                            </span>
                                        )}
                                    </div>
                                    {entry.description && (
                                        <p className="mt-1 line-clamp-2 text-sm text-slate-600">
                                            {entry.description}
                                        </p>
                                    )}
                                    <p className="mt-2 text-xs text-slate-400">
                                        Updated {new Date(entry.updated_at).toLocaleDateString()}
                                    </p>
                                </div>
                                <button
                                    onClick={() => openEdit(entry)}
                                    className="rounded-lg border p-2 text-slate-600 hover:bg-slate-50"
                                    aria-label={`Edit ${entry.title}`}
                                >
                                    <Pencil className="size-4" />
                                </button>
                                <button
                                    onClick={() => void remove(entry)}
                                    className="rounded-lg border p-2 text-red-600 hover:bg-red-50"
                                    aria-label={`Remove ${entry.title}`}
                                >
                                    <Trash2 className="size-4" />
                                </button>
                            </article>
                        ))}
                    </div>
                    {entries.last_page > 1 && (
                        <div className="flex items-center justify-between border-t p-4 text-sm">
                            {entries.prev_page_url ? (
                                <Link
                                    href={entries.prev_page_url}
                                    className="inline-flex items-center gap-1 font-semibold"
                                >
                                    <ArrowLeft className="size-4" /> Previous
                                </Link>
                            ) : (
                                <span />
                            )}
                            <span className="text-slate-500">
                                Page {entries.current_page} of {entries.last_page}
                            </span>
                            {entries.next_page_url ? (
                                <Link
                                    href={entries.next_page_url}
                                    className="inline-flex items-center gap-1 font-semibold"
                                >
                                    Next <ArrowRight className="size-4" />
                                </Link>
                            ) : (
                                <span />
                            )}
                        </div>
                    )}
                </div>
            )}

            {editorOpen && (
                <div
                    className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/55 p-4 sm:p-8"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="content-editor-title"
                >
                    <div className="mx-auto max-w-3xl rounded-2xl bg-white shadow-2xl">
                        <div className="sticky top-0 z-10 flex items-center justify-between border-b bg-white px-5 py-4">
                            <div>
                                <p className="text-brand-700 text-xs font-semibold uppercase">
                                    {module.label}
                                </p>
                                <h2 id="content-editor-title" className="text-lg font-bold">
                                    {editing ? `Edit ${module.singular}` : `Add ${module.singular}`}
                                </h2>
                            </div>
                            <button
                                onClick={() => setEditorOpen(false)}
                                className="rounded-lg p-2 hover:bg-slate-100"
                                aria-label="Close editor"
                            >
                                <X className="size-5" />
                            </button>
                        </div>
                        <form onSubmit={submit} className="p-5 sm:p-6">
                            <div className="grid gap-5 sm:grid-cols-2">
                                {module.fields.map((field) => (
                                    <FieldControl
                                        key={field.key}
                                        field={field}
                                        value={form.data[field.key]}
                                        error={form.errors[field.key]}
                                        mediaAssets={mediaAssets}
                                        mediaUploadUrl={mediaUploadUrl}
                                        onChange={(value) => form.setData(field.key, value)}
                                    />
                                ))}
                            </div>
                            {form.errors.limit && (
                                <p className="mt-4 text-sm text-red-600">{form.errors.limit}</p>
                            )}
                            <div className="mt-7 flex justify-end gap-3 border-t pt-5">
                                <button
                                    type="button"
                                    onClick={() => setEditorOpen(false)}
                                    className="rounded-xl border px-4 py-2.5 text-sm font-semibold"
                                >
                                    Cancel
                                </button>
                                <button
                                    disabled={form.processing}
                                    className="bg-brand-700 rounded-xl px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                                >
                                    {form.processing ? 'Saving…' : 'Save entry'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </DashboardLayout>
    );
}

function FieldControl({
    field,
    value,
    error,
    mediaAssets,
    mediaUploadUrl,
    onChange,
}: {
    field: Field;
    value: string | number | boolean | null;
    error?: string;
    mediaAssets: MediaAssetOption[];
    mediaUploadUrl: string;
    onChange: (value: string | number | boolean) => void;
}) {
    if (field.type === 'boolean')
        return (
            <label className="flex items-center gap-3 rounded-xl border p-4 text-sm font-medium sm:self-end">
                <input
                    type="checkbox"
                    checked={Boolean(value)}
                    onChange={(e) => onChange(e.target.checked)}
                    className="size-4"
                />
                {field.label}
            </label>
        );
    const wide = ['textarea', 'richtext', 'lines'].includes(field.type);
    const inputType =
        field.type === 'datetime'
            ? 'datetime-local'
            : ['email', 'url', 'date', 'time', 'number'].includes(field.type)
              ? field.type
              : field.type === 'decimal'
                ? 'number'
                : 'text';
    return (
        <label className={wide ? 'sm:col-span-2' : ''}>
            <span className="mb-1.5 block text-sm font-semibold">
                {field.label}
                {field.required && <span className="text-red-500"> *</span>}
            </span>
            {field.type === 'select' ? (
                <select
                    value={String(value ?? '')}
                    onChange={(e) => onChange(e.target.value)}
                    className="w-full rounded-xl border px-3 py-2.5 text-sm"
                >
                    <option value="">Select…</option>
                    {field.options.map((option) => (
                        <option key={option} value={option}>
                            {option.replace('-', ' ')}
                        </option>
                    ))}
                </select>
            ) : field.type === 'richtext' ? (
                <RichTextEditor value={String(value ?? '')} onChange={onChange} mediaAssets={mediaAssets} mediaUploadUrl={mediaUploadUrl} />
            ) : wide ? (
                <textarea
                    rows={4}
                    value={String(value ?? '')}
                    onChange={(e) => onChange(e.target.value)}
                    className="w-full rounded-xl border px-3 py-2.5 text-sm"
                />
            ) : (
                <input
                    type={inputType}
                    step={field.type === 'decimal' ? '0.01' : undefined}
                    value={formatInputValue(value, field.type)}
                    onChange={(e) => onChange(e.target.value)}
                    className="w-full rounded-xl border px-3 py-2.5 text-sm"
                />
            )}
            {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
        </label>
    );
}

function RichTextEditor({
    value,
    onChange,
    mediaAssets,
    mediaUploadUrl,
}: {
    value: string;
    onChange: (value: string) => void;
    mediaAssets: MediaAssetOption[];
    mediaUploadUrl: string;
}) {
    const editor = useRef<HTMLDivElement>(null);
    const fileInput = useRef<HTMLInputElement>(null);
    const savedRange = useRef<Range | null>(null);
    const selectedFigure = useRef<HTMLElement | null>(null);
    const [showMedia, setShowMedia] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [uploadError, setUploadError] = useState('');
    const [imageSettings, setImageSettings] = useState<{ width: string; align: string } | null>(null);
    useEffect(() => {
        if (
            editor.current &&
            document.activeElement !== editor.current &&
            editor.current.innerHTML !== value
        ) {
            editor.current.innerHTML = value;
        }
    }, [value]);
    const sync = () => onChange(editor.current?.innerHTML ?? '');
    const rememberSelection = () => {
        const selection = window.getSelection();
        if (selection?.rangeCount && editor.current?.contains(selection.anchorNode)) savedRange.current = selection.getRangeAt(0).cloneRange();
    };
    const restoreSelection = () => {
        editor.current?.focus();
        if (!savedRange.current) return;
        const selection = window.getSelection();
        selection?.removeAllRanges();
        selection?.addRange(savedRange.current);
    };
    const command = (name: string, argument?: string) => {
        editor.current?.focus();
        document.execCommand(name, false, argument);
        sync();
    };
    const addLink = () => {
        const url = window.prompt('Link URL (https://, /page, mailto: or tel:)');
        if (url && /^(https?:\/\/|\/|mailto:|tel:|#)/i.test(url)) command('createLink', url);
    };
    const insertImage = (asset: MediaAssetOption, caption = '') => {
        restoreSelection();
        const figure = document.createElement('figure');
        figure.dataset.width = '100';
        figure.dataset.align = 'center';
        const image = document.createElement('img');
        image.src = asset.url;
        image.alt = asset.alt_text || asset.title;
        image.loading = 'lazy';
        figure.appendChild(image);
        if (caption) {
            const figcaption = document.createElement('figcaption');
            figcaption.textContent = caption;
            figure.appendChild(figcaption);
        }
        const range = window.getSelection()?.rangeCount ? window.getSelection()!.getRangeAt(0) : null;
        if (range) {
            range.deleteContents();
            range.insertNode(figure);
            range.setStartAfter(figure);
            range.collapse(true);
        } else editor.current?.appendChild(figure);
        setShowMedia(false);
        sync();
    };
    const selectImage = (target: EventTarget | null) => {
        if (!(target instanceof HTMLImageElement)) {
            selectedFigure.current = null;
            setImageSettings(null);
            return;
        }
        const figure = target.closest('figure');
        if (!(figure instanceof HTMLElement)) return;
        selectedFigure.current = figure;
        setImageSettings({ width: figure.dataset.width || '100', align: figure.dataset.align || 'center' });
    };
    const resizeImage = (width: string) => {
        if (!selectedFigure.current) return;
        selectedFigure.current.dataset.width = width;
        setImageSettings((current) => current ? { ...current, width } : null);
        sync();
    };
    const alignImage = (align: string) => {
        if (!selectedFigure.current) return;
        selectedFigure.current.dataset.align = align;
        setImageSettings((current) => current ? { ...current, align } : null);
        sync();
    };
    const uploadImage = async (file: File) => {
        setUploading(true);
        setUploadError('');
        const data = new FormData();
        data.append('image', file);
        data.append('folder', 'Blog');
        data.append('title', file.name.replace(/\.[^.]+$/, ''));
        const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        try {
            const response = await fetch(mediaUploadUrl, {
                method: 'POST', body: data,
                headers: { Accept: 'application/json', ...(token ? { 'X-CSRF-TOKEN': token } : {}) },
                credentials: 'same-origin',
            });
            const payload: unknown = await response.json();
            const result = payload && typeof payload === 'object' ? payload as { message?: string; errors?: { image?: string[] }; asset?: MediaAssetOption } : {};
            if (!response.ok || !result.asset) throw new Error(result.message || result.errors?.image?.[0] || 'Image upload failed.');
            insertImage(result.asset);
        } catch (error) {
            setUploadError(error instanceof Error ? error.message : 'Image upload failed.');
        } finally {
            setUploading(false);
            if (fileInput.current) fileInput.current.value = '';
        }
    };

    return (
        <div className="overflow-hidden rounded-xl border">
            <div className="flex flex-wrap gap-1 border-b bg-slate-50 p-2" aria-label="Rich text formatting">
                <select
                    aria-label="Text style"
                    defaultValue="p"
                    onChange={(event) => command('formatBlock', event.target.value)}
                    className="rounded-lg border bg-white px-2 py-1 text-xs font-semibold"
                >
                    <option value="p">Paragraph</option><option value="h2">Heading 2</option><option value="h3">Heading 3</option><option value="blockquote">Quote</option>
                </select>
                {[
                    ['bold', 'Bold'],
                    ['italic', 'Italic'],
                    ['insertUnorderedList', 'Bullets'],
                    ['insertOrderedList', 'Numbering'],
                ].map(([name, label]) => (
                    <button
                        key={name}
                        type="button"
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => command(name)}
                        className="rounded-lg border bg-white px-2.5 py-1 text-xs font-semibold"
                    >
                        {label}
                    </button>
                ))}
                <button type="button" onMouseDown={(event) => event.preventDefault()} onClick={addLink} className="rounded-lg border bg-white px-2.5 py-1 text-xs font-semibold">Link</button>
                <button type="button" onMouseDown={(event) => event.preventDefault()} onClick={() => command('unlink')} className="rounded-lg border bg-white px-2.5 py-1 text-xs font-semibold">Unlink</button>
                <button type="button" onMouseDown={(event) => event.preventDefault()} onClick={() => command('insertHorizontalRule')} className="rounded-lg border bg-white px-2.5 py-1 text-xs font-semibold">Divider</button>
                <button type="button" onMouseDown={(event) => { event.preventDefault(); rememberSelection(); }} onClick={() => setShowMedia((open) => !open)} className="inline-flex items-center gap-1 rounded-lg border bg-white px-2.5 py-1 text-xs font-semibold"><ImagePlus className="size-3.5" /> Media</button>
                <button type="button" disabled={uploading} onMouseDown={(event) => { event.preventDefault(); rememberSelection(); }} onClick={() => fileInput.current?.click()} className="inline-flex items-center gap-1 rounded-lg border bg-white px-2.5 py-1 text-xs font-semibold disabled:opacity-50"><Upload className="size-3.5" /> {uploading ? 'Uploading…' : 'Upload image'}</button>
                <input ref={fileInput} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={(event) => { const file = event.target.files?.[0]; if (file) void uploadImage(file); }} />
            </div>
            {imageSettings && (
                <div className="flex flex-wrap items-center gap-2 border-b bg-blue-50 px-3 py-2" aria-label="Selected image settings">
                    <strong className="text-[11px] text-blue-900">Selected image</strong>
                    <span className="text-[11px] text-slate-600">Width</span>
                    {['25', '50', '75', '100'].map((width) => (
                        <button key={width} type="button" onClick={() => resizeImage(width)} className={`rounded-md border px-2 py-1 text-[11px] font-semibold ${imageSettings.width === width ? 'border-blue-600 bg-blue-600 text-white' : 'bg-white'}`}>{width}%</button>
                    ))}
                    <span className="ml-1 text-[11px] text-slate-600">Align</span>
                    {['left', 'center', 'right'].map((align) => (
                        <button key={align} type="button" onClick={() => alignImage(align)} className={`rounded-md border px-2 py-1 text-[11px] font-semibold capitalize ${imageSettings.align === align ? 'border-blue-600 bg-blue-600 text-white' : 'bg-white'}`}>{align}</button>
                    ))}
                </div>
            )}
            {showMedia && (
                <div className="border-b bg-white p-3">
                    <p className="mb-2 text-xs font-semibold text-slate-600">Choose an image from your media library</p>
                    {mediaAssets.length ? <div className="grid max-h-48 grid-cols-4 gap-2 overflow-y-auto sm:grid-cols-6">
                        {mediaAssets.map((asset) => <button key={asset.public_id} type="button" title={asset.title} onClick={() => insertImage(asset, asset.alt_text || '')} className="overflow-hidden rounded-lg border hover:ring-2 hover:ring-blue-500"><img src={asset.thumbnail_url} alt={asset.alt_text || asset.title} className="aspect-square w-full object-cover" /></button>)}
                    </div> : <p className="text-xs text-slate-500">No media yet. Use Upload image to add one.</p>}
                </div>
            )}
            {uploadError && <p className="border-b bg-red-50 px-3 py-2 text-xs text-red-700">{uploadError}</p>}
            <div
                ref={editor}
                contentEditable
                suppressContentEditableWarning
                role="textbox"
                aria-multiline="true"
                className="ezyweb-rich-editor min-h-40 px-3 py-2.5 text-sm outline-none"
                onClick={(event) => selectImage(event.target)}
                onMouseUp={rememberSelection}
                onKeyUp={rememberSelection}
                onInput={(event) => onChange(event.currentTarget.innerHTML)}
            />
            <p className="border-t bg-slate-50 px-3 py-2 text-[11px] text-slate-500">Images are optimized and saved to your Ezyweb media library. Add descriptive alt text in Media for accessibility and SEO.</p>
        </div>
    );
}

function initialData(fields: Field[]) {
    return Object.fromEntries(
        fields.map((field) => [
            field.key,
            field.type === 'boolean'
                ? ['is_active', 'show_price'].includes(field.key)
                : field.key === 'status'
                  ? 'draft'
                  : field.key === 'aspect_ratio'
                    ? 'auto'
                    : field.key === 'display_order'
                      ? 0
                      : '',
        ]),
    );
}
function formatInputValue(value: string | number | boolean | null, type: FieldType) {
    if (type === 'datetime' && typeof value === 'string') return value.slice(0, 16);
    return typeof value === 'boolean' ? '' : (value ?? '');
}
function Status({ active }: { active: boolean }) {
    return (
        <span
            className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}
        >
            {active ? 'Active' : 'Draft / inactive'}
        </span>
    );
}
