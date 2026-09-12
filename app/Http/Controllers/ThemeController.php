<?php

namespace App\Http\Controllers;

use App\Actions\ResetWebsiteTheme;
use App\Actions\UpdateWebsiteTheme;
use App\Http\Requests\UpdateThemeRequest;
use App\Models\Template;
use App\Models\Website;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;

class ThemeController extends Controller
{
    public function update(UpdateThemeRequest $request, CurrentTenant $context, UpdateWebsiteTheme $action): RedirectResponse
    {
        $website = $this->website($context);
        $this->authorize('update', $website);
        $action->execute($website, $request->theme());

        return back()->with('success', 'Theme settings saved.');
    }

    public function reset(CurrentTenant $context, ResetWebsiteTheme $action): RedirectResponse
    {
        $website = $this->website($context);
        $this->authorize('update', $website);
        $template = Template::query()->where('key', $website->template_key)->where('is_active', true)->firstOrFail();
        $action->execute($website, $template);

        return back()->with('success', 'Theme reset to the template defaults.');
    }

    private function website(CurrentTenant $context): Website
    {
        $website = $context->website();
        abort_unless($website instanceof Website, 404);

        return $website;
    }
}
