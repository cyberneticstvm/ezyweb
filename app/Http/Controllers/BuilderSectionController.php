<?php

namespace App\Http\Controllers;

use App\Actions\AddPageSection;
use App\Actions\DeletePageSection;
use App\Actions\DuplicatePageSection;
use App\Actions\ReorderPageSections;
use App\Actions\UpdatePageSection;
use App\Builder\BuilderWorkspace;
use App\Http\Requests\ReorderPageSectionsRequest;
use App\Http\Requests\StorePageSectionRequest;
use App\Http\Requests\UpdatePageSectionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class BuilderSectionController extends Controller
{
    public function store(StorePageSectionRequest $request, BuilderWorkspace $workspace, AddPageSection $action): RedirectResponse
    {
        $page = $workspace->page($request->integer('page_id'));
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $page->website);
        $action->execute($page, $request->string('type')->toString());

        return back()->with('success', 'Section added to your draft.');
    }

    public function update(UpdatePageSectionRequest $request, int $section, BuilderWorkspace $workspace, UpdatePageSection $action): RedirectResponse
    {
        $model = $workspace->section($section);
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $model->website);
        $action->execute($model, $request->validated());

        return back()->with('success', 'Draft section saved.');
    }

    public function duplicate(int $section, BuilderWorkspace $workspace, DuplicatePageSection $action): RedirectResponse
    {
        $model = $workspace->section($section);
        /** @var User $user */
        $user = request()->user();
        $this->authorize('update', $model->website);
        $action->execute($model);

        return back()->with('success', 'Section duplicated.');
    }

    public function destroy(int $section, BuilderWorkspace $workspace, DeletePageSection $action): RedirectResponse
    {
        $model = $workspace->section($section);
        /** @var User $user */
        $user = request()->user();
        $this->authorize('update', $model->website);
        $action->execute($model);

        return back()->with('success', 'Section removed from your draft.');
    }

    public function reorder(ReorderPageSectionsRequest $request, BuilderWorkspace $workspace, ReorderPageSections $action): RedirectResponse
    {
        $page = $workspace->page($request->integer('page_id'));
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $page->website);
        $action->execute($page, $request->validated('section_ids'));

        return back()->with('success', 'Section order saved.');
    }
}
