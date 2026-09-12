import { Head } from '@inertiajs/react';
import { Activity, Building2, ShieldCheck, Users } from 'lucide-react';
import { EmptyState } from '../../components/empty-state';
import { SuperAdminLayout } from '../../layouts/super-admin-layout';
export default function AdminIndex() {
    return (
        <SuperAdminLayout
            title="Platform overview"
            description="Secure operational access for Ezyweb platform administrators."
        >
            <Head title="Platform admin" />
            <div className="grid gap-4 sm:grid-cols-3">
                {[
                    [Users, 'Accounts', '—'],
                    [Building2, 'Tenants', '—'],
                    [Activity, 'System status', 'Foundation ready'],
                ].map(([Icon, label, value]) => (
                    <div key={label as string} className="rounded-2xl border bg-white p-5 shadow-sm">
                        <Icon className="text-brand-700 size-5" />
                        <p className="mt-4 text-sm text-slate-500">{label as string}</p>
                        <p className="mt-1 text-xl font-bold">{value as string}</p>
                    </div>
                ))}
            </div>
            <div className="mt-6">
                <EmptyState
                    icon={ShieldCheck}
                    title="Admin foundation is protected"
                    description="Platform reports and tenant controls will be added in their designated phases. This area is already restricted to the super-admin role."
                />
            </div>
        </SuperAdminLayout>
    );
}
