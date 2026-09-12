import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { BreadcrumbItem } from '../types';

export function Breadcrumbs({ items }: { items: BreadcrumbItem[] }) {
    return (
        <nav aria-label="Breadcrumb" className="flex items-center gap-1 text-sm text-slate-500">
            {items.map((item, index) => (
                <span key={`${item.label}-${index}`} className="flex items-center gap-1">
                    {index > 0 && <ChevronRight className="size-4" aria-hidden="true" />}
                    {item.href ? (
                        <Link href={item.href} className="hover:text-slate-950">
                            {item.label}
                        </Link>
                    ) : (
                        <span aria-current="page" className="font-medium text-slate-700">
                            {item.label}
                        </span>
                    )}
                </span>
            ))}
        </nav>
    );
}
