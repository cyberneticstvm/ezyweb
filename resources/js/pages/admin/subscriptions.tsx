import { Head, router, useForm } from '@inertiajs/react';
import { CalendarPlus, CreditCard, Search, ShieldAlert } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { SuperAdminLayout } from '../../layouts/super-admin-layout';

interface Plan {
    id: number;
    name: string;
    code: string;
    annual_price?: string;
    currency?: string;
}
interface Payment {
    public_id: string;
    amount: string;
    currency: string;
    status: string;
    transaction_reference: string;
    paid_at: string | null;
}
interface Subscription {
    public_id: string;
    status: string;
    starts_at: string;
    trial_ends_at: string | null;
    current_period_ends_at: string | null;
    grace_ends_at: string | null;
    tenant: { id: number; name: string; contact_email: string } | null;
    plan: Plan | null;
    payments: Payment[];
}
interface Props {
    subscriptions: {
        data: Subscription[];
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    plans: Plan[];
    statuses: string[];
    filters: { search: string; status: string };
}
const date = (value: string | null) => (value ? new Date(value).toLocaleDateString('en-IN') : '—');

export default function AdminSubscriptions({ subscriptions, plans, statuses, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get('/admin/subscriptions', { search, status }, { preserveState: true });
    };
    return (
        <SuperAdminLayout
            title="Subscriptions"
            description="Manage Ezyweb annual plans, renewals, payments, grace periods, and suspensions."
        >
            <Head title="Admin subscriptions" />
            <form
                onSubmit={submit}
                className="flex flex-wrap gap-3 rounded-2xl border bg-white p-4 shadow-sm"
            >
                <input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search tenant"
                    className="min-w-56 flex-1 rounded-xl border px-3 py-2.5 text-sm"
                />
                <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value)}
                    className="rounded-xl border px-3 py-2.5 text-sm"
                >
                    <option value="">All statuses</option>
                    {statuses.map((item) => (
                        <option key={item}>{item}</option>
                    ))}
                </select>
                <button className="bg-brand-700 inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white">
                    <Search className="size-4" />
                    Filter
                </button>
            </form>
            <p className="mt-5 text-sm text-slate-500">{subscriptions.total} subscription(s)</p>
            <div className="mt-4 space-y-5">
                {subscriptions.data.map((subscription) => (
                    <SubscriptionCard
                        key={subscription.public_id}
                        subscription={subscription}
                        plans={plans}
                        statuses={statuses}
                    />
                ))}
            </div>
            {!subscriptions.data.length && (
                <div className="mt-4 rounded-2xl border bg-white p-12 text-center text-sm text-slate-500">
                    No subscriptions match this view.
                </div>
            )}
            <div className="mt-6 flex justify-between text-sm">
                {subscriptions.prev_page_url ? (
                    <button onClick={() => router.get(subscriptions.prev_page_url!)}>Previous</button>
                ) : (
                    <span />
                )}
                {subscriptions.next_page_url && (
                    <button onClick={() => router.get(subscriptions.next_page_url!)}>Next</button>
                )}
            </div>
        </SuperAdminLayout>
    );
}

function SubscriptionCard({
    subscription,
    plans,
    statuses,
}: {
    subscription: Subscription;
    plans: Plan[];
    statuses: string[];
}) {
    const plan = useForm({ plan_id: subscription.plan?.id ?? plans[0]?.id ?? 0 });
    const extension = useForm({ days: 365 });
    const state = useForm({ status: subscription.status });
    const payment = useForm({
        amount: subscription.plan?.annual_price ?? '',
        currency: subscription.plan?.currency ?? 'INR',
        transaction_reference: '',
        paid_at: new Date().toISOString().slice(0, 10),
        extend_days: 365,
    });
    return (
        <article className="rounded-2xl border bg-white shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-4 border-b p-5">
                <div>
                    <h2 className="font-semibold">{subscription.tenant?.name ?? 'Deleted tenant'}</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        {subscription.tenant?.contact_email} · {subscription.plan?.name}
                    </p>
                </div>
                <span
                    className={`rounded-full px-3 py-1 text-xs font-bold capitalize ${['active', 'trialing'].includes(subscription.status) ? 'bg-emerald-100 text-emerald-700' : subscription.status === 'grace_period' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}`}
                >
                    {subscription.status.replace('_', ' ')}
                </span>
            </div>
            <div className="grid gap-5 p-5 lg:grid-cols-4">
                <div>
                    <p className="text-xs font-semibold text-slate-500 uppercase">Period ends</p>
                    <p className="mt-2 text-sm">{date(subscription.current_period_ends_at)}</p>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        plan.put(`/admin/subscriptions/${subscription.public_id}/plan`, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <Label text="Change plan" />
                    <select
                        value={plan.data.plan_id}
                        onChange={(e) => plan.setData('plan_id', Number(e.target.value))}
                        className="w-full rounded-xl border px-3 py-2 text-sm"
                    >
                        {plans.map((item) => (
                            <option value={item.id} key={item.id}>
                                {item.name}
                            </option>
                        ))}
                    </select>
                    <SmallButton text="Apply plan" />
                </form>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        extension.post(`/admin/subscriptions/${subscription.public_id}/extend`, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <Label text="Extend days" />
                    <input
                        type="number"
                        value={extension.data.days}
                        onChange={(e) => extension.setData('days', Number(e.target.value))}
                        className="w-full rounded-xl border px-3 py-2 text-sm"
                    />
                    <SmallButton icon={CalendarPlus} text="Extend" />
                </form>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        state.put(`/admin/subscriptions/${subscription.public_id}/status`, {
                            preserveScroll: true,
                        });
                    }}
                >
                    <Label text="Lifecycle status" />
                    <select
                        value={state.data.status}
                        onChange={(e) => state.setData('status', e.target.value)}
                        className="w-full rounded-xl border px-3 py-2 text-sm"
                    >
                        {statuses.map((item) => (
                            <option key={item}>{item}</option>
                        ))}
                    </select>
                    <SmallButton icon={ShieldAlert} text="Update status" />
                </form>
            </div>
            <details className="border-t p-5">
                <summary className="cursor-pointer text-sm font-semibold">Record annual payment</summary>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        payment.post(`/admin/subscriptions/${subscription.public_id}/payments`, {
                            preserveScroll: true,
                            onSuccess: () => payment.reset('transaction_reference'),
                        });
                    }}
                    className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5"
                >
                    <input
                        type="number"
                        step="0.01"
                        value={payment.data.amount}
                        onChange={(e) => payment.setData('amount', e.target.value)}
                        placeholder="Amount"
                        className="rounded-xl border px-3 py-2 text-sm"
                    />
                    <input
                        value={payment.data.currency}
                        onChange={(e) => payment.setData('currency', e.target.value.toUpperCase())}
                        className="rounded-xl border px-3 py-2 text-sm"
                    />
                    <input
                        value={payment.data.transaction_reference}
                        onChange={(e) => payment.setData('transaction_reference', e.target.value)}
                        placeholder="Transaction reference"
                        className="rounded-xl border px-3 py-2 text-sm"
                    />
                    <input
                        type="date"
                        value={payment.data.paid_at}
                        onChange={(e) => payment.setData('paid_at', e.target.value)}
                        className="rounded-xl border px-3 py-2 text-sm"
                    />
                    <button className="bg-brand-700 inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-white">
                        <CreditCard className="size-4" />
                        Record + renew
                    </button>
                </form>
                {Object.values(payment.errors).map((error) => (
                    <p key={error} className="mt-2 text-xs text-red-600">
                        {error}
                    </p>
                ))}
            </details>
        </article>
    );
}
function Label({ text }: { text: string }) {
    return <label className="mb-1.5 block text-xs font-semibold text-slate-500">{text}</label>;
}
function SmallButton({ text, icon: Icon }: { text: string; icon?: typeof CalendarPlus }) {
    return (
        <button className="mt-2 inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold">
            {Icon && <Icon className="size-3.5" />}
            {text}
        </button>
    );
}
