import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
}

export function EmptyState({ icon: Icon, title, description, action }: EmptyStateProps) {
    return (
        <div className="rounded-2xl border border-dashed bg-white px-6 py-12 text-center">
            <span className="bg-brand-50 text-brand-700 mx-auto grid size-12 place-items-center rounded-2xl">
                <Icon className="size-6" aria-hidden="true" />
            </span>
            <h2 className="mt-4 font-semibold text-slate-950">{title}</h2>
            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">{description}</p>
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
