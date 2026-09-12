<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\ChangeSubscriptionPlanRequest;
use App\Http\Requests\ExtendSubscriptionRequest;
use App\Http\Requests\RecordSubscriptionPaymentRequest;
use App\Http\Requests\UpdateSubscriptionStatusRequest;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Subscriptions\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminSubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $subscriptions = Subscription::query()->with(['tenant', 'plan', 'payments'])->when($search !== '', fn ($query) => $query->whereHas('tenant', fn ($tenant) => $tenant->where('name', 'like', '%'.$search.'%')))
            ->when($status !== '', fn ($query) => $query->where('status', $status))->latest()->paginate(20)->withQueryString();

        return Inertia::render('admin/subscriptions', [
            'subscriptions' => $subscriptions->through(fn (Subscription $subscription): array => [
                ...$subscription->only('public_id', 'status', 'starts_at', 'trial_ends_at', 'current_period_ends_at', 'grace_ends_at'),
                'tenant' => $subscription->tenant?->only('id', 'name', 'contact_email'), 'plan' => $subscription->plan?->only('id', 'name', 'code', 'annual_price', 'currency'),
                'payments' => $subscription->payments->take(5)->map->only('public_id', 'amount', 'currency', 'status', 'transaction_reference', 'paid_at'),
            ]),
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get()->map->only('id', 'name', 'code'),
            'statuses' => array_column(SubscriptionStatus::cases(), 'value'), 'filters' => compact('search', 'status'),
        ]);
    }

    public function changePlan(string $subscription, ChangeSubscriptionPlanRequest $request, SubscriptionManager $manager): RedirectResponse
    {
        $model = $this->subscription($subscription);
        $manager->changePlan($model, Plan::query()->findOrFail($request->integer('plan_id')), $this->actor($request), $request);
        return back()->with('success', 'Subscription plan changed.');
    }

    public function extend(string $subscription, ExtendSubscriptionRequest $request, SubscriptionManager $manager): RedirectResponse
    {
        $manager->extend($this->subscription($subscription), $request->integer('days'), $this->actor($request), $request);
        return back()->with('success', 'Subscription extended.');
    }

    public function status(string $subscription, UpdateSubscriptionStatusRequest $request, SubscriptionManager $manager): RedirectResponse
    {
        $manager->setStatus($this->subscription($subscription), SubscriptionStatus::from($request->string('status')->toString()), $this->actor($request), $request);
        return back()->with('success', 'Subscription status updated.');
    }

    public function payment(string $subscription, RecordSubscriptionPaymentRequest $request, SubscriptionManager $manager): RedirectResponse
    {
        $model = $this->subscription($subscription);
        $actor = $this->actor($request);
        DB::transaction(function () use ($model, $request, $actor, $manager): void {
            $payment = SubscriptionPayment::query()->create([
                'public_id' => (string) Str::ulid(), 'tenant_id' => $model->tenant_id, 'subscription_id' => $model->getKey(),
                'amount' => $request->input('amount'), 'currency' => strtoupper($request->string('currency')->toString()), 'status' => PaymentStatus::Paid,
                'provider' => 'manual', 'transaction_reference' => $request->string('transaction_reference')->toString(), 'paid_at' => $request->date('paid_at'), 'recorded_by' => $actor->getKey(),
            ]);
            AuditLog::query()->create(['actor_id' => $actor->getKey(), 'tenant_id' => $model->tenant_id, 'action' => 'subscription.payment_recorded', 'auditable_type' => SubscriptionPayment::class, 'auditable_id' => $payment->getKey(), 'after' => $payment->only('amount', 'currency', 'status', 'transaction_reference'), 'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 500, '')]);
            $manager->extend($model->fresh(), $request->integer('extend_days'), $actor, $request);
        });
        return back()->with('success', 'Payment recorded and subscription extended.');
    }

    private function subscription(string $publicId): Subscription { return Subscription::query()->where('public_id', $publicId)->firstOrFail(); }
    private function actor(Request $request): User { $user = $request->user(); abort_unless($user instanceof User, 401); return $user; }
}
