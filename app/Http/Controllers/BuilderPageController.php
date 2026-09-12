<?php

namespace App\Http\Controllers;

use App\Actions\CreatePage;
use App\Actions\DeletePage;
use App\Actions\UpdatePage;
use App\Builder\BuilderWorkspace;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class BuilderPageController extends Controller
{
    public function store(StorePageRequest $request, BuilderWorkspace $workspace, CreatePage $action): RedirectResponse
    {
        $website = $workspace->website();
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $website);
        $action->execute($website, $request->validated());

        return back()->with('success', 'Page added to your draft.');
    }

    public function update(UpdatePageRequest $request, int $page, BuilderWorkspace $workspace, UpdatePage $action): RedirectResponse
    {
        $model = $workspace->page($page);
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $model->website);
        $action->execute($model, $request->validated());

        return back()->with('success', 'Page settings saved.');
    }

    public function destroy(int $page, BuilderWorkspace $workspace, DeletePage $action): RedirectResponse
    {
        $model = $workspace->page($page);
        /** @var User $user */
        $user = request()->user();
        $this->authorize('update', $model->website);
        $action->execute($model);

        return back()->with('success', 'Page removed from your draft.');
    }
}
