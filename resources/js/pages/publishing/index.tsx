import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Clock3, Eye, History, RefreshCcw, Rocket, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { useConfirm } from '../../components/confirm-provider';
import { EmptyState } from '../../components/empty-state';
import { DashboardLayout } from '../../layouts/dashboard-layout';

interface PublishingWebsite {
    id: number;
    name: string;
    status: string;
    publication_status: string;
    draft_revision: number;
    published_revision: number;
    published_version_id: number | null;
    published_at: string | null;
    last_publish_error: string | null;
    has_unpublished_changes: boolean;
}

interface Version {
    id: number;
    version_number: number;
    reason: string;
    published_at: string | null;
    created_at: string;
    creator: string | null;
    is_current: boolean;
    preview_url: string;
}

export default function Publishing({
    publishingWebsite,
    previewUrl,
    versions,
}: {
    publishingWebsite: PublishingWebsite;
    previewUrl: string;
    versions: Version[];
}) {
    const confirm = useConfirm();
    const [publishing, setPublishing] = useState(false);

    const publish = async () => {
        if (
            !(await confirm({
                title: 'Publish this draft?',
                description:
                    'Ezyweb will validate the website, create an immutable version, and replace the live snapshot.',
                confirmLabel: 'Publish website',
            }))
        )
            return;
        setPublishing(true);
        router.post(
            '/dashboard/publishing/publish',
            {},
            {
                preserveScroll: true,
                onError: (errors) => toast.error(Object.values(errors)[0] ?? 'Publishing failed.'),
                onFinish: () => setPublishing(false),
            },
        );
    };

    const restore = async (version: Version) => {
        if (
            !(await confirm({
                title: `Restore version ${version.version_number}?`,
                description:
                    'The version will become a new draft. Your currently published website will stay live until you publish again.',
                confirmLabel: 'Restore to draft',
            }))
        )
            return;
        router.post(`/dashboard/publishing/versions/${version.id}/restore`, {}, { preserveScroll: true });
    };

    return (
        <DashboardLayout
            title="Publishing"
            description="Preview private draft changes, publish immutable snapshots, and safely restore earlier versions."
            breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Publishing' }]}
        >
            <Head title="Publishing" />
            <div className="grid gap-5 lg:grid-cols-[1.3fr_0.7fr]">
                <section className="rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold text-slate-500">{publishingWebsite.name}</p>
                            <h2 className="mt-1 text-xl font-bold text-slate-950">
                                Draft revision {publishingWebsite.draft_revision}
                            </h2>
                            <p className="mt-2 text-sm text-slate-600">
                                {publishingWebsite.has_unpublished_changes
                                    ? `Published revision ${publishingWebsite.published_revision} is still live.`
                                    : 'The live website matches this draft.'}
                            </p>
                        </div>
                        <Status value={publishingWebsite.publication_status} />
                    </div>
                    {publishingWebsite.last_publish_error && (
                        <p className="mt-4 flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
                            <TriangleAlert className="size-4 shrink-0" />{' '}
                            {publishingWebsite.last_publish_error}
                        </p>
                    )}
                    <div className="mt-6 flex flex-wrap gap-3">
                        <a
                            href={previewUrl}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-slate-700"
                        >
                            <Eye className="size-4" /> Preview draft
                        </a>
                        <button
                            onClick={() => void publish()}
                            disabled={publishing || !publishingWebsite.has_unpublished_changes}
                            className="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-45"
                        >
                            <Rocket className="size-4" /> {publishing ? 'Publishing…' : 'Publish draft'}
                        </button>
                    </div>
                </section>
                <section className="rounded-2xl border bg-slate-950 p-5 text-white shadow-sm sm:p-6">
                    <Clock3 className="size-6 text-emerald-300" />
                    <h2 className="mt-4 text-lg font-bold">Safe publishing</h2>
                    <p className="mt-2 text-sm leading-6 text-slate-300">
                        Draft edits never clear or replace the live snapshot. Failed validation leaves the
                        current website untouched and can be retried safely.
                    </p>
                </section>
            </div>

            <section className="mt-6 rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
                <div className="flex items-center gap-3">
                    <span className="grid size-10 place-items-center rounded-xl bg-slate-100">
                        <History className="size-5" />
                    </span>
                    <div>
                        <h2 className="font-bold">Version history</h2>
                        <p className="text-sm text-slate-500">
                            Published and restored snapshots are preserved.
                        </p>
                    </div>
                </div>
                {versions.length === 0 ? (
                    <div className="mt-5">
                        <EmptyState
                            icon={History}
                            title="No versions yet"
                            description="Publish the first complete draft to create version 1."
                        />
                    </div>
                ) : (
                    <div className="mt-5 divide-y rounded-xl border">
                        {versions.map((version) => (
                            <article
                                key={version.id}
                                className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="font-bold">Version {version.version_number}</h3>
                                        {version.is_current && (
                                            <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                                Live
                                            </span>
                                        )}
                                        {!version.published_at && (
                                            <span className="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                                Restored draft
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {version.reason.replaceAll('-', ' ')} · {version.creator ?? 'System'}{' '}
                                        · {new Date(version.created_at).toLocaleString()}
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <a
                                        href={version.preview_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold"
                                    >
                                        <Eye className="size-3.5" /> Preview
                                    </a>
                                    <button
                                        onClick={() => void restore(version)}
                                        className="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold"
                                    >
                                        <RefreshCcw className="size-3.5" /> Restore
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}

function Status({ value }: { value: string }) {
    const published = value === 'published';
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold capitalize ${published ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}`}
        >
            {published ? <CheckCircle2 className="size-3.5" /> : <Clock3 className="size-3.5" />}
            {value.replaceAll('_', ' ')}
        </span>
    );
}
