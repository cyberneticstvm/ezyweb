import { Head } from '@inertiajs/react';
import { CalendarDays, Check, CreditCard, ReceiptText, ShieldCheck } from 'lucide-react';
import { DashboardLayout } from '../../layouts/dashboard-layout';

interface Feature {
    key: string;
    value: boolean | number | string;
    label: string;
}
interface Plan {
    public_id: string;
    code: string;
    name: string;
    description: string;
    annual_price: string;
    currency: string;
    trial_days: number;
    grace_days: number;
    is_featured: boolean;
    features: Feature[];
}
interface Payment {
    public_id: string;
    amount: string;
    currency: string;
    status: string;
    transaction_reference: string;
    paid_at: string | null;
    created_at: string;
}
interface Subscription {
    public_id: string;
    status: string;
    starts_at: string;
    trial_ends_at: string | null;
    current_period_starts_at: string | null;
    current_period_ends_at: string | null;
    grace_ends_at: string | null;
    auto_renew: boolean;
    plan: Plan;
    payments: Payment[];
}
interface Props {
    subscription: Subscription | null;
    plans: Plan[];
}

const money = (amount: string, currency: string) =>
    new Intl.NumberFormat('en-IN', { style: 'currency', currency, maximumFractionDigits: 0 }).format(
        Number(amount),
    );
const date = (value: string | null) =>
    value
        ? new Date(value).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        : '—';

export default function SubscriptionIndex({ subscription, plans }: Props) {
    return (
        <DashboardLayout
            title="Subscription"
            description="Review your annual plan, renewal timeline, included limits, and payment history."
        >
            <Head title="Subscription" />
            {subscription ? (
                <>
                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <Metric icon={ShieldCheck} label="Current plan" value={subscription.plan.name} />
                        <Metric
                            icon={CreditCard}
                            label="Status"
                            value={subscription.status.replace('_', ' ')}
                        />
                        <Metric
                            icon={CalendarDays}
                            label="Renewal due"
                            value={date(subscription.current_period_ends_at)}
                        />
                        <Metric
                            icon={CalendarDays}
                            label="Grace ends"
                            value={date(subscription.grace_ends_at)}
                        />
                    </section>
                    {['grace_period', 'past_due'].includes(subscription.status) && (
                        <div className="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                            <strong>Renewal required.</strong> Your website remains available during the grace
                            period, then it will be suspended without deleting your data.
                        </div>
                    )}
                </>
            ) : (
                <div className="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                    No subscription is assigned to this workspace. Contact Ezyweb support.
                </div>
            )}

            <section className="mt-8">
                <h2 className="text-lg font-semibold">Annual plans</h2>
                <div className="mt-4 grid gap-5 lg:grid-cols-2">
                    {plans.map((plan) => (
                        <article
                            key={plan.code}
                            className={`rounded-2xl border bg-white p-6 shadow-sm ${subscription?.plan.code === plan.code ? 'ring-brand-600 ring-2' : ''}`}
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="text-xl font-bold">{plan.name}</h3>
                                        {subscription?.plan.code === plan.code && (
                                            <span className="bg-brand-100 text-brand-700 rounded-full px-2 py-1 text-xs font-bold">
                                                Current
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-2 text-sm leading-6 text-slate-600">
                                        {plan.description}
                                    </p>
                                </div>
                                <p className="text-right text-2xl font-bold">
                                    {money(plan.annual_price, plan.currency)}
                                    <span className="block text-xs font-normal text-slate-500">per year</span>
                                </p>
                            </div>
                            <ul className="mt-5 grid gap-3 sm:grid-cols-2">
                                {plan.features.map((feature) => (
                                    <li key={feature.key} className="flex gap-2 text-sm">
                                        <Check className="text-brand-700 mt-0.5 size-4 shrink-0" />
                                        {feature.label}
                                    </li>
                                ))}
                            </ul>
                            {subscription?.plan.code !== plan.code && (
                                <p className="mt-5 rounded-xl bg-slate-50 p-3 text-sm text-slate-600">
                                    Contact Ezyweb billing to move to this annual plan.
                                </p>
                            )}
                        </article>
                    ))}
                </div>
            </section>

            <section className="mt-8 overflow-hidden rounded-2xl border bg-white shadow-sm">
                <div className="flex items-center gap-2 border-b p-5">
                    <ReceiptText className="text-brand-700 size-5" />
                    <h2 className="font-semibold">Payment history</h2>
                </div>
                {!subscription?.payments.length ? (
                    <p className="p-10 text-center text-sm text-slate-500">No payments recorded yet.</p>
                ) : (
                    <div className="divide-y">
                        {subscription.payments.map((payment) => (
                            <div
                                key={payment.public_id}
                                className="flex flex-wrap justify-between gap-3 p-5 text-sm"
                            >
                                <div>
                                    <strong>{money(payment.amount, payment.currency)}</strong>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {payment.transaction_reference}
                                    </p>
                                </div>
                                <div className="text-right">
                                    <span className="capitalize">{payment.status}</span>
                                    <p className="mt-1 text-xs text-slate-500">{date(payment.paid_at)}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}

function Metric({ icon: Icon, label, value }: { icon: typeof ShieldCheck; label: string; value: string }) {
    return (
        <div className="rounded-2xl border bg-white p-5 shadow-sm">
            <Icon className="text-brand-700 size-5" />
            <p className="mt-4 text-sm text-slate-500">{label}</p>
            <p className="mt-1 text-lg font-bold capitalize">{value}</p>
        </div>
    );
}
