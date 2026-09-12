import * as AlertDialog from '@radix-ui/react-alert-dialog';
import { createContext, useCallback, useContext, useMemo, useState, type PropsWithChildren } from 'react';

interface ConfirmOptions {
    title: string;
    description: string;
    confirmLabel?: string;
    destructive?: boolean;
}

type ConfirmFunction = (options: ConfirmOptions) => Promise<boolean>;

const ConfirmContext = createContext<ConfirmFunction | null>(null);

export function ConfirmProvider({ children }: PropsWithChildren) {
    const [request, setRequest] = useState<(ConfirmOptions & { resolve: (value: boolean) => void }) | null>(
        null,
    );

    const confirm = useCallback<ConfirmFunction>((options) => {
        return new Promise((resolve) => setRequest({ ...options, resolve }));
    }, []);

    const close = useCallback((result: boolean) => {
        setRequest((current) => {
            current?.resolve(result);
            return null;
        });
    }, []);

    const value = useMemo(() => confirm, [confirm]);

    return (
        <ConfirmContext.Provider value={value}>
            {children}
            <AlertDialog.Root open={request !== null} onOpenChange={(open) => !open && close(false)}>
                <AlertDialog.Portal>
                    <AlertDialog.Overlay className="fixed inset-0 z-50 bg-slate-950/45 backdrop-blur-sm" />
                    <AlertDialog.Content className="fixed top-1/2 left-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border bg-white p-6 shadow-2xl">
                        <AlertDialog.Title className="text-lg font-semibold text-slate-950">
                            {request?.title}
                        </AlertDialog.Title>
                        <AlertDialog.Description className="mt-2 text-sm leading-6 text-slate-600">
                            {request?.description}
                        </AlertDialog.Description>
                        <div className="mt-6 flex justify-end gap-3">
                            <AlertDialog.Cancel asChild>
                                <button className="rounded-xl border px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Cancel
                                </button>
                            </AlertDialog.Cancel>
                            <AlertDialog.Action asChild>
                                <button
                                    onClick={() => close(true)}
                                    className={
                                        request?.destructive
                                            ? 'rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700'
                                            : 'bg-brand-700 hover:bg-brand-600 rounded-xl px-4 py-2 text-sm font-semibold text-white'
                                    }
                                >
                                    {request?.confirmLabel ?? 'Confirm'}
                                </button>
                            </AlertDialog.Action>
                        </div>
                    </AlertDialog.Content>
                </AlertDialog.Portal>
            </AlertDialog.Root>
        </ConfirmContext.Provider>
    );
}

export function useConfirm(): ConfirmFunction {
    const context = useContext(ConfirmContext);
    if (!context) throw new Error('useConfirm must be used within ConfirmProvider');
    return context;
}
