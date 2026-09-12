import type { PropsWithChildren } from 'react';
import { Toaster } from 'sonner';

import { ConfirmProvider } from './confirm-provider';

export function AppProviders({ children }: PropsWithChildren) {
    return (
        <ConfirmProvider>
            {children}
            <Toaster position="top-right" richColors closeButton />
        </ConfirmProvider>
    );
}
