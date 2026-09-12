<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AdminPlanController;
use App\Http\Controllers\BuilderController;
use App\Http\Controllers\BuilderPageController;
use App\Http\Controllers\BuilderSectionController;
use App\Http\Controllers\ContentModuleController;
use App\Http\Controllers\ContactDashboardController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MediaDeliveryController;
use App\Http\Controllers\NewsletterSubscriberController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\PublicNewsletterController;
use App\Http\Controllers\PublicWebsiteController;
use App\Http\Controllers\PublicSeoController;
use App\Http\Controllers\PublishingController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SeoRedirectController;
use App\Http\Controllers\SubdomainAvailabilityController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\WebsitePreviewController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/up/ready', ReadinessController::class)->name('health.ready');

Route::get('/', [PublicWebsiteController::class, 'home'])
    ->middleware(['resolve-public-website', 'subscription-public-access', 'website-published', 'published-redirects'])
    ->name('home');
Route::get('/templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');
Route::post('/contact/enquiries', [PublicContactController::class, 'store'])
    ->middleware(['resolve-public-website', 'subscription-public-access', 'website-published', 'throttle:5,1'])->name('tenant.contact.store');
Route::post('/newsletter/subscriptions', [PublicNewsletterController::class, 'store'])
    ->middleware(['resolve-public-website', 'subscription-public-access', 'website-published', 'throttle:5,1'])->name('tenant.newsletter.store');
Route::get('/media/{media}/{variant?}', [MediaDeliveryController::class, 'show'])
    ->where('media', '[0-9A-HJKMNP-TV-Z]{26}')->where('variant', 'thumbnail|small|medium|large|original')->name('media.show');
Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap'])->middleware(['resolve-public-website', 'subscription-public-access', 'website-published'])->name('tenant.seo.sitemap');
Route::get('/robots.txt', [PublicSeoController::class, 'robots'])->middleware(['resolve-public-website', 'subscription-public-access', 'website-published'])->name('tenant.seo.robots');

Route::middleware(['resolve-public-website', 'subscription-public-access', 'website-published', 'published-redirects'])->group(function (): void {
    Route::get('/{contentType}', [PublicContentController::class, 'archive'])
        ->where('contentType', 'blog|news|events|careers')->name('tenant.content.archive');
    Route::get('/{contentType}/{slug}', [PublicContentController::class, 'show'])
        ->where('contentType', 'blog|news|events|careers')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('tenant.content.show');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding.create');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::get('/onboarding/subdomain-availability', SubdomainAvailabilityController::class)
        ->middleware('throttle:30,1')
        ->name('onboarding.subdomain-availability');
    Route::get('/dashboard', fn () => Inertia::render('dashboard/index'))
        ->middleware('tenant-member')
        ->name('dashboard');
    Route::middleware('tenant-member')->prefix('dashboard/design')->name('design.')->group(function (): void {
        Route::get('/', DesignController::class)->name('index');
        Route::put('/theme', [ThemeController::class, 'update'])->name('theme.update');
        Route::post('/theme/reset', [ThemeController::class, 'reset'])->name('theme.reset');
        Route::post('/template', [TemplateController::class, 'switch'])->name('template.switch');
    });
    Route::middleware('tenant-member')->prefix('dashboard/builder')->name('builder.')->group(function (): void {
        Route::get('/', BuilderController::class)->name('index');
        Route::post('/pages', [BuilderPageController::class, 'store'])->name('pages.store');
        Route::put('/pages/{page}', [BuilderPageController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [BuilderPageController::class, 'destroy'])->name('pages.destroy');
        Route::post('/sections', [BuilderSectionController::class, 'store'])->name('sections.store');
        Route::put('/sections/reorder', [BuilderSectionController::class, 'reorder'])->name('sections.reorder');
        Route::put('/sections/{section}', [BuilderSectionController::class, 'update'])->name('sections.update');
        Route::post('/sections/{section}/duplicate', [BuilderSectionController::class, 'duplicate'])->name('sections.duplicate');
        Route::delete('/sections/{section}', [BuilderSectionController::class, 'destroy'])->name('sections.destroy');
    });
    Route::middleware('tenant-member')->prefix('dashboard/publishing')->name('publishing.')->group(function (): void {
        Route::get('/', [PublishingController::class, 'index'])->name('index');
        Route::post('/publish', [PublishingController::class, 'publish'])->name('publish');
        Route::post('/versions/{version}/restore', [PublishingController::class, 'restore'])->name('versions.restore');
    });
    Route::middleware('tenant-member')->prefix('dashboard/content')->name('content.')->group(function (): void {
        Route::get('/{module}', [ContentModuleController::class, 'index'])->name('index');
        Route::post('/{module}', [ContentModuleController::class, 'store'])->name('store');
        Route::put('/{module}/{entry}', [ContentModuleController::class, 'update'])->whereNumber('entry')->name('update');
        Route::delete('/{module}/{entry}', [ContentModuleController::class, 'destroy'])->whereNumber('entry')->name('destroy');
    });
    Route::middleware('tenant-member')->prefix('dashboard/contact')->name('contact.')->group(function (): void {
        Route::get('/', [ContactDashboardController::class, 'index'])->name('index');
        Route::put('/settings', [ContactDashboardController::class, 'updateContact'])->name('settings.update');
        Route::put('/whatsapp', [ContactDashboardController::class, 'updateWhatsapp'])->name('whatsapp.update');
        Route::post('/messages/{message}/read', [ContactMessageController::class, 'read'])->name('messages.read');
        Route::post('/messages/{message}/archive', [ContactMessageController::class, 'archive'])->name('messages.archive');
        Route::get('/export', [ContactMessageController::class, 'export'])->name('export');
        Route::post('/subscribers/{subscriber}/status', [NewsletterSubscriberController::class, 'status'])->name('subscribers.status');
        Route::delete('/subscribers/{subscriber}', [NewsletterSubscriberController::class, 'destroy'])->name('subscribers.destroy');
        Route::get('/subscribers-export', [NewsletterSubscriberController::class, 'export'])->name('subscribers.export');
    });
    Route::middleware('tenant-member')->prefix('dashboard/media')->name('media.')->group(function (): void {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::post('/', [MediaController::class, 'store'])->name('store');
        Route::put('/{media}', [MediaController::class, 'update'])->name('update');
        Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
    });
    Route::middleware('tenant-member')->prefix('dashboard/seo')->name('seo.')->group(function (): void {
        Route::get('/', [SeoController::class, 'index'])->name('index');
        Route::put('/site', [SeoController::class, 'updateSite'])->name('site.update');
        Route::put('/pages/{page}', [SeoController::class, 'updatePage'])->whereNumber('page')->name('pages.update');
        Route::post('/redirects', [SeoRedirectController::class, 'store'])->name('redirects.store');
        Route::put('/redirects/{redirect}', [SeoRedirectController::class, 'update'])->name('redirects.update');
        Route::delete('/redirects/{redirect}', [SeoRedirectController::class, 'destroy'])->name('redirects.destroy');
    });
    Route::middleware('tenant-member')->get('/dashboard/subscription', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'signed:relative', 'tenant-member'])->group(function (): void {
    Route::get('/preview/{website}/versions/{version}/{pageSlug?}', [WebsitePreviewController::class, 'version'])
        ->whereNumber('website')->whereNumber('version')->where('pageSlug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('website.preview.version');
    Route::get('/preview/{website}/{pageSlug?}', [WebsitePreviewController::class, 'draft'])
        ->whereNumber('website')->where('pageSlug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('website.preview.draft');
});

Route::middleware(['auth', 'super-admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', fn () => Inertia::render('admin/index'))->name('index');
    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::put('/plans/{plan:public_id}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::get('/subscriptions', [\App\Http\Controllers\AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::put('/subscriptions/{subscription}/plan', [\App\Http\Controllers\AdminSubscriptionController::class, 'changePlan'])->name('subscriptions.plan');
    Route::post('/subscriptions/{subscription}/extend', [\App\Http\Controllers\AdminSubscriptionController::class, 'extend'])->name('subscriptions.extend');
    Route::put('/subscriptions/{subscription}/status', [\App\Http\Controllers\AdminSubscriptionController::class, 'status'])->name('subscriptions.status');
    Route::post('/subscriptions/{subscription}/payments', [\App\Http\Controllers\AdminSubscriptionController::class, 'payment'])->name('subscriptions.payments');
});

Route::get('/{pageSlug}', [PublicWebsiteController::class, 'show'])
    ->middleware(['resolve-public-website', 'subscription-public-access', 'website-published', 'published-redirects'])
    ->where('pageSlug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('tenant.page');
Route::get('/{fallback}', [PublicSeoController::class, 'notFound'])
    ->middleware(['resolve-public-website', 'subscription-public-access', 'website-published', 'published-redirects'])
    ->where('fallback', '.*')->name('tenant.not-found');
