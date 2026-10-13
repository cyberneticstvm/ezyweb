<?php

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Website;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/index', [
            'metrics' => [
                'accounts' => User::query()->count(),
                'customers' => Tenant::query()->count(),
                'active_customers' => Tenant::query()->where('status', TenantStatus::Active)->count(),
                'websites' => Website::query()->withoutGlobalScopes()->count(),
            ],
        ]);
    }
}
