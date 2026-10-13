import { Head, useForm } from '@inertiajs/react';
import { Database, Globe2, Save, Settings, ShieldCheck } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { SuperAdminLayout } from '../../layouts/super-admin-layout';

interface SettingsForm {
    platform_name: string;
    support_email: string;
    registrations_enabled: boolean;
    default_country_code: string;
    default_timezone: string;
    default_locale: string;
}

interface Props {
    settings: Omit<SettingsForm, 'support_email'> & { support_email: string | null };
    options: {
        countries: Record<string, string>;
        timezones: Record<string, string>;
        languages: Record<string, string>;
    };
    environment: Record<string, string>;
}

export default function PlatformSettings({ settings, options, environment }: Props) {
    const form = useForm<SettingsForm>({ ...settings, support_email: settings.support_email ?? '' });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/admin/settings', { preserveScroll: true });
    };

    return (
        <SuperAdminLayout
            title="Platform settings"
            description="Manage customer registration and workspace defaults. Infrastructure values remain controlled by the server environment."
        >
            <Head title="Platform settings" />
            <form onSubmit={submit} className="overflow-hidden rounded-2xl border bg-white shadow-sm">
                <div className="flex items-center gap-3 border-b p-5 sm:p-6">
                    <span className="bg-brand-50 text-brand-700 grid size-10 place-items-center rounded-xl">
                        <Settings className="size-5" />
                    </span>
                    <div>
                        <h2 className="font-semibold text-slate-950">Customer onboarding</h2>
                        <p className="mt-1 text-sm text-slate-500">These values are stored by Ezyweb and apply to new customers.</p>
                    </div>
                </div>
                <div className="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <Field label="Platform name" error={form.errors.platform_name}>
                        <input className="input" value={form.data.platform_name} onChange={(event) => form.setData('platform_name', event.target.value)} required />
                    </Field>
                    <Field label="Support email" error={form.errors.support_email}>
                        <input className="input" type="email" value={form.data.support_email} onChange={(event) => form.setData('support_email', event.target.value)} placeholder="support@ezyweb.in" />
                    </Field>
                    <Field label="Default country" error={form.errors.default_country_code}>
                        <select className="input" value={form.data.default_country_code} onChange={(event) => form.setData('default_country_code', event.target.value)}>
                            {Object.entries(options.countries).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                    </Field>
                    <Field label="Default timezone" error={form.errors.default_timezone}>
                        <select className="input" value={form.data.default_timezone} onChange={(event) => form.setData('default_timezone', event.target.value)}>
                            {Object.entries(options.timezones).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                    </Field>
                    <Field label="Default language" error={form.errors.default_locale}>
                        <select className="input" value={form.data.default_locale} onChange={(event) => form.setData('default_locale', event.target.value)}>
                            {Object.entries(options.languages).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                    </Field>
                    <label className="flex items-start gap-3 rounded-xl border p-4 sm:self-end">
                        <input
                            type="checkbox"
                            checked={form.data.registrations_enabled}
                            onChange={(event) => form.setData('registrations_enabled', event.target.checked)}
                            className="accent-brand-700 mt-1 size-4"
                        />
                        <span>
                            <span className="block text-sm font-semibold">Allow new registrations</span>
                            <span className="mt-1 block text-xs leading-5 text-slate-500">When disabled, existing customers can still sign in.</span>
                        </span>
                    </label>
                </div>
                <div className="flex items-center justify-between gap-4 border-t bg-slate-50 px-5 py-4 sm:px-6">
                    <p className="text-xs text-slate-500">All changes are recorded in the platform audit log.</p>
                    <button disabled={form.processing} className="bg-brand-700 inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                        <Save className="size-4" /> Save settings
                    </button>
                </div>
            </form>

            <section className="mt-6 rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
                <div className="flex items-center gap-2">
                    <ShieldCheck className="text-brand-700 size-5" />
                    <h2 className="font-semibold text-slate-950">Environment configuration</h2>
                </div>
                <p className="mt-2 text-sm leading-6 text-slate-600">Domains and service drivers are security-sensitive and remain managed in the production <code>.env</code> file.</p>
                <dl className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {Object.entries(environment).map(([key, value]) => (
                        <div key={key} className="rounded-xl bg-slate-50 p-4">
                            <dt className="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                {key.includes('domain') ? <Globe2 className="size-3.5" /> : <Database className="size-3.5" />}
                                {key.replaceAll('_', ' ')}
                            </dt>
                            <dd className="mt-2 break-all text-sm font-medium">{value || 'Not configured'}</dd>
                        </div>
                    ))}
                </dl>
            </section>
        </SuperAdminLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-medium">{label}</span>
            {children}
            {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
        </label>
    );
}
