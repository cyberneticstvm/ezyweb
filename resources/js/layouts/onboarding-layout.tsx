import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { AppLogo } from '../components/app-logo';

export function OnboardingLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-slate-50">
            <header className="border-b bg-white">
                <div className="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <AppLogo />
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="text-sm font-medium text-slate-600 hover:text-slate-950"
                    >
                        Sign out
                    </Link>
                </div>
            </header>
            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">{children}</main>
        </div>
    );
}
