import { zodResolver } from '@hookform/resolvers/zod';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, EyeOff, Loader2 } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { AuthLayout } from '../../layouts/auth-layout';

const schema = z.object({
    email: z.email('Enter a valid email address.'),
    password: z.string().min(1, 'Enter your password.'),
    remember: z.boolean(),
});
type FormData = z.infer<typeof schema>;

export default function Login() {
    const [showPassword, setShowPassword] = useState(false);
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<FormData>({
        resolver: zodResolver(schema),
        defaultValues: { email: '', password: '', remember: false },
    });
    const submit = (data: FormData) =>
        new Promise<void>((resolve) =>
            router.post('/login', data, {
                onError: (serverErrors) =>
                    Object.entries(serverErrors).forEach(([field, message]) =>
                        setError(field as keyof FormData, { message }),
                    ),
                onFinish: () => resolve(),
            }),
        );
    return (
        <AuthLayout>
            <Head title="Sign in" />
            <div className="w-full">
                <p className="text-brand-700 text-sm font-semibold">Welcome back</p>
                <h1 className="mt-2 text-3xl font-bold tracking-tight">Sign in to Ezyweb</h1>
                <p className="mt-3 text-sm text-slate-600">
                    Manage your website and keep your online presence current.
                </p>
                <form onSubmit={(event) => void handleSubmit(submit)(event)} className="mt-8 space-y-5">
                    <Field label="Email address" error={errors.email?.message}>
                        <input
                            {...register('email')}
                            type="email"
                            autoComplete="email"
                            className="w-full rounded-xl border px-3.5 py-3 text-sm"
                        />
                    </Field>
                    <Field label="Password" error={errors.password?.message}>
                        <div className="relative">
                            <input
                                {...register('password')}
                                type={showPassword ? 'text' : 'password'}
                                autoComplete="current-password"
                                className="w-full rounded-xl border px-3.5 py-3 pr-11 text-sm"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="absolute top-1/2 right-3 -translate-y-1/2 text-slate-500"
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                            >
                                {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                            </button>
                        </div>
                    </Field>
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            {...register('remember')}
                            type="checkbox"
                            className="accent-brand-700 size-4 rounded"
                        />
                        Remember me
                    </label>
                    <button
                        disabled={isSubmitting}
                        className="bg-brand-700 hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 font-semibold text-white disabled:opacity-60"
                    >
                        {isSubmitting && <Loader2 className="size-4 animate-spin" />}Sign in
                    </button>
                </form>
                <p className="mt-7 text-center text-sm text-slate-600">
                    New to Ezyweb?{' '}
                    <Link href="/register" className="text-brand-700 font-semibold">
                        Create an account
                    </Link>
                </p>
            </div>
        </AuthLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <label className="block">
            <span className="mb-2 block text-sm font-medium">{label}</span>
            {children}
            {error && <span className="mt-1.5 block text-xs text-red-600">{error}</span>}
        </label>
    );
}
