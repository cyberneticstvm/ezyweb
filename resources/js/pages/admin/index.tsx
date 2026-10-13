import { Head, Link } from '@inertiajs/react';
import { Activity, Building2, Globe2, Users } from 'lucide-react';
import { SuperAdminLayout } from '../../layouts/super-admin-layout';

interface Props {
    metrics: { accounts: number; customers: number; active_customers: number; websites: number };
}

export default function AdminIndex({ metrics }: Props) {
    const cards = [
        { icon: Users, label: 'Accounts', value: metrics.accounts },
        { icon: Building2, label: 'Customers', value: metrics.customers },
        { icon: Activity, label: 'Active customers', value: metrics.active_customers },
        { icon: Globe2, label: 'Websites', value: metrics.websites },
    ];

    return (
        <SuperAdminLayout
            title="Platform overview"
            description="Secure operational access for Ezyweb platform administrators."
        >
            <Head title="Platform admin" />
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {cards.map(({ icon: Icon, label, value }) => (
                    <div key={label} className="rounded-2xl border bg-white p-5 shadow-sm">
                        <Icon className="text-brand-700 size-5" />
                        <p className="mt-4 text-sm text-slate-500">{label}</p>
                        <p className="mt-1 text-xl font-bold">{value}</p>
                    </div>
                ))}
            </div>
            <div className="mt-6 grid gap-4 md:grid-cols-2">
                <Link href="/admin/customers" className="rounded-2xl border bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow">
                    <h2 className="font-semibold text-slate-950">Manage customers</h2>
                    <p className="mt-2 text-sm leading-6 text-slate-600">Search customer workspaces, inspect owners and websites, and control workspace access.</p>
                </Link>
                <Link href="/admin/settings" className="rounded-2xl border bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow">
                    <h2 className="font-semibold text-slate-950">Platform settings</h2>
                    <p className="mt-2 text-sm leading-6 text-slate-600">Manage registration and onboarding defaults and review environment-backed services.</p>
                </Link>
            </div>
        </SuperAdminLayout>
    );
}
