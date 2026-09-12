import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, CalendarDays, CreditCard, Globe2, Layers3, Rocket } from 'lucide-react';
import { EmptyState } from '../../components/empty-state';
import { DashboardLayout } from '../../layouts/dashboard-layout';
import type { AppPageProps } from '../../types';

export default function Dashboard() {
    const { auth, tenant, website, subscription } = usePage<AppPageProps>().props;
    return (
        <DashboardLayout
            title={`Good to see you, ${auth.user?.name.split(' ')[0] ?? 'there'}`}
            description={`${tenant?.name ?? 'Your workspace'} is securely isolated and ready to build, preview, and publish.`}
        >
            <Head title="Dashboard" />
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Metric icon={Globe2} label="Websites" value="1" />
                <Metric
                    icon={Layers3}
                    label="Website status"
                    value={website?.publication_status ?? website?.status ?? 'Draft'}
                />
                <Metric
                    icon={CreditCard}
                    label="Subscription"
                    value={
                        subscription
                            ? `${subscription.plan ?? 'Plan'} · ${subscription.status.replace('_', ' ')}`
                            : 'Not assigned'
                    }
                />
                <Metric
                    icon={CalendarDays}
                    label="Renewal due"
                    value={
                        subscription?.current_period_ends_at
                            ? new Date(subscription.current_period_ends_at).toLocaleDateString('en-IN')
                            : '—'
                    }
                />
            </div>
            <div className="mt-6 grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
                <div className="rounded-2xl border bg-white p-6 shadow-sm sm:p-8">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-brand-700 text-sm font-semibold">Primary website</p>
                            <h2 className="mt-2 text-xl font-bold">{website?.name}</h2>
                            <p className="mt-2 text-sm text-slate-600">{website?.hostname}</p>
                        </div>
                        <span className="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                            {(website?.publication_status ?? 'draft').replace('_', ' ')}
                        </span>
                    </div>
                    <div className="mt-7 grid gap-3 sm:grid-cols-2">
                        <Info
                            label="Website type"
                            value={website?.type === 'landing_page' ? 'Single-page' : 'Multipage'}
                        />
                        <Info label="Starting template" value={website?.template_key ?? '—'} />
                    </div>
                    <Link
                        href="/dashboard/builder"
                        className="mt-6 inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        Open website builder <ArrowUpRight className="size-4" />
                    </Link>
                </div>
                <EmptyState
                    icon={Rocket}
                    title={
                        website?.has_unpublished_changes
                            ? 'Changes ready to publish'
                            : 'Live site is up to date'
                    }
                    description={
                        website?.has_unpublished_changes
                            ? 'Preview the signed draft, review it, and publish a new immutable website version.'
                            : 'Open Publishing to preview the site, inspect version history, or restore an earlier version.'
                    }
                    action={
                        <Link
                            href="/dashboard/publishing"
                            className="bg-brand-700 inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            Manage publishing <ArrowUpRight className="size-4" />
                        </Link>
                    }
                />
            </div>
            <Link
                href="/dashboard/design"
                className="bg-brand-700 mt-6 inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
            >
                Customize design <ArrowUpRight className="size-4" />
            </Link>
        </DashboardLayout>
    );
}

function Metric({ icon: Icon, label, value }: { icon: typeof Globe2; label: string; value: string }) {
    return (
        <div className="rounded-2xl border bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between">
                <p className="text-sm font-medium text-slate-500">{label}</p>
                <Icon className="text-brand-700 size-5" />
            </div>
            <p className="mt-4 text-2xl font-bold capitalize">{value.replace('_', ' ')}</p>
        </div>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl bg-slate-50 p-4">
            <p className="text-xs font-semibold tracking-wide text-slate-400 uppercase">{label}</p>
            <p className="mt-2 text-sm font-semibold capitalize">{value}</p>
        </div>
    );
}
