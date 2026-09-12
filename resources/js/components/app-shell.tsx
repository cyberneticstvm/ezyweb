import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Building2,
    ChevronDown,
    Globe2,
    LayoutDashboard,
    PanelsTopLeft,
    Rocket,
    Palette,
    LogOut,
    Menu,
    Settings,
    ShieldCheck,
    X,
    Rows3,
    MessageSquareText,
    Images,
    SearchCheck,
    CreditCard,
    PackageCheck,
} from 'lucide-react';
import { useEffect, useRef, useState, type KeyboardEvent, type PropsWithChildren } from 'react';

import { cn, initials } from '../lib/utils';
import type { AppPageProps, BreadcrumbItem } from '../types';
import { AppLogo } from './app-logo';
import { Breadcrumbs } from './breadcrumbs';
import { useConfirm } from './confirm-provider';

interface AppShellProps extends PropsWithChildren {
    title: string;
    description?: string;
    breadcrumbs: BreadcrumbItem[];
    area?: 'tenant' | 'admin';
}

const tenantNav = [
    { label: 'Overview', href: '/dashboard', icon: LayoutDashboard },
    { label: 'Website builder', href: '/dashboard/builder', icon: PanelsTopLeft },
    { label: 'Design', href: '/dashboard/design', icon: Palette },
    { label: 'Publishing', href: '/dashboard/publishing', icon: Rocket },
    { label: 'Contact & enquiries', href: '/dashboard/contact', icon: MessageSquareText },
    { label: 'Media library', href: '/dashboard/media', icon: Images },
    { label: 'SEO', href: '/dashboard/seo', icon: SearchCheck },
    { label: 'Subscription', href: '/dashboard/subscription', icon: CreditCard },
    { label: 'Websites', href: '#', icon: Globe2, disabled: true },
    { label: 'Settings', href: '#', icon: Settings, disabled: true },
];

const contentNav = [
    ['Services', 'services'],
    ['Products', 'products'],
    ['Brands', 'brands'],
    ['Testimonials', 'testimonials'],
    ['Portfolio', 'portfolio'],
    ['Gallery', 'gallery'],
    ['Team', 'team'],
    ['FAQ', 'faq'],
    ['Blog', 'blog'],
    ['News', 'news'],
    ['Events', 'events'],
    ['Careers', 'careers'],
];

const adminNav = [
    { label: 'Platform overview', href: '/admin', icon: ShieldCheck },
    { label: 'Subscriptions', href: '/admin/subscriptions', icon: CreditCard },
    { label: 'Plans', href: '/admin/plans', icon: PackageCheck },
    { label: 'Customers', href: '#', icon: Building2, disabled: true },
    { label: 'Platform settings', href: '#', icon: Settings, disabled: true },
];

export function AppShell({ children, title, description, breadcrumbs, area = 'tenant' }: AppShellProps) {
    const { auth, tenant } = usePage<AppPageProps>().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const mobileDialogRef = useRef<HTMLElement>(null);
    const confirm = useConfirm();
    const navigation = area === 'admin' ? adminNav : tenantNav;
    const user = auth.user;

    useEffect(() => {
        if (!mobileOpen) return;

        const previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        mobileDialogRef.current?.querySelector<HTMLElement>('a, button')?.focus();

        const closeOnEscape = (event: globalThis.KeyboardEvent) => {
            if (event.key === 'Escape') setMobileOpen(false);
        };
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('keydown', closeOnEscape);
            document.body.style.overflow = previousOverflow;
            previousFocus?.focus();
        };
    }, [mobileOpen]);

    const containMobileFocus = (event: KeyboardEvent<HTMLElement>) => {
        if (event.key !== 'Tab') return;
        const focusable = Array.from(
            event.currentTarget.querySelectorAll<HTMLElement>('a[href], button:not([disabled])'),
        ).filter((element) => element.getAttribute('aria-disabled') !== 'true');
        if (focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    const logout = async () => {
        if (
            await confirm({
                title: 'Sign out of Ezyweb?',
                description: 'You will need to sign in again to access your workspace.',
                confirmLabel: 'Sign out',
            })
        ) {
            router.post('/logout');
        }
    };

    const sidebar = (
        <div className="flex h-full flex-col overflow-y-auto bg-slate-950 text-slate-300">
            <div className="flex h-18 items-center border-b border-white/10 px-5">
                <div className="[&_span:last-child]:text-white">
                    <AppLogo />
                </div>
            </div>
            <div className="px-4 pt-5">
                <p className="px-3 text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                    {area === 'admin' ? 'Platform admin' : (tenant?.name ?? 'Workspace')}
                </p>
                <nav className="mt-3 space-y-1" aria-label="Primary navigation">
                    {navigation.map(({ label, href, icon: Icon, disabled }) => (
                        <Link
                            key={label}
                            href={href}
                            onClick={(event) => {
                                if (disabled) event.preventDefault();
                                else setMobileOpen(false);
                            }}
                            aria-disabled={disabled}
                            className={cn(
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                location.pathname === href
                                    ? 'bg-white/10 text-white'
                                    : 'hover:bg-white/5 hover:text-white',
                                disabled && 'cursor-not-allowed opacity-45',
                            )}
                        >
                            <Icon className="size-4.5" aria-hidden="true" />
                            {label}
                            {disabled && <span className="ml-auto text-[10px] uppercase">Soon</span>}
                        </Link>
                    ))}
                </nav>
                {area === 'tenant' && (
                    <>
                        <p className="mt-6 px-3 text-[11px] font-semibold tracking-[0.16em] text-slate-500 uppercase">
                            Content
                        </p>
                        <nav className="mt-2 grid grid-cols-2 gap-1" aria-label="Content modules">
                            {contentNav.map(([label, key]) => {
                                const href = `/dashboard/content/${key}`;
                                return (
                                    <Link
                                        key={key}
                                        href={href}
                                        className={cn(
                                            'flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition hover:bg-white/5 hover:text-white',
                                            location.pathname === href && 'bg-white/10 text-white',
                                        )}
                                    >
                                        <Rows3 className="size-3.5" /> {label}
                                    </Link>
                                );
                            })}
                        </nav>
                    </>
                )}
            </div>
            {area === 'admin' && (
                <Link
                    href="/dashboard"
                    className="mx-4 mt-auto mb-5 rounded-xl border border-white/10 px-3 py-2.5 text-sm hover:bg-white/5"
                >
                    Return to workspace
                </Link>
            )}
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50">
            <a
                href="#dashboard-main"
                className="bg-brand-700 fixed top-3 left-3 z-[100] -translate-y-24 rounded-lg px-4 py-2 text-sm font-semibold text-white focus:translate-y-0"
            >
                Skip to main content
            </a>
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 lg:block">{sidebar}</aside>
            {mobileOpen && (
                <div
                    className="fixed inset-0 z-50 lg:hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Dashboard navigation"
                    onKeyDown={containMobileFocus}
                >
                    <button
                        className="absolute inset-0 bg-slate-950/50"
                        aria-label="Close navigation"
                        onClick={() => setMobileOpen(false)}
                    />
                    <aside
                        ref={mobileDialogRef}
                        id="dashboard-mobile-navigation"
                        className="relative h-full w-72 shadow-2xl"
                    >
                        {sidebar}
                        <button
                            onClick={() => setMobileOpen(false)}
                            className="absolute top-5 right-4 rounded-lg p-2 text-slate-300 hover:bg-white/10"
                            aria-label="Close navigation"
                        >
                            <X className="size-5" />
                        </button>
                    </aside>
                </div>
            )}
            <div className="lg:pl-64">
                <header className="sticky top-0 z-20 flex h-18 items-center border-b bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button
                        onClick={() => setMobileOpen(true)}
                        className="mr-3 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                        aria-label="Open navigation"
                        aria-expanded={mobileOpen}
                        aria-controls="dashboard-mobile-navigation"
                    >
                        <Menu className="size-5" />
                    </button>
                    <div className="min-w-0 flex-1">
                        <Breadcrumbs items={breadcrumbs} />
                    </div>
                    <DropdownMenu.Root>
                        <DropdownMenu.Trigger
                            className="flex items-center gap-2 rounded-xl p-1.5 pr-2 hover:bg-slate-100"
                            aria-label="Open user menu"
                        >
                            <span className="bg-brand-100 text-brand-700 grid size-8 place-items-center rounded-lg text-xs font-bold">
                                {initials(user?.name ?? 'User')}
                            </span>
                            <span className="hidden max-w-36 truncate text-sm font-medium sm:block">
                                {user?.name}
                            </span>
                            <ChevronDown className="size-4 text-slate-500" />
                        </DropdownMenu.Trigger>
                        <DropdownMenu.Portal>
                            <DropdownMenu.Content
                                align="end"
                                sideOffset={8}
                                className="z-50 w-56 rounded-xl border bg-white p-1.5 shadow-xl"
                            >
                                <div className="px-2 py-2">
                                    <p className="truncate text-sm font-medium">{user?.name}</p>
                                    <p className="truncate text-xs text-slate-500">{user?.email}</p>
                                </div>
                                {user?.platform_role === 'super_admin' && area !== 'admin' && (
                                    <DropdownMenu.Item asChild>
                                        <Link
                                            href="/admin"
                                            className="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm outline-none hover:bg-slate-100"
                                        >
                                            <ShieldCheck className="size-4" />
                                            Platform admin
                                        </Link>
                                    </DropdownMenu.Item>
                                )}
                                <DropdownMenu.Separator className="my-1 h-px bg-slate-100" />
                                <DropdownMenu.Item
                                    onSelect={() => void logout()}
                                    className="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm text-red-600 outline-none hover:bg-red-50"
                                >
                                    <LogOut className="size-4" />
                                    Sign out
                                </DropdownMenu.Item>
                            </DropdownMenu.Content>
                        </DropdownMenu.Portal>
                    </DropdownMenu.Root>
                </header>
                <main id="dashboard-main" tabIndex={-1} className="p-4 outline-none sm:p-6 lg:p-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-7">
                            <h1 className="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                {title}
                            </h1>
                            {description && (
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                    {description}
                                </p>
                            )}
                        </div>
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
