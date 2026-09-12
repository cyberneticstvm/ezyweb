<header class="site-header">
    @if ($template->key === 'localpro')
        <div class="site-contact-bar">
            <div class="site-container site-contact-bar-inner">
                <span>{{ app(\App\Models\Tenant::class)->contact_phone }}</span>
                <span>{{ app(\App\Models\Tenant::class)->contact_email }}</span>
            </div>
        </div>
    @endif
    <div class="site-container site-header-inner">
        <a class="site-brand" href="{{ $website->type->value === 'landing_page' ? '#top' : '/' }}" aria-label="{{ $website->name }} home">
            <span class="site-brand-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($website->name, 0, 1)) }}</span>
            <span>{{ $website->name }}</span>
        </a>

        <nav class="site-desktop-nav" aria-label="Primary navigation">
            @foreach ($navigation as $item)
                <a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        @if ($template->key !== 'canvas' && $contactUrl)
            <a class="site-header-cta" href="{{ $contactUrl }}">Contact us</a>
        @endif

        <details class="site-mobile-menu">
            <summary aria-label="Open navigation menu"><span></span><span></span><span></span></summary>
            <nav aria-label="Mobile navigation">
                @foreach ($navigation as $item)
                    <a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </details>
    </div>
</header>
