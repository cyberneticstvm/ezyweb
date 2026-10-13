import { Head, router, useForm } from '@inertiajs/react';
import { Building2, ExternalLink, Search, ShieldCheck, ShieldOff, Users } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { useConfirm } from '../../components/confirm-provider';
import { SuperAdminLayout } from '../../layouts/super-admin-layout';

interface Customer {
    id: number;
    name: string;
    slug: string;
    business_category: string;
    country_code: string;
    timezone: string;
    contact_email: string;
    contact_phone: string | null;
    status: 'active' | 'suspended';
    created_at: string;
    memberships_count: number;
    websites_count: number;
    owner: { name: string; email: string; phone: string | null } | null;
    hostname: string | null;
    subscription: { status: string; plan: string | null; ends_at: string | null } | null;
}

interface Props {
    customers: {
        data: Customer[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    statuses: string[];
    filters: { search: string; status: string };
}

const date = (value: string | null) => (value ? new Date(value).toLocaleDateString('en-IN') : '—');

export default function AdminCustomers({ customers, statuses, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get('/admin/customers', { search, status }, { preserveState: true });
    };

    return (
        <SuperAdminLayout
            title="Customers"
            description="View every Ezyweb customer workspace, its owner, website, subscription, and access status."
        >
            <Head title="Admin customers" />
            <form onSubmit={submit} className="flex flex-wrap gap-3 rounded-2xl border bg-white p-4 shadow-sm">
                <input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search name, email, owner, or slug"
                    className="min-w-64 flex-1 rounded-xl border px-3 py-2.5 text-sm"
                />
                <select
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    className="rounded-xl border px-3 py-2.5 text-sm capitalize"
                >
                    <option value="">All statuses</option>
                    {statuses.map((item) => (
                        <option key={item} value={item}>
                            {item}
                        </option>
                    ))}
                </select>
                <button className="bg-brand-700 inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white">
                    <Search className="size-4" /> Filter
                </button>
            </form>

            <p className="mt-5 text-sm text-slate-500">{customers.total} customer(s)</p>
            <div className="mt-4 space-y-4">
                {customers.data.map((customer) => (
                    <CustomerCard key={customer.id} customer={customer} />
                ))}
            </div>
            {!customers.data.length && (
                <div className="mt-4 rounded-2xl border bg-white p-12 text-center text-sm text-slate-500">
                    No customers match this view.
                </div>
            )}
            <div className="mt-6 flex justify-between text-sm">
                {customers.prev_page_url ? (
                    <button onClick={() => router.get(customers.prev_page_url!)}>Previous</button>
                ) : (
                    <span />
                )}
                {customers.next_page_url && (
                    <button onClick={() => router.get(customers.next_page_url!)}>Next</button>
                )}
            </div>
        </SuperAdminLayout>
    );
}

function CustomerCard({ customer }: { customer: Customer }) {
    const confirm = useConfirm();
    const form = useForm({ status: customer.status === 'active' ? 'suspended' : 'active' });
    const activate = customer.status === 'suspended';
    const updateStatus = async () => {
        if (
            !(await confirm({
                title: `${activate ? 'Activate' : 'Suspend'} ${customer.name}?`,
                description: activate
                    ? 'The customer can access the dashboard and published website again.'
                    : 'This immediately blocks the customer dashboard and public website until reactivated.',
                confirmLabel: activate ? 'Activate customer' : 'Suspend customer',
                destructive: !activate,
            }))
        )
            return;

        form.put(`/admin/customers/${customer.id}/status`, { preserveScroll: true });
    };

    return (
        <article className="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-4 border-b p-5">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="font-semibold text-slate-950">{customer.name}</h2>
                        <span
                            className={`rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${customer.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'}`}
                        >
                            {customer.status}
                        </span>
                    </div>
                    <p className="mt-1 text-sm text-slate-500">
                        {customer.contact_email} · {customer.slug}
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() => void updateStatus()}
                    disabled={form.processing}
                    className={`inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold disabled:opacity-50 ${activate ? 'text-emerald-700' : 'text-red-700'}`}
                >
                    {activate ? <ShieldCheck className="size-4" /> : <ShieldOff className="size-4" />}
                    {activate ? 'Activate' : 'Suspend'}
                </button>
            </div>
            <div className="grid gap-5 p-5 sm:grid-cols-2 xl:grid-cols-4">
                <Detail label="Owner" value={customer.owner?.name ?? 'No owner'} supporting={customer.owner?.email} />
                <Detail
                    label="Workspace"
                    value={`${customer.websites_count} website(s)`}
                    supporting={`${customer.memberships_count} member(s)`}
                    icon={Users}
                />
                <Detail
                    label="Subscription"
                    value={customer.subscription?.plan ?? 'No plan'}
                    supporting={customer.subscription ? `${customer.subscription.status.replace('_', ' ')} · ends ${date(customer.subscription.ends_at)}` : undefined}
                />
                <div>
                    <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">Primary website</p>
                    {customer.hostname ? (
                        <a
                            href={`https://${customer.hostname}`}
                            target="_blank"
                            rel="noreferrer"
                            className="text-brand-700 mt-2 inline-flex items-center gap-1 text-sm font-medium"
                        >
                            {customer.hostname} <ExternalLink className="size-3.5" />
                        </a>
                    ) : (
                        <p className="mt-2 text-sm">No domain</p>
                    )}
                    <p className="mt-1 text-xs text-slate-500">Joined {date(customer.created_at)}</p>
                </div>
            </div>
        </article>
    );
}

function Detail({
    label,
    value,
    supporting,
    icon: Icon = Building2,
}: {
    label: string;
    value: string;
    supporting?: string;
    icon?: typeof Building2;
}) {
    return (
        <div>
            <p className="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <Icon className="size-3.5" /> {label}
            </p>
            <p className="mt-2 text-sm font-medium capitalize">{value}</p>
            {supporting && <p className="mt-1 break-all text-xs text-slate-500">{supporting}</p>}
        </div>
    );
}
