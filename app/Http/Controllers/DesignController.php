<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\User;
use App\Models\Website;
use App\Tenancy\CurrentTenant;
use App\Themes\ContrastService;
use App\Themes\ThemeTokenService;
use Inertia\Inertia;
use Inertia\Response;

class DesignController extends Controller
{
    public function __invoke(CurrentTenant $context, ThemeTokenService $tokens, ContrastService $contrast): Response
    {
        $website = $context->website();
        abort_unless($website instanceof Website, 404);
        /** @var User $user */
        $user = request()->user();
        $this->authorize('update', $website);

        $templates = Template::query()->where('is_active', true)->orderBy('sort_order')->get();
        $selected = $templates->firstWhere('key', $website->template_key) ?? $templates->firstOrFail();
        $theme = $tokens->forWebsite($website, $selected);

        return Inertia::render('design/index', [
            'website' => $website->only('id', 'name', 'template_key', 'status'),
            'templates' => $templates,
            'presets' => config('templates.presets'),
            'theme' => $theme,
            'cssVariables' => $tokens->cssVariables($theme),
            'contrast' => $contrast->analyze($theme),
        ]);
    }
}
