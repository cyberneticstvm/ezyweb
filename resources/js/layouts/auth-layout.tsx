import { Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { AppLogo } from '../components/app-logo';

export function AuthLayout({ children }: PropsWithChildren) {
    return (
        <div className="grid min-h-screen bg-white lg:grid-cols-[1fr_0.9fr]">
            <div className="flex min-h-screen flex-col px-5 py-6 sm:px-10 lg:px-16">
                <AppLogo />
                <main className="mx-auto flex w-full max-w-md flex-1 items-center py-10">{children}</main>
                <p className="text-center text-xs text-slate-500">
                    By continuing, you agree to Ezyweb's terms and privacy policy.
                </p>
            </div>
            <aside className="relative hidden overflow-hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-center">
                <div className="bg-brand-500/15 absolute -top-32 -right-32 size-96 rounded-full blur-3xl" />
                <div className="relative max-w-lg">
                    <p className="text-brand-500 text-sm font-semibold tracking-widest uppercase">
                        Built for momentum
                    </p>
                    <h2 className="mt-5 text-4xl leading-tight font-bold">
                        Your professional website, without the usual complexity.
                    </h2>
                    <p className="mt-5 text-lg leading-8 text-slate-300">
                        Ezyweb gives small teams a focused, section-based way to launch and manage a polished
                        online presence.
                    </p>
                    <ul className="mt-9 space-y-4">
                        {[
                            'Responsive from the first section',
                            'Clear, controlled design choices',
                            'Publishing workflow built for teams',
                        ].map((item) => (
                            <li key={item} className="flex items-center gap-3 text-sm text-slate-200">
                                <CheckCircle2 className="text-brand-500 size-5" />
                                {item}
                            </li>
                        ))}
                    </ul>
                    <Link
                        href="/"
                        className="text-brand-500 hover:text-brand-100 mt-10 inline-block text-sm font-semibold"
                    >
                        ← Back to ezyweb.in
                    </Link>
                </div>
            </aside>
        </div>
    );
}
