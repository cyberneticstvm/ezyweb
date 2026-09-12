import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { StrictMode, type ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { toast } from 'sonner';

import { AppProviders } from './components/app-providers';
import type { AppPageProps } from './types';

const pages = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx');

void createInertiaApp<AppPageProps>({
    title: (title) => (title ? `${title} · Ezyweb` : 'Ezyweb'),
    resolve: async (name) => {
        const page = pages[`./pages/${name}.tsx`];
        if (!page) throw new Error(`Page not found: ${name}`);
        return (await page()).default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <StrictMode>
                <AppProviders>
                    <App {...props} />
                </AppProviders>
            </StrictMode>,
        );
    },
    progress: { color: '#0d9488', showSpinner: false },
});

router.on('success', (event) => {
    const page = event.detail.page.props as unknown as AppPageProps;
    if (page.flash?.success) toast.success(page.flash.success);
    if (page.flash?.error) toast.error(page.flash.error);
});
