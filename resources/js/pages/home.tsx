import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    LayoutTemplate,
    MousePointerClick,
    Palette,
    ShieldCheck,
    Smartphone,
    Zap,
} from 'lucide-react';
import { PublicLayout } from '../layouts/public-layout';

const features = [
    {
        icon: LayoutTemplate,
        title: 'Guided sections',
        text: 'Build with proven responsive sections instead of wrestling with a blank canvas.',
    },
    {
        icon: Palette,
        title: 'Your brand, consistently',
        text: 'Choose your colours and typography once. Ezyweb keeps every page coherent.',
    },
    {
        icon: Smartphone,
        title: 'Ready on every screen',
        text: 'Every template is designed mobile-first and tested for clarity at every size.',
    },
    {
        icon: ShieldCheck,
        title: 'Secure by foundation',
        text: 'Sensible defaults, controlled content, and a publishing workflow you can trust.',
    },
    {
        icon: MousePointerClick,
        title: 'Simple to manage',
        text: 'Update services, contact details, and content without calling a developer.',
    },
    {
        icon: Zap,
        title: 'Fast by design',
        text: 'Lean server-rendered websites give visitors the quick experience they expect.',
    },
];

export default function Home() {
    return (
        <PublicLayout>
            <Head>
                <title>Professional websites made simple</title>
                <meta
                    name="description"
                    content="Ezyweb helps small businesses launch and manage professional, responsive websites without complexity."
                />
            </Head>
            <section className="relative overflow-hidden bg-[radial-gradient(circle_at_75%_20%,#ccfbf1_0,transparent_34%),linear-gradient(to_bottom,#fff,#f8fafc)]">
                <div className="mx-auto grid max-w-7xl items-center gap-14 px-4 py-20 sm:px-6 sm:py-28 lg:grid-cols-2 lg:px-8 lg:py-32">
                    <div>
                        <span className="border-brand-100 bg-brand-50 text-brand-700 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold">
                            <span className="bg-brand-500 size-1.5 rounded-full" />
                            Website building, simplified
                        </span>
                        <h1 className="mt-6 text-4xl leading-[1.08] font-bold tracking-[-0.035em] text-slate-950 sm:text-6xl">
                            A website that looks like you mean business.
                        </h1>
                        <p className="mt-6 max-w-xl text-lg leading-8 text-slate-600">
                            Launch a polished, responsive website for your business with guided sections,
                            professional templates, and none of the usual technical overhead.
                        </p>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href="/register"
                                className="bg-brand-700 shadow-brand-700/20 hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3.5 font-semibold text-white shadow-lg"
                            >
                                Start building <ArrowRight className="size-4" />
                            </Link>
                            <a
                                href="#how-it-works"
                                className="inline-flex items-center justify-center rounded-xl border bg-white px-5 py-3.5 font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                See how it works
                            </a>
                        </div>
                        <p className="mt-4 text-sm text-slate-500">No design experience required.</p>
                    </div>
                    <div className="relative">
                        <div className="rounded-[2rem] border bg-white p-3 shadow-2xl shadow-slate-900/10">
                            <div className="overflow-hidden rounded-[1.4rem] border bg-slate-50">
                                <div className="flex items-center gap-1.5 border-b bg-white px-4 py-3">
                                    <span className="size-2.5 rounded-full bg-red-300" />
                                    <span className="size-2.5 rounded-full bg-amber-300" />
                                    <span className="size-2.5 rounded-full bg-emerald-300" />
                                    <span className="ml-3 rounded-md bg-slate-100 px-4 py-1 text-[10px] text-slate-400">
                                        yourbusiness.ezyweb.in
                                    </span>
                                </div>
                                <div className="p-5 sm:p-8">
                                    <div className="flex items-center justify-between">
                                        <div className="h-7 w-28 rounded-md bg-slate-900" />
                                        <div className="hidden gap-3 sm:flex">
                                            <span className="h-2 w-12 rounded bg-slate-200" />
                                            <span className="h-2 w-12 rounded bg-slate-200" />
                                        </div>
                                    </div>
                                    <div className="mt-10 grid items-center gap-8 sm:grid-cols-2">
                                        <div>
                                            <div className="bg-brand-500 h-3 w-20 rounded" />
                                            <div className="mt-4 h-7 w-full rounded bg-slate-900" />
                                            <div className="mt-2 h-7 w-4/5 rounded bg-slate-900" />
                                            <div className="mt-4 h-2 w-full rounded bg-slate-200" />
                                            <div className="mt-2 h-2 w-3/4 rounded bg-slate-200" />
                                            <div className="bg-brand-600 mt-6 h-9 w-28 rounded-lg" />
                                        </div>
                                        <div className="from-brand-100 aspect-[4/3] rounded-2xl bg-gradient-to-br to-slate-200" />
                                    </div>
                                    <div className="mt-9 grid grid-cols-3 gap-3">
                                        {[1, 2, 3].map((item) => (
                                            <div key={item} className="rounded-xl bg-white p-3 shadow-sm">
                                                <div className="bg-brand-50 size-7 rounded-lg" />
                                                <div className="mt-3 h-2 w-3/4 rounded bg-slate-300" />
                                                <div className="mt-2 h-1.5 w-full rounded bg-slate-100" />
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section id="audience" className="border-y bg-white">
                <div className="mx-auto max-w-7xl px-4 py-8 text-center sm:px-6 lg:px-8">
                    <p className="text-xs font-semibold tracking-widest text-slate-400 uppercase">
                        Made for the people building local trust
                    </p>
                    <div className="mt-5 flex flex-wrap justify-center gap-x-8 gap-y-3 text-sm font-semibold text-slate-600">
                        {[
                            'Consultants',
                            'Clinics',
                            'Schools',
                            'Restaurants',
                            'Agencies',
                            'Local services',
                        ].map((item) => (
                            <span key={item}>{item}</span>
                        ))}
                    </div>
                </div>
            </section>
            <section id="features" className="bg-slate-50 py-20 sm:py-28">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="max-w-2xl">
                        <p className="text-brand-700 text-sm font-semibold">Everything you need</p>
                        <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                            Professional foundations, without feature overload.
                        </h2>
                        <p className="mt-4 text-lg text-slate-600">
                            Ezyweb stays focused so you can go from idea to credible online presence quickly.
                        </p>
                    </div>
                    <div className="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        {features.map(({ icon: Icon, title, text }) => (
                            <article key={title} className="rounded-2xl border bg-white p-6 shadow-sm">
                                <span className="bg-brand-50 text-brand-700 grid size-11 place-items-center rounded-xl">
                                    <Icon className="size-5" />
                                </span>
                                <h3 className="mt-5 text-lg font-semibold">{title}</h3>
                                <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>
            <section id="how-it-works" className="bg-white py-20 sm:py-28">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <p className="text-brand-700 text-sm font-semibold">A clearer way to launch</p>
                        <h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                            Three steps from blank slate to live website.
                        </h2>
                    </div>
                    <div className="mt-14 grid gap-8 md:grid-cols-3">
                        {[
                            [
                                '01',
                                'Choose your direction',
                                'Pick a professional template that fits your business.',
                            ],
                            ['02', 'Make it yours', 'Add your brand, pages, sections, and business content.'],
                            [
                                '03',
                                'Preview and publish',
                                'Check every screen, then publish when you are ready.',
                            ],
                        ].map(([number, title, text]) => (
                            <div key={number} className="relative">
                                <span className="text-brand-100 text-5xl font-bold">{number}</span>
                                <h3 className="mt-3 text-xl font-semibold">{title}</h3>
                                <p className="mt-2 leading-7 text-slate-600">{text}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>
            <section className="bg-slate-950 py-20 text-white">
                <div className="mx-auto max-w-4xl px-4 text-center sm:px-6">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Ready to make your business easier to find?
                    </h2>
                    <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-300">
                        Create your Ezyweb account today. Your guided setup will help you build with
                        confidence.
                    </p>
                    <Link
                        href="/register"
                        className="bg-brand-500 hover:bg-brand-100 mt-8 inline-flex items-center gap-2 rounded-xl px-5 py-3.5 font-semibold text-slate-950"
                    >
                        Create your account <Check className="size-4" />
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
