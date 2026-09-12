<?php

namespace App\Http\Controllers;

use App\Actions\SwitchWebsiteTemplate;
use App\Http\Requests\SwitchTemplateRequest;
use App\Models\Template;
use App\Models\User;
use App\Models\Website;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function preview(Template $template): Response
    {
        abort_unless($template->is_active, 404);

        return Inertia::render('design/template-preview', ['template' => $template]);
    }

    public function switch(SwitchTemplateRequest $request, CurrentTenant $context, SwitchWebsiteTemplate $action): RedirectResponse
    {
        $website = $context->website();
        abort_unless($website instanceof Website, 404);
        $this->authorize('update', $website);
        $template = Template::query()->where('key', $request->string('template_key'))->where('is_active', true)->firstOrFail();
        /** @var User $user */
        $user = $request->user();
        $action->execute($website, $template, $user);

        return back()->with('success', "Switched to {$template->name}. Review the preview before publishing.");
    }
}
