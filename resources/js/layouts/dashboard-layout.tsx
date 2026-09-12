import type { PropsWithChildren } from 'react';
import { AppShell } from '../components/app-shell';
import type { BreadcrumbItem } from '../types';

interface Props extends PropsWithChildren {
    title: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
}
export function DashboardLayout({
    children,
    title,
    description,
    breadcrumbs = [{ label: 'Dashboard' }],
}: Props) {
    return (
        <AppShell title={title} description={description} breadcrumbs={breadcrumbs}>
            {children}
        </AppShell>
    );
}
