import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useEffect, useRef, useState, type PropsWithChildren } from 'react';
import { AppLogo } from '../components/app-logo';
import type { AppPageProps } from '../types';

export function PublicLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<AppPageProps>().props;
    const [open, setOpen] = useState(false);
    const mobileNavigationRef = useRef<HTMLElement>(null);
    const mobileMenuButtonRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!open) return;
        const menuButton = mobileMenuButtonRef.current;
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpen(false);
        };
        document.addEventListener('keydown', closeOnEscape);
        mobileNavigationRef.current?.querySelector<HTMLElement>('a')?.focus();
        return () => {
            document.removeEventListener('keydown', closeOnEscape);
            menuButton?.focus();
        };
    }, [open]);

    return (
        <div className="min-h-screen bg-white">
            <a
                href="#marketing-main"
                className="bg-brand-700 fixed top-3 left-3 z-[100] -translate-y-24 rounded-lg px-4 py-2 text-sm font-semibold text-white focus:translate-y-0"
            >
                Skip to main content
            </a>
            <header className="sticky top-0 z-40 border-b border-slate-200/70 bg-white/90 backdrop-blur-xl">
                <div className="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <AppLogo />
                    <nav
                        className="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex"
                        aria-label="Main navigation"
                    >
                        <a href="#features" className="hover:text-slate-950">
                            Features
                        </a>
                        <a href="#how-it-works" className="hover:text-slate-950">
                            How it works
                        </a>
                        <a href="#audience" className="hover:text-slate-950">
                            Who it is for
                        </a>
                    </nav>
                    <div className="hidden items-center gap-3 md:flex">
                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="bg-brand-700 hover:bg-brand-600 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                Open dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="px-3 py-2 text-sm font-semibold text-slate-700"
                                >
                                    Sign in
                                </Link>
                                <Link
                                    href="/register"
                                    className="bg-brand-700 hover:bg-brand-600 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
                                >
                                    Start building
                                </Link>
                            </>
                        )}
                    </div>
                    <button
                        ref={mobileMenuButtonRef}
                        onClick={() => setOpen(!open)}
                        className="rounded-lg p-2 text-slate-700 md:hidden"
                        aria-expanded={open}
                        aria-controls="marketing-mobile-navigation"
                        aria-label="Toggle navigation"
                    >
                        {open ? <X className="size-5" /> : <Menu className="size-5" />}
                    </button>
                </div>
                {open && (
                    <nav
                        ref={mobileNavigationRef}
                        id="marketing-mobile-navigation"
                        aria-label="Mobile navigation"
                        className="border-t bg-white px-4 py-4 md:hidden"
                    >
                        <div className="space-y-1">
                            <a href="#features" className="block rounded-lg px-3 py-2">
                                Features
                            </a>
                            <a href="#how-it-works" className="block rounded-lg px-3 py-2">
                                How it works
                            </a>
                        </div>
                        <div className="mt-4 grid grid-cols-2 gap-3">
                            <Link
                                href="/login"
                                className="rounded-xl border px-4 py-2.5 text-center text-sm font-semibold"
                            >
                                Sign in
                            </Link>
                            <Link
                                href="/register"
                                className="bg-brand-700 rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-white"
                            >
                                Start building
                            </Link>
                        </div>
                    </nav>
                )}
            </header>
            <main id="marketing-main" tabIndex={-1} className="outline-none">
                {children}
            </main>
            <footer className="border-t bg-slate-950 text-slate-300">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-10 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                    <div className="[&_span:last-child]:text-white">
                        <AppLogo />
                    </div>
                    <p className="text-sm">Simple, professional websites for growing businesses.</p>
                    <p className="text-sm">© {new Date().getFullYear()} Ezyweb</p>
                </div>
            </footer>
        </div>
    );
}
