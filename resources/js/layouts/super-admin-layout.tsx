import type { PropsWithChildren } from 'react';
import { AppShell } from '../components/app-shell';
export function SuperAdminLayout({
    children,
    title,
    description,
}: PropsWithChildren<{ title: string; description?: string }>) {
    return (
        <AppShell
            area="admin"
            title={title}
            description={description}
            breadcrumbs={[{ label: 'Platform admin' }]}
        >
            {children}
        </AppShell>
    );
}
