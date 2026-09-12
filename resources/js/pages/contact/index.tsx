import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Archive,
    Bell,
    CheckCheck,
    Download,
    Inbox,
    MailPlus,
    MessageCircle,
    Settings2,
    Trash2,
    UserCheck,
    UserMinus,
} from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { DashboardLayout } from '../../layouts/dashboard-layout';

interface ContactSettings {
    heading: string;
    description: string;
    address: string;
    phone_numbers_text: string;
    email_addresses_text: string;
    working_hours: string;
    maps_embed_url: string;
    success_message: string;
    recipient_email: string;
    whatsapp_number: string;
    is_form_enabled: boolean;
    form_fields: Record<'name' | 'email' | 'phone' | 'subject' | 'message', boolean>;
}
interface WhatsappSettings {
    is_enabled: boolean;
    phone_number: string;
    default_message: string;
    show_on_desktop: boolean;
    show_on_tablet: boolean;
    show_on_mobile: boolean;
    position: 'left' | 'right';
    bottom_offset: number;
    display_delay: number;
    pulse_animation: boolean;
    show_label: boolean;
    included_pages_text: string;
    mobile_sticky_bar: boolean;
    call_number: string;
    directions_url: string;
}
interface Message {
    public_id: string;
    name: string;
    email?: string;
    phone?: string;
    subject?: string;
    message: string;
    source_page?: string;
    is_read: boolean;
    is_archived: boolean;
    submitted_at: string;
}
interface NewsletterSubscriber {
    public_id: string;
    name?: string | null;
    email: string;
    source_page?: string | null;
    consented_at: string;
    subscribed_at: string;
    unsubscribed_at?: string | null;
}
interface Props {
    contactSettings: ContactSettings;
    whatsappSettings: WhatsappSettings;
    messages: {
        data: Message[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    subscribers: {
        data: NewsletterSubscriber[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    notifications: Array<{ id: string; data: { title: string; message: string }; read_at?: string | null }>;
    filters: {
        search?: string;
        archived?: boolean;
        subscriber_search?: string;
        subscriber_status?: string;
    };
}

export default function ContactIndex(props: Props) {
    const [tab, setTab] = useState<'inbox' | 'newsletter' | 'settings' | 'whatsapp'>('inbox');
    return (
        <DashboardLayout
            title="Contact & enquiries"
            description="Configure public contact options and manage tenant-scoped enquiries."
        >
            <Head title="Contact & enquiries" />
            <div className="flex flex-wrap gap-2 rounded-2xl border bg-white p-2 shadow-sm">
                <Tab active={tab === 'inbox'} onClick={() => setTab('inbox')} icon={Inbox}>
                    Inbox ({props.messages.total})
                </Tab>
                <Tab active={tab === 'newsletter'} onClick={() => setTab('newsletter')} icon={MailPlus}>
                    Newsletter ({props.subscribers.total})
                </Tab>
                <Tab active={tab === 'settings'} onClick={() => setTab('settings')} icon={Settings2}>
                    Contact settings
                </Tab>
                <Tab active={tab === 'whatsapp'} onClick={() => setTab('whatsapp')} icon={MessageCircle}>
                    WhatsApp
                </Tab>
            </div>
            {tab === 'inbox' && <InboxPanel {...props} />}
            {tab === 'newsletter' && <SubscribersPanel {...props} />}
            {tab === 'settings' && <ContactSettingsForm settings={props.contactSettings} />}
            {tab === 'whatsapp' && <WhatsappSettingsForm settings={props.whatsappSettings} />}
        </DashboardLayout>
    );
}

function SubscribersPanel({ subscribers, filters }: Props) {
    const [search, setSearch] = useState(filters.subscriber_search ?? '');
    const [status, setStatus] = useState(filters.subscriber_status ?? '');
    const searchSubmit = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            '/dashboard/contact',
            { subscriber_search: search, subscriber_status: status },
            { preserveState: true },
        );
    };

    return (
        <section className="mt-6 overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
                <form onSubmit={searchSubmit} className="flex flex-wrap gap-2">
                    <input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search subscribers"
                        className="rounded-xl border px-3 py-2 text-sm"
                    />
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="rounded-xl border bg-white px-3 py-2 text-sm"
                        aria-label="Subscriber status"
                    >
                        <option value="">All statuses</option>
                        <option value="subscribed">Subscribed</option>
                        <option value="unsubscribed">Unsubscribed</option>
                    </select>
                    <button className="rounded-xl border px-3 py-2 text-sm font-semibold">Filter</button>
                </form>
                <a
                    href="/dashboard/contact/subscribers-export"
                    className="inline-flex items-center justify-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold"
                >
                    <Download className="size-4" />
                    Export CSV
                </a>
            </div>
            {subscribers.data.length === 0 ? (
                <div className="p-10 text-center">
                    <MailPlus className="mx-auto size-8 text-slate-300" />
                    <p className="mt-3 text-sm font-semibold">No newsletter subscribers match this view.</p>
                    <p className="mt-1 text-xs text-slate-500">
                        Add and publish a Newsletter section to start collecting consented signups.
                    </p>
                </div>
            ) : (
                <div className="divide-y">
                    {subscribers.data.map((subscriber) => {
                        const subscribed = !subscriber.unsubscribed_at;
                        return (
                            <article
                                key={subscriber.public_id}
                                className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="truncate font-semibold">
                                            {subscriber.name || subscriber.email}
                                        </h2>
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-[11px] font-bold ${
                                                subscribed
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-slate-100 text-slate-600'
                                            }`}
                                        >
                                            {subscribed ? 'Subscribed' : 'Unsubscribed'}
                                        </span>
                                    </div>
                                    {subscriber.name && (
                                        <p className="mt-1 truncate text-sm text-slate-600">{subscriber.email}</p>
                                    )}
                                    <p className="mt-1 text-xs text-slate-400">
                                        Joined {new Date(subscriber.subscribed_at).toLocaleString()} · Source:{' '}
                                        {subscriber.source_page || '/'}
                                    </p>
                                </div>
                                <div className="flex shrink-0 gap-2">
                                    <button
                                        onClick={() =>
                                            router.post(
                                                `/dashboard/contact/subscribers/${subscriber.public_id}/status`,
                                                { subscribed: !subscribed },
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold"
                                    >
                                        {subscribed ? (
                                            <UserMinus className="size-3.5" />
                                        ) : (
                                            <UserCheck className="size-3.5" />
                                        )}
                                        {subscribed ? 'Unsubscribe' : 'Reactivate'}
                                    </button>
                                    <button
                                        onClick={() => {
                                            if (window.confirm(`Remove ${subscriber.email} permanently?`)) {
                                                router.delete(
                                                    `/dashboard/contact/subscribers/${subscriber.public_id}`,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                        className="inline-flex items-center gap-1 rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-700"
                                        aria-label={`Remove ${subscriber.email}`}
                                    >
                                        <Trash2 className="size-3.5" />
                                        Remove
                                    </button>
                                </div>
                            </article>
                        );
                    })}
                </div>
            )}
            {subscribers.last_page > 1 && (
                <div className="flex justify-between border-t p-4 text-sm">
                    {subscribers.prev_page_url ? (
                        <Link href={subscribers.prev_page_url} preserveState>
                            Previous
                        </Link>
                    ) : (
                        <span />
                    )}
                    <span className="text-slate-500">
                        Page {subscribers.current_page} of {subscribers.last_page}
                    </span>
                    {subscribers.next_page_url ? (
                        <Link href={subscribers.next_page_url} preserveState>
                            Next
                        </Link>
                    ) : (
                        <span />
                    )}
                </div>
            )}
        </section>
    );
}

function InboxPanel({ messages, notifications, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const searchSubmit = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            '/dashboard/contact',
            { search, archived: filters.archived ? 1 : 0 },
            { preserveState: true },
        );
    };
    return (
        <div className="mt-6 grid gap-6 xl:grid-cols-[1fr_19rem]">
            <section className="overflow-hidden rounded-2xl border bg-white shadow-sm">
                <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                    <form onSubmit={searchSubmit} className="flex gap-2">
                        <input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search enquiries"
                            className="rounded-xl border px-3 py-2 text-sm"
                        />
                        <button className="rounded-xl border px-3 py-2 text-sm font-semibold">Search</button>
                    </form>
                    <div className="flex gap-2">
                        <Link
                            href={`/dashboard/contact?archived=${filters.archived ? 0 : 1}`}
                            className="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold"
                        >
                            <Archive className="size-4" />
                            {filters.archived ? 'Active' : 'Archived'}
                        </Link>
                        <a
                            href="/dashboard/contact/export"
                            className="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold"
                        >
                            <Download className="size-4" />
                            CSV
                        </a>
                    </div>
                </div>
                {messages.data.length === 0 ? (
                    <p className="p-10 text-center text-sm text-slate-500">No enquiries match this view.</p>
                ) : (
                    <div className="divide-y">
                        {messages.data.map((message) => (
                            <article
                                key={message.public_id}
                                className={`p-5 ${message.is_read ? '' : 'bg-brand-50/40'}`}
                            >
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h2 className="font-semibold">
                                            {message.subject || 'Website enquiry'}
                                        </h2>
                                        <p className="mt-1 text-sm text-slate-600">
                                            {message.name}
                                            {message.email ? ` · ${message.email}` : ''}
                                            {message.phone ? ` · ${message.phone}` : ''}
                                        </p>
                                    </div>
                                    <time className="text-xs text-slate-400">
                                        {new Date(message.submitted_at).toLocaleString()}
                                    </time>
                                </div>
                                <p className="mt-3 text-sm leading-6 whitespace-pre-line text-slate-700">
                                    {message.message}
                                </p>
                                <p className="mt-2 text-xs text-slate-400">
                                    Source: {message.source_page || '/'}
                                </p>
                                <div className="mt-4 flex gap-2">
                                    {!message.is_read && (
                                        <button
                                            onClick={() =>
                                                router.post(
                                                    `/dashboard/contact/messages/${message.public_id}/read`,
                                                )
                                            }
                                            className="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold"
                                        >
                                            <CheckCheck className="size-3.5" />
                                            Mark read
                                        </button>
                                    )}
                                    <button
                                        onClick={() =>
                                            router.post(
                                                `/dashboard/contact/messages/${message.public_id}/archive`,
                                                { archived: !message.is_archived },
                                            )
                                        }
                                        className="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold"
                                    >
                                        <Archive className="size-3.5" />
                                        {message.is_archived ? 'Restore' : 'Archive'}
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
                {messages.last_page > 1 && (
                    <div className="flex justify-between border-t p-4 text-sm">
                        {messages.prev_page_url ? (
                            <Link href={messages.prev_page_url}>Previous</Link>
                        ) : (
                            <span />
                        )}
                        {messages.next_page_url ? <Link href={messages.next_page_url}>Next</Link> : <span />}
                    </div>
                )}
            </section>
            <aside className="rounded-2xl border bg-white p-5 shadow-sm">
                <div className="flex items-center gap-2">
                    <Bell className="text-brand-700 size-5" />
                    <h2 className="font-semibold">Notifications</h2>
                </div>
                <div className="mt-4 space-y-3">
                    {notifications.length ? (
                        notifications.map((notification) => (
                            <div key={notification.id} className="rounded-xl bg-slate-50 p-3">
                                <p className="text-sm font-semibold">{notification.data.title}</p>
                                <p className="mt-1 text-xs text-slate-600">{notification.data.message}</p>
                            </div>
                        ))
                    ) : (
                        <p className="text-sm text-slate-500">No notifications yet.</p>
                    )}
                </div>
            </aside>
        </div>
    );
}

function ContactSettingsForm({ settings }: { settings: ContactSettings }) {
    const form = useForm<ContactSettings>(settings);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/dashboard/contact/settings', { preserveScroll: true });
    };
    return (
        <form onSubmit={submit} className="mt-6 rounded-2xl border bg-white p-6 shadow-sm">
            <div className="grid gap-5 sm:grid-cols-2">
                <Field
                    label="Heading"
                    value={form.data.heading}
                    onChange={(v) => form.setData('heading', v)}
                    error={form.errors.heading}
                />
                <Field
                    label="Recipient email"
                    type="email"
                    value={form.data.recipient_email}
                    onChange={(v) => form.setData('recipient_email', v)}
                    error={form.errors.recipient_email}
                />
                <Area
                    label="Description"
                    value={form.data.description}
                    onChange={(v) => form.setData('description', v)}
                />
                <Area
                    label="Address"
                    value={form.data.address}
                    onChange={(v) => form.setData('address', v)}
                />
                <Area
                    label="Phone numbers (one per line)"
                    value={form.data.phone_numbers_text}
                    onChange={(v) => form.setData('phone_numbers_text', v)}
                />
                <Area
                    label="Internal email addresses (one per line)"
                    value={form.data.email_addresses_text}
                    onChange={(v) => form.setData('email_addresses_text', v)}
                />
                <Area
                    label="Working hours"
                    value={form.data.working_hours}
                    onChange={(v) => form.setData('working_hours', v)}
                />
                <Field
                    label="Google Maps embed URL"
                    type="url"
                    value={form.data.maps_embed_url}
                    onChange={(v) => form.setData('maps_embed_url', v)}
                    error={form.errors.maps_embed_url}
                />
                <Field
                    label="Success message"
                    value={form.data.success_message}
                    onChange={(v) => form.setData('success_message', v)}
                />
                <Field
                    label="WhatsApp number"
                    value={form.data.whatsapp_number}
                    onChange={(v) => form.setData('whatsapp_number', v)}
                />
            </div>
            <div className="mt-6 flex flex-wrap gap-4">
                {Object.entries(form.data.form_fields).map(([key, enabled]) => (
                    <Check
                        key={key}
                        label={`Show ${key}`}
                        checked={enabled}
                        onChange={(v) => form.setData('form_fields', { ...form.data.form_fields, [key]: v })}
                        disabled={key === 'name' || key === 'message'}
                    />
                ))}
                <Check
                    label="Enable contact form"
                    checked={form.data.is_form_enabled}
                    onChange={(v) => form.setData('is_form_enabled', v)}
                />
            </div>
            <Save processing={form.processing} />
        </form>
    );
}

function WhatsappSettingsForm({ settings }: { settings: WhatsappSettings }) {
    const form = useForm<WhatsappSettings>(settings);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/dashboard/contact/whatsapp', { preserveScroll: true });
    };
    return (
        <form onSubmit={submit} className="mt-6 rounded-2xl border bg-white p-6 shadow-sm">
            <div className="grid gap-5 sm:grid-cols-2">
                <Field
                    label="WhatsApp number"
                    value={form.data.phone_number}
                    onChange={(v) => form.setData('phone_number', v)}
                    error={form.errors.phone_number}
                />
                <Field
                    label="Default message"
                    value={form.data.default_message}
                    onChange={(v) => form.setData('default_message', v)}
                />
                <Field
                    label="Bottom offset (pixels)"
                    type="number"
                    value={String(form.data.bottom_offset)}
                    onChange={(v) => form.setData('bottom_offset', Number(v))}
                />
                <Field
                    label="Display delay (seconds)"
                    type="number"
                    value={String(form.data.display_delay)}
                    onChange={(v) => form.setData('display_delay', Number(v))}
                />
                <Area
                    label="Included pages (one path per line, blank for all)"
                    value={form.data.included_pages_text}
                    onChange={(v) => form.setData('included_pages_text', v)}
                />
                <Field
                    label="Call number"
                    value={form.data.call_number}
                    onChange={(v) => form.setData('call_number', v)}
                />
                <Field
                    label="Directions URL"
                    type="url"
                    value={form.data.directions_url}
                    onChange={(v) => form.setData('directions_url', v)}
                />
                <label>
                    <span className="mb-1.5 block text-sm font-semibold">Position</span>
                    <select
                        value={form.data.position}
                        onChange={(e) => form.setData('position', e.target.value as 'left' | 'right')}
                        className="w-full rounded-xl border px-3 py-2.5"
                    >
                        <option value="right">Right</option>
                        <option value="left">Left</option>
                    </select>
                </label>
            </div>
            <div className="mt-6 flex flex-wrap gap-4">
                <Check
                    label="Enable WhatsApp"
                    checked={form.data.is_enabled}
                    onChange={(v) => form.setData('is_enabled', v)}
                />
                <Check
                    label="Desktop"
                    checked={form.data.show_on_desktop}
                    onChange={(v) => form.setData('show_on_desktop', v)}
                />
                <Check
                    label="Tablet"
                    checked={form.data.show_on_tablet}
                    onChange={(v) => form.setData('show_on_tablet', v)}
                />
                <Check
                    label="Mobile"
                    checked={form.data.show_on_mobile}
                    onChange={(v) => form.setData('show_on_mobile', v)}
                />
                <Check
                    label="Pulse"
                    checked={form.data.pulse_animation}
                    onChange={(v) => form.setData('pulse_animation', v)}
                />
                <Check
                    label="Show label"
                    checked={form.data.show_label}
                    onChange={(v) => form.setData('show_label', v)}
                />
                <Check
                    label="Mobile sticky bar"
                    checked={form.data.mobile_sticky_bar}
                    onChange={(v) => form.setData('mobile_sticky_bar', v)}
                />
            </div>
            <Save processing={form.processing} />
        </form>
    );
}

function Tab({
    active,
    onClick,
    icon: Icon,
    children,
}: {
    active: boolean;
    onClick: () => void;
    icon: typeof Inbox;
    children: ReactNode;
}) {
    return (
        <button
            onClick={onClick}
            className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ${active ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50'}`}
        >
            <Icon className="size-4" />
            {children}
        </button>
    );
}
function Field({
    label,
    value,
    onChange,
    type = 'text',
    error,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    type?: string;
    error?: string;
}) {
    return (
        <label>
            <span className="mb-1.5 block text-sm font-semibold">{label}</span>
            <input
                type={type}
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
                className="w-full rounded-xl border px-3 py-2.5 text-sm"
            />
            {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
        </label>
    );
}
function Area({ label, value, onChange }: { label: string; value: string; onChange: (v: string) => void }) {
    return (
        <label>
            <span className="mb-1.5 block text-sm font-semibold">{label}</span>
            <textarea
                rows={4}
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
                className="w-full rounded-xl border px-3 py-2.5 text-sm"
            />
        </label>
    );
}
function Check({
    label,
    checked,
    onChange,
    disabled = false,
}: {
    label: string;
    checked: boolean;
    onChange: (v: boolean) => void;
    disabled?: boolean;
}) {
    return (
        <label className="inline-flex items-center gap-2 text-sm font-medium">
            <input
                type="checkbox"
                checked={checked}
                disabled={disabled}
                onChange={(e) => onChange(e.target.checked)}
                className="size-4"
            />
            {label}
        </label>
    );
}
function Save({ processing }: { processing: boolean }) {
    return (
        <div className="mt-7 border-t pt-5">
            <button
                disabled={processing}
                className="bg-brand-700 rounded-xl px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
            >
                {processing ? 'Saving…' : 'Save settings'}
            </button>
        </div>
    );
}
