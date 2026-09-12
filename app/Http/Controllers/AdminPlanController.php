<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use App\Subscriptions\PlanManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminPlanController extends Controller
{
    public function index(): Response
    {
        $definitions = collect($this->featureDefinitions());

        return Inertia::render('admin/plans', [
            'plans' => Plan::query()->with(['features'])->withCount('subscriptions')->orderBy('sort_order')->orderBy('id')->get()
                ->map(function (Plan $plan) use ($definitions): array {
                    $values = $plan->features->mapWithKeys(fn (PlanFeature $feature): array => [$feature->key => $feature->value]);

                    return [
                        ...$plan->only(['public_id', 'code', 'name', 'description', 'annual_price', 'currency', 'trial_days', 'grace_days', 'retention_days', 'sort_order', 'is_active', 'is_featured', 'subscriptions_count']),
                        'features' => $definitions->mapWithKeys(function (array $definition) use ($values): array {
                            $fallbackKey = $definition['fallback_key'] ?? null;
                            $fallback = is_string($fallbackKey)
                                ? $values->get($fallbackKey, 0)
                                : ($definition['type'] === 'boolean' ? false : 0);

                            return [$definition['key'] => $values->get($definition['key'], $fallback)];
                        })->all(),
                    ];
                }),
            'featureDefinitions' => $definitions,
        ]);
    }

    public function update(Plan $plan, UpdatePlanRequest $request, PlanManager $manager): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        $manager->update($plan, $request->validated(), $actor, $request);

        return back()->with('success', 'Plan and feature availability updated.');
    }

    /** @return list<array{key: string, type: string, label: string, description: string, max?: int, fallback_key?: string}> */
    private function featureDefinitions(): array
    {
        $configured = config('plan-features', []);
        if (! is_array($configured)) {
            return [];
        }

        $definitions = [];
        foreach ($configured as $key => $definition) {
            if (! is_string($key) || ! is_array($definition)) {
                continue;
            }

            $item = [
                'key' => $key,
                'type' => (string) ($definition['type'] ?? 'limit'),
                'label' => (string) ($definition['label'] ?? $key),
                'description' => (string) ($definition['description'] ?? ''),
            ];
            if (isset($definition['max'])) {
                $item['max'] = (int) $definition['max'];
            }
            if (isset($definition['fallback_key']) && is_string($definition['fallback_key'])) {
                $item['fallback_key'] = $definition['fallback_key'];
            }
            $definitions[] = $item;
        }

        return $definitions;
    }
}
