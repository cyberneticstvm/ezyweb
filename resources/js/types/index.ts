export type PlatformRole = 'user' | 'super_admin' | 'platform_support' | 'platform_finance';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    platform_role: PlatformRole;
}

export interface AppPageProps extends Record<string, unknown> {
    app: { name: string; domain: string };
    auth: { user: AuthUser | null };
    tenant: { id: number; name: string; slug: string; status: string } | null;
    website: {
        id: number;
        name: string;
        type: string;
        status: string;
        publication_status: string;
        template_key: string;
        draft_revision: number;
        published_revision: number;
        published_at?: string | null;
        has_unpublished_changes: boolean;
        hostname?: string | null;
    } | null;
    subscription: {
        status: string;
        plan: string | null;
        current_period_ends_at: string | null;
        grace_ends_at: string | null;
    } | null;
    flash: { success?: string; error?: string };
    errors: Record<string, string>;
}

export interface BreadcrumbItem {
    label: string;
    href?: string;
}
