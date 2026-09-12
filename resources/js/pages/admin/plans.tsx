import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, Power, Save, SlidersHorizontal } from 'lucide-react';
import type { FormEvent } from 'react';
import { useConfirm } from '../../components/confirm-provider';
import { SuperAdminLayout } from '../../layouts/super-admin-layout';

interface FeatureDefinition {
    key: string;
    type: 'limit' | 'boolean';
    label: string;
    description: string;
    max?: number;
}

interface Plan {
    public_id: string;
    code: string;
    name: string;
    description: string | null;
    annual_price: string;
    currency: string;
    trial_days: number;
    grace_days: number;
    retention_days: number;
    sort_order: number;
    is_active: boolean;
    is_featured: boolean;
    subscriptions_count: number;
    features: Record<string, boolean | number>;
}

interface PlanForm {
    name: string;
    description: string;
    annual_price: string;
    currency: string;
    trial_days: number;
    grace_days: number;
    retention_days: number;
    sort_order: number;
    is_active: boolean;
    is_featured: boolean;
    features: Record<string, boolean | number>;
}

interface Props {
    plans: Plan[];
    featureDefinitions: FeatureDefinition[];
}

export default function Plans({ plans, featureDefinitions }: Props) {
    return (
        <SuperAdminLayout
            title="Plans"
            description="Control Ezyweb pricing, lifecycle defaults, availability, and feature entitlements. Changes apply immediately to subscriptions using the plan."
        >
            <Head title="Admin plans" />
            <div className="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Numeric limits use <strong>0</strong> to disable that capability. Turning a plan off removes
                it from new selections but does not delete or reassign existing subscriptions.
            </div>
            <div className="space-y-6">
                {plans.map((plan) => (
                    <PlanEditor key={plan.public_id} plan={plan} featureDefinitions={featureDefinitions} />
                ))}
            </div>
        </SuperAdminLayout>
    );
}

function PlanEditor({ plan, featureDefinitions }: { plan: Plan; featureDefinitions: FeatureDefinition[] }) {
    const confirm = useConfirm();
    const form = useForm<PlanForm>({
        name: plan.name,
        description: plan.description ?? '',
        annual_price: plan.annual_price,
        currency: plan.currency,
        trial_days: plan.trial_days,
        grace_days: plan.grace_days,
        retention_days: plan.retention_days,
        sort_order: plan.sort_order,
        is_active: plan.is_active,
        is_featured: plan.is_featured,
        features: { ...plan.features },
    });

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (
            plan.is_active &&
            !form.data.is_active &&
            !(await confirm({
                title: `Disable ${plan.name}?`,
                description:
                    'It will disappear from new plan selections. Existing subscriptions keep this plan and its configured features.',
                confirmLabel: 'Disable plan',
                destructive: true,
            }))
        ) {
            return;
        }

        form.put(`/admin/plans/${plan.public_id}`, { preserveScroll: true });
    };

    const setFeature = (key: string, value: boolean | number) => {
        form.setData('features', { ...form.data.features, [key]: value });
    };

    const limitFeatures = featureDefinitions.filter((feature) => feature.type === 'limit');
    const booleanFeatures = featureDefinitions.filter((feature) => feature.type === 'boolean');

    return (
        <form
            onSubmit={(event) => void submit(event)}
            className="overflow-hidden rounded-2xl border bg-white shadow-sm"
        >
            <div className="flex flex-wrap items-start justify-between gap-4 border-b p-5 sm:p-6">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="text-xl font-bold text-slate-950">{plan.name}</h2>
                        <span className="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-[11px] text-slate-600">
                            {plan.code}
                        </span>
                        {plan.is_featured && (
                            <span className="rounded-full bg-teal-100 px-2.5 py-1 text-[11px] font-semibold text-teal-800">
                                Featured
                            </span>
                        )}
                    </div>
                    <p className="mt-2 text-sm text-slate-500">
                        {plan.subscriptions_count} active or historical subscription(s)
                    </p>
                </div>
                <span
                    className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold ${form.data.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}`}
                >
                    <Power className="size-3.5" /> {form.data.is_active ? 'Available' : 'Unavailable'}
                </span>
            </div>

            <div className="grid gap-6 p-5 sm:p-6 xl:grid-cols-[0.8fr_1.2fr]">
                <section aria-labelledby={`${plan.code}-details`}>
                    <div className="mb-4 flex items-center gap-2">
                        <SlidersHorizontal className="text-brand-700 size-5" aria-hidden="true" />
                        <h3 id={`${plan.code}-details`} className="font-semibold">
                            Pricing and lifecycle
                        </h3>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Plan name" wide>
                            <input
                                className="input"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                required
                            />
                        </Field>
                        <Field label="Description" wide>
                            <textarea
                                className="input min-h-24"
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                                maxLength={1000}
                            />
                        </Field>
                        <Field label="Annual price">
                            <input
                                className="input"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.annual_price}
                                onChange={(event) => form.setData('annual_price', event.target.value)}
                                required
                            />
                        </Field>
                        <Field label="Currency">
                            <input
                                className="input uppercase"
                                value={form.data.currency}
                                onChange={(event) =>
                                    form.setData('currency', event.target.value.toUpperCase())
                                }
                                maxLength={3}
                                required
                            />
                        </Field>
                        <NumberField
                            label="Trial days"
                            value={form.data.trial_days}
                            max={365}
                            onChange={(value) => form.setData('trial_days', value)}
                        />
                        <NumberField
                            label="Grace days"
                            value={form.data.grace_days}
                            max={365}
                            onChange={(value) => form.setData('grace_days', value)}
                        />
                        <NumberField
                            label="Retention days"
                            value={form.data.retention_days}
                            min={1}
                            max={3650}
                            onChange={(value) => form.setData('retention_days', value)}
                        />
                        <NumberField
                            label="Display order"
                            value={form.data.sort_order}
                            max={65535}
                            onChange={(value) => form.setData('sort_order', value)}
                        />
                    </div>
                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        <Toggle
                            label="Plan available"
                            description="Allow new selections and trials."
                            checked={form.data.is_active}
                            onChange={(checked) => form.setData('is_active', checked)}
                        />
                        <Toggle
                            label="Featured plan"
                            description="Highlight this plan as recommended."
                            checked={form.data.is_featured}
                            onChange={(checked) => form.setData('is_featured', checked)}
                        />
                    </div>
                </section>

                <section aria-labelledby={`${plan.code}-features`}>
                    <div className="mb-4 flex items-center gap-2">
                        <CheckCircle2 className="text-brand-700 size-5" aria-hidden="true" />
                        <h3 id={`${plan.code}-features`} className="font-semibold">
                            Feature limits and availability
                        </h3>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {limitFeatures.map((feature) => (
                            <label key={feature.key} className="rounded-xl border bg-slate-50 p-3">
                                <span className="text-sm font-semibold text-slate-800">{feature.label}</span>
                                <span className="mt-1 block min-h-10 text-xs leading-5 text-slate-500">
                                    {feature.description}
                                </span>
                                <input
                                    className="input mt-2 bg-white"
                                    type="number"
                                    min="0"
                                    max={feature.max}
                                    value={Number(form.data.features[feature.key] ?? 0)}
                                    onChange={(event) => setFeature(feature.key, Number(event.target.value))}
                                    required
                                />
                            </label>
                        ))}
                    </div>
                    <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {booleanFeatures.map((feature) => (
                            <Toggle
                                key={feature.key}
                                label={feature.label}
                                description={feature.description}
                                checked={Boolean(form.data.features[feature.key])}
                                onChange={(checked) => setFeature(feature.key, checked)}
                            />
                        ))}
                    </div>
                </section>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 border-t bg-slate-50 px-5 py-4 sm:px-6">
                <div aria-live="polite">
                    {Object.values(form.errors).map((error) => (
                        <p key={error} className="text-sm text-red-600">
                            {error}
                        </p>
                    ))}
                </div>
                <button
                    disabled={form.processing}
                    className="bg-brand-700 hover:bg-brand-600 inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                >
                    <Save className="size-4" aria-hidden="true" />
                    {form.processing ? 'Saving…' : 'Save plan'}
                </button>
            </div>
        </form>
    );
}

function Field({
    label,
    wide = false,
    children,
}: {
    label: string;
    wide?: boolean;
    children: React.ReactNode;
}) {
    return (
        <label className={wide ? 'sm:col-span-2' : undefined}>
            <span className="mb-1.5 block text-xs font-semibold text-slate-600">{label}</span>
            {children}
        </label>
    );
}

function NumberField({
    label,
    value,
    min = 0,
    max,
    onChange,
}: {
    label: string;
    value: number;
    min?: number;
    max: number;
    onChange: (value: number) => void;
}) {
    return (
        <Field label={label}>
            <input
                className="input"
                type="number"
                min={min}
                max={max}
                value={value}
                onChange={(event) => onChange(Number(event.target.value))}
                required
            />
        </Field>
    );
}

function Toggle({
    label,
    description,
    checked,
    onChange,
}: {
    label: string;
    description: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <label className="flex cursor-pointer gap-3 rounded-xl border p-3">
            <input
                type="checkbox"
                className="accent-brand-700 mt-1 size-4"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
            />
            <span>
                <span className="block text-sm font-semibold text-slate-800">{label}</span>
                <span className="mt-1 block text-xs leading-5 text-slate-500">{description}</span>
            </span>
        </label>
    );
}
