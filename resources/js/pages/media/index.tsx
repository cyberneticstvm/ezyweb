import { Head, router, useForm } from '@inertiajs/react';
import { Copy, FolderOpen, HardDrive, ImagePlus, Pencil, Search, Trash2, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import { useConfirm } from '../../components/confirm-provider';
import { DashboardLayout } from '../../layouts/dashboard-layout';

type Variant = { url: string; width: number; height: number; size: number };
type Asset = {
    public_id: string;
    original_name: string;
    title: string | null;
    alt_text: string | null;
    caption: string | null;
    folder: string | null;
    width: number;
    height: number;
    total_size: number;
    variants: Record<string, Variant>;
    usages_count: number;
};
type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
};

export default function MediaIndex({
    assets,
    folders,
    filters,
    storage,
    maxUploadKb,
}: {
    assets: Paginated<Asset>;
    folders: string[];
    filters: { search?: string; folder?: string };
    storage: { used: number; limit: number };
    maxUploadKb: number;
}) {
    const [selected, setSelected] = useState<Asset | null>(null);
    const confirm = useConfirm();
    const [search, setSearch] = useState(filters.search ?? '');
    const upload = useForm<{
        image: File | null;
        folder: string;
        alt_text: string;
        title: string;
        caption: string;
    }>({ image: null, folder: filters.folder ?? '', alt_text: '', title: '', caption: '' });
    const bytes = (value: number) =>
        value < 1024 * 1024 ? `${(value / 1024).toFixed(1)} KB` : `${(value / 1024 / 1024).toFixed(1)} MB`;
    const filter = (event: FormEvent) => {
        event.preventDefault();
        router.get('/dashboard/media', { search, folder: filters.folder ?? '' }, { preserveState: true });
    };
    const submit = (event: FormEvent) => {
        event.preventDefault();
        upload.post('/dashboard/media', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => upload.reset(),
        });
    };
    const copy = async (asset: Asset) => {
        await navigator.clipboard.writeText(asset.variants.original.url);
        toast.success('Image URL copied.');
    };
    const remove = async (asset: Asset) => {
        const warning =
            asset.usages_count > 0
                ? `This image is used in ${asset.usages_count} place(s). Delete it anyway?`
                : 'Delete this image and all optimized variants?';
        if (
            await confirm({
                title: 'Delete this image?',
                description: warning,
                confirmLabel: 'Delete image',
                destructive: true,
            })
        )
            router.delete(`/dashboard/media/${asset.public_id}`, {
                data: { confirmed_usage: asset.usages_count > 0 },
                preserveScroll: true,
            });
    };

    return (
        <DashboardLayout
            title="Media library"
            description="Upload, organize, and reuse optimized images across your website."
        >
            <Head title="Media library" />
            <div className="space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold text-blue-600">Assets</p>
                        <h2 className="text-3xl font-bold tracking-tight text-slate-950">Media library</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Upload once, then use optimized image URLs across your website.
                        </p>
                    </div>
                    <div className="min-w-64 rounded-2xl border border-slate-200 bg-white p-4">
                        <div className="flex items-center justify-between text-sm">
                            <span className="flex items-center gap-2 font-semibold">
                                <HardDrive className="size-4" />
                                Storage
                            </span>
                            <span>
                                {bytes(storage.used)} / {bytes(storage.limit)}
                            </span>
                        </div>
                        <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div
                                className="h-full rounded-full bg-blue-600"
                                style={{ width: `${Math.min(100, (storage.used / storage.limit) * 100)}%` }}
                            />
                        </div>
                    </div>
                </div>

                <form
                    onSubmit={submit}
                    className="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[1.3fr_1fr_1fr_auto]"
                >
                    <label className="block">
                        <span className="text-sm font-semibold text-slate-700">Image</span>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) => upload.setData('image', event.target.files?.[0] ?? null)}
                            className="mt-2 block w-full text-sm"
                            required
                        />
                        <span className="mt-1 block text-xs text-slate-500">
                            JPEG, PNG or WebP up to {Math.round(maxUploadKb / 1024)} MB. SVG and executable
                            formats are blocked.
                        </span>
                        {upload.errors.image && (
                            <span className="text-xs text-red-600">{upload.errors.image}</span>
                        )}
                    </label>
                    <Field
                        label="Folder"
                        value={upload.data.folder}
                        onChange={(value) => upload.setData('folder', value)}
                        placeholder="e.g. Homepage"
                    />
                    <Field
                        label="Alt text"
                        value={upload.data.alt_text}
                        onChange={(value) => upload.setData('alt_text', value)}
                        placeholder="Describe the image"
                    />
                    <button
                        disabled={upload.processing}
                        className="self-end rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        <ImagePlus className="mr-2 inline size-4" />
                        {upload.processing ? 'Optimizing…' : 'Upload'}
                    </button>
                </form>

                <div className="flex flex-wrap gap-3">
                    <form onSubmit={filter} className="relative min-w-72 flex-1">
                        <Search className="absolute top-3 left-3 size-4 text-slate-400" />
                        <input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search filename, title or alt text"
                            className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-4 pl-10 text-sm"
                        />
                    </form>
                    <select
                        value={filters.folder ?? ''}
                        onChange={(event) =>
                            router.get(
                                '/dashboard/media',
                                { search, folder: event.target.value },
                                { preserveState: true },
                            )
                        }
                        className="rounded-xl border border-slate-200 bg-white px-4 text-sm"
                    >
                        <option value="">All folders</option>
                        {folders.map((folder) => (
                            <option key={folder} value={folder}>
                                {folder}
                            </option>
                        ))}
                    </select>
                </div>

                {assets.data.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                        <FolderOpen className="mx-auto size-9 text-slate-400" />
                        <p className="mt-3 font-semibold">No images found</p>
                        <p className="text-sm text-slate-500">Upload an image or change your filters.</p>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {assets.data.map((asset) => (
                            <article
                                key={asset.public_id}
                                className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                            >
                                <button
                                    onClick={() => setSelected(asset)}
                                    className="block aspect-[4/3] w-full overflow-hidden bg-slate-100"
                                >
                                    <img
                                        src={asset.variants.thumbnail.url}
                                        alt={asset.alt_text ?? ''}
                                        className="h-full w-full object-cover"
                                    />
                                </button>
                                <div className="p-4">
                                    <p className="truncate font-semibold text-slate-900">
                                        {asset.title || asset.original_name}
                                    </p>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {asset.width}×{asset.height} · {bytes(asset.total_size)}
                                        {asset.folder ? ` · ${asset.folder}` : ''}
                                    </p>
                                    {asset.usages_count > 0 && (
                                        <p className="mt-2 text-xs font-semibold text-amber-700">
                                            Used in {asset.usages_count} place(s)
                                        </p>
                                    )}
                                    <div className="mt-4 flex gap-2">
                                        <button
                                            onClick={() => copy(asset)}
                                            className="rounded-lg border border-slate-200 p-2"
                                            title="Copy original URL"
                                        >
                                            <Copy className="size-4" />
                                        </button>
                                        <button
                                            onClick={() => setSelected(asset)}
                                            className="rounded-lg border border-slate-200 p-2"
                                            title="Edit details"
                                        >
                                            <Pencil className="size-4" />
                                        </button>
                                        <button
                                            onClick={() => remove(asset)}
                                            className="ml-auto rounded-lg border border-red-100 p-2 text-red-600"
                                            title="Delete"
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
                <div className="flex justify-end gap-2">
                    {assets.prev_page_url && (
                        <button
                            onClick={() => router.get(assets.prev_page_url!)}
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            Previous
                        </button>
                    )}
                    {assets.next_page_url && (
                        <button
                            onClick={() => router.get(assets.next_page_url!)}
                            className="rounded-lg border px-3 py-2 text-sm"
                        >
                            Next
                        </button>
                    )}
                </div>
            </div>
            {selected && <Editor asset={selected} onClose={() => setSelected(null)} />}
        </DashboardLayout>
    );
}

function Editor({ asset, onClose }: { asset: Asset; onClose: () => void }) {
    const form = useForm({
        folder: asset.folder ?? '',
        alt_text: asset.alt_text ?? '',
        title: asset.title ?? '',
        caption: asset.caption ?? '',
    });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/dashboard/media/${asset.public_id}`, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <div className="fixed inset-0 z-50 flex justify-end bg-slate-950/40" role="dialog" aria-modal="true">
            <form
                onSubmit={submit}
                className="h-full w-full max-w-md overflow-y-auto bg-white p-6 shadow-2xl"
            >
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-bold">Image details</h2>
                    <button type="button" onClick={onClose}>
                        <X className="size-5" />
                    </button>
                </div>
                <img
                    src={asset.variants.small.url}
                    alt=""
                    className="mt-5 aspect-video w-full rounded-xl bg-slate-100 object-contain"
                />
                <div className="mt-6 space-y-4">
                    <Field
                        label="Title"
                        value={form.data.title}
                        onChange={(value) => form.setData('title', value)}
                    />
                    <Field
                        label="Alt text"
                        value={form.data.alt_text}
                        onChange={(value) => form.setData('alt_text', value)}
                    />
                    <Field
                        label="Folder"
                        value={form.data.folder}
                        onChange={(value) => form.setData('folder', value)}
                    />
                    <label className="block">
                        <span className="text-sm font-semibold text-slate-700">Caption</span>
                        <textarea
                            value={form.data.caption}
                            onChange={(event) => form.setData('caption', event.target.value)}
                            rows={4}
                            className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        />
                    </label>
                </div>
                <button
                    disabled={form.processing}
                    className="mt-6 w-full rounded-xl bg-slate-950 py-3 text-sm font-semibold text-white"
                >
                    Save details
                </button>
            </form>
        </div>
    );
}

function Field({
    label,
    value,
    onChange,
    placeholder,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
}) {
    return (
        <label className="block">
            <span className="text-sm font-semibold text-slate-700">{label}</span>
            <input
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
            />
        </label>
    );
}
