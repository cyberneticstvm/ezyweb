<?php

namespace App\Http\Controllers;

use App\Enums\MembershipRole;
use App\Enums\TenantStatus;
use App\Http\Requests\UpdateTenantStatusRequest;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminCustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $customers = Tenant::query()
            ->with(['memberships.user', 'websites.domains', 'currentSubscription.plan'])
            ->withCount(['memberships', 'websites'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('contact_email', 'like', '%'.$search.'%')
                        ->orWhereHas('memberships.user', fn ($users) => $users
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/customers', [
            'customers' => $customers->through(function (Tenant $tenant): array {
                $owner = $tenant->memberships->first(
                    fn ($membership): bool => $membership->role === MembershipRole::Owner,
                )?->user;
                $website = $tenant->websites->first();
                $domain = $website?->domains->firstWhere('is_primary', true) ?? $website?->domains->first();
                $subscription = $tenant->currentSubscription;

                return [
                    ...$tenant->only('id', 'name', 'slug', 'business_category', 'country_code', 'timezone', 'contact_email', 'contact_phone', 'created_at'),
                    'status' => $tenant->status->value,
                    'memberships_count' => $tenant->memberships_count,
                    'websites_count' => $tenant->websites_count,
                    'owner' => $owner?->only('name', 'email', 'phone'),
                    'hostname' => $domain?->hostname,
                    'subscription' => $subscription ? [
                        'status' => $subscription->status->value,
                        'plan' => $subscription->plan?->name,
                        'ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
                    ] : null,
                ];
            }),
            'statuses' => array_column(TenantStatus::cases(), 'value'),
            'filters' => compact('search', 'status'),
        ]);
    }

    public function status(Tenant $tenant, UpdateTenantStatusRequest $request): RedirectResponse
    {
        $before = $tenant->status->value;
        $status = TenantStatus::from($request->string('status')->toString());
        $tenant->update(['status' => $status]);

        $actor = $request->user();
        AuditLog::query()->create([
            'actor_id' => $actor instanceof User ? $actor->getKey() : null,
            'tenant_id' => $tenant->getKey(),
            'action' => 'tenant.status_updated',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->getKey(),
            'before' => ['status' => $before],
            'after' => ['status' => $status->value],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        return back()->with('success', "{$tenant->name} is now {$status->value}.");
    }
}
