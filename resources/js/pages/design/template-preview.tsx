import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { AppLogo } from '../../components/app-logo';
import { TemplatePreview, type ThemeTokens } from '../../components/template-preview';

interface TemplateRecord {
    key: string;
    name: string;
    description: string;
    default_theme: ThemeTokens;
}
export default function TemplatePreviewPage({ template }: { template: TemplateRecord }) {
    return (
        <div className="min-h-screen bg-slate-100">
            <Head title={`${template.name} preview`} />
            <header className="border-b bg-white">
                <div className="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6">
                    <AppLogo />
                    <Link
                        href="/register"
                        className="bg-brand-700 rounded-xl px-4 py-2 text-sm font-semibold text-white"
                    >
                        Start with {template.name}
                    </Link>
                </div>
            </header>
            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                <div className="mb-5">
                    <Link href="/" className="inline-flex items-center gap-2 text-sm text-slate-600">
                        <ArrowLeft className="size-4" />
                        Back to Ezyweb
                    </Link>
                    <h1 className="mt-4 text-3xl font-bold">{template.name}</h1>
                    <p className="mt-2 max-w-2xl text-slate-600">{template.description}</p>
                </div>
                <div className="overflow-hidden rounded-2xl border bg-white shadow-xl">
                    <TemplatePreview templateKey={template.key} theme={template.default_theme} />
                </div>
            </main>
        </div>
    );
}
