import { zodResolver } from '@hookform/resolvers/zod';
import { Head, Link, router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { AuthLayout } from '../../layouts/auth-layout';

const schema = z
    .object({
        name: z.string().min(2, 'Enter your full name.').max(120),
        email: z.email('Enter a valid email address.'),
        phone: z.string().max(30).optional(),
        password: z.string().min(8, 'Use at least 8 characters.'),
        password_confirmation: z.string(),
        terms: z.boolean().refine((value) => value, 'You must accept the terms.'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        path: ['password_confirmation'],
        message: 'Passwords do not match.',
    });
type FormData = z.infer<typeof schema>;
export default function Register() {
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<FormData>({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            email: '',
            phone: '',
            password: '',
            password_confirmation: '',
            terms: false,
        },
    });
    const submit = (data: FormData) =>
        new Promise<void>((resolve) =>
            router.post('/register', data, {
                onError: (serverErrors) =>
                    Object.entries(serverErrors).forEach(([field, message]) =>
                        setError(field as keyof FormData, { message }),
                    ),
                onFinish: () => resolve(),
            }),
        );
    return (
        <AuthLayout>
            <Head title="Create account" />
            <div className="w-full">
                <p className="text-brand-700 text-sm font-semibold">Get started</p>
                <h1 className="mt-2 text-3xl font-bold tracking-tight">Create your Ezyweb account</h1>
                <p className="mt-3 text-sm text-slate-600">
                    Your website setup begins after your account is ready.
                </p>
                <form onSubmit={(event) => void handleSubmit(submit)(event)} className="mt-7 space-y-4">
                    <Input
                        label="Full name"
                        error={errors.name?.message}
                        input={
                            <input
                                {...register('name')}
                                autoComplete="name"
                                className="w-full rounded-xl border px-3.5 py-3 text-sm"
                            />
                        }
                    />
                    <Input
                        label="Email address"
                        error={errors.email?.message}
                        input={
                            <input
                                {...register('email')}
                                type="email"
                                autoComplete="email"
                                className="w-full rounded-xl border px-3.5 py-3 text-sm"
                            />
                        }
                    />
                    <Input
                        label="Phone number (optional)"
                        error={errors.phone?.message}
                        input={
                            <input
                                {...register('phone')}
                                type="tel"
                                autoComplete="tel"
                                className="w-full rounded-xl border px-3.5 py-3 text-sm"
                            />
                        }
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Password"
                            error={errors.password?.message}
                            input={
                                <input
                                    {...register('password')}
                                    type="password"
                                    autoComplete="new-password"
                                    className="w-full rounded-xl border px-3.5 py-3 text-sm"
                                />
                            }
                        />
                        <Input
                            label="Confirm password"
                            error={errors.password_confirmation?.message}
                            input={
                                <input
                                    {...register('password_confirmation')}
                                    type="password"
                                    autoComplete="new-password"
                                    className="w-full rounded-xl border px-3.5 py-3 text-sm"
                                />
                            }
                        />
                    </div>
                    <label className="flex items-start gap-3 text-sm leading-6 text-slate-600">
                        <input
                            {...register('terms')}
                            type="checkbox"
                            className="accent-brand-700 mt-1 size-4"
                        />
                        I agree to the terms of service and privacy policy.
                    </label>
                    {errors.terms && <p className="text-xs text-red-600">{errors.terms.message}</p>}
                    <button
                        disabled={isSubmitting}
                        className="bg-brand-700 hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 font-semibold text-white disabled:opacity-60"
                    >
                        {isSubmitting && <Loader2 className="size-4 animate-spin" />}Create account
                    </button>
                </form>
                <p className="mt-6 text-center text-sm text-slate-600">
                    Already have an account?{' '}
                    <Link href="/login" className="text-brand-700 font-semibold">
                        Sign in
                    </Link>
                </p>
            </div>
        </AuthLayout>
    );
}
function Input({ label, error, input }: { label: string; error?: string; input: ReactNode }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-medium">{label}</span>
            {input}
            {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
        </label>
    );
}
