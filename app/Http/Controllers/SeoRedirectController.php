<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Http\Requests\SaveRedirectRequest;
use App\Seo\SeoManager;
use Illuminate\Http\RedirectResponse;

class SeoRedirectController extends Controller
{
    public function store(SaveRedirectRequest $request, BuilderWorkspace $workspace, SeoManager $manager): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $manager->saveRedirect($website, $request->validated());

        return back()->with('success', 'Redirect created.');
    }

    public function update(string $redirect, SaveRedirectRequest $request, BuilderWorkspace $workspace, SeoManager $manager): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $manager->saveRedirect($website, $request->validated(), $manager->findRedirect($website, $redirect));

        return back()->with('success', 'Redirect updated.');
    }

    public function destroy(string $redirect, BuilderWorkspace $workspace, SeoManager $manager): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $manager->deleteRedirect($website, $manager->findRedirect($website, $redirect));

        return back()->with('success', 'Redirect deleted.');
    }
}
