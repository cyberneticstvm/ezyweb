<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Tenancy\TenantScope;
use App\Models\Website;
use App\Subscriptions\FeatureGate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaDeliveryController extends Controller
{
    public function show(FeatureGate $features, string $media, string $variant = 'original'): StreamedResponse
    {
        $asset = MediaAsset::query()->withoutGlobalScope(TenantScope::class)->where('public_id', $media)->firstOrFail();
        $website = Website::query()->withoutGlobalScopes()->findOrFail($asset->website_id);
        $subscription = $features->subscription($website);
        $member = request()->user()?->tenantMemberships()->withoutGlobalScopes()->where('tenant_id', $asset->tenant_id)->exists() ?? false;
        abort_if($subscription && ! $subscription->status->allowsPublicAccess() && ! $member, 404);
        $details = $asset->variants[$variant] ?? null;
        abort_if($details === null, 404);
        $disk = Storage::disk($asset->disk);
        abort_unless($disk->exists($details['path']), 404);

        return response()->stream(function () use ($disk, $details): void {
            $stream = $disk->readStream($details['path']);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'image/webp',
            'Content-Length' => (string) $details['size'],
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.$asset->public_id.'-'.$variant.'.webp"',
        ]);
    }
}
