import { Link, usePage } from '@inertiajs/react';
import { Blocks } from 'lucide-react';
import type { AppPageProps } from '../types';

export function AppLogo({ compact = false }: { compact?: boolean }) {
    const { app } = usePage<AppPageProps>().props;

    return (
        <Link href="/" className="inline-flex items-center gap-2.5" aria-label={`${app.name} home`}>
            <span className="bg-brand-700 grid size-9 place-items-center rounded-xl text-white shadow-sm">
                <Blocks className="size-5" aria-hidden="true" />
            </span>
            {!compact && <span className="text-xl font-bold tracking-tight text-slate-950">{app.name}</span>}
        </Link>
    );
}
