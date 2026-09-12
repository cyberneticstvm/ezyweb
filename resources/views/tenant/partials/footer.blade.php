<footer class="site-footer">
    <div class="site-container site-footer-grid">
        <div>
            <a class="site-brand site-footer-brand" href="{{ $website->type->value === 'landing_page' ? '#top' : '/' }}">
                <span class="site-brand-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($website->name, 0, 1)) }}</span>
                <span>{{ $website->name }}</span>
            </a>
            <p>A clear, responsive website built to help visitors understand and connect with your business.</p>
        </div>
        <nav aria-label="Footer navigation">
            @foreach ($navigation as $item)
                <a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>
    </div>
    <div class="site-container site-footer-bottom">
        <span>© {{ now()->year }} {{ $website->name }}. All rights reserved.</span>
        <a href="https://www.ezyweb.in" rel="noopener">Website by Ezyweb</a>
    </div>
</footer>
