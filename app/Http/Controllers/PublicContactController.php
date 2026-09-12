<?php

namespace App\Http\Controllers;

use App\Actions\SubmitContactMessage;
use App\Contact\PublicContactConfig;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\Website;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;

class PublicContactController extends Controller
{
    public function store(StoreContactMessageRequest $request, CurrentTenant $context, SubmitContactMessage $action, PublicContactConfig $settings): RedirectResponse
    {
        $website = $context->website();
        abort_unless($website instanceof Website, 404);
        $action->execute($website, $request->validated(), $request);

        $message = $settings->forWebsite($website)['success_message'] ?? 'Thank you. We will get back to you soon.';

        return back()->with('contact_success', is_string($message) ? $message : 'Thank you. We will get back to you soon.');
    }
}
