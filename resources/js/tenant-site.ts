function initializeHeroSlider(slider: HTMLElement) {
    const slides = Array.from(slider.querySelectorAll<HTMLElement>('[data-hero-slide]'));
    const dots = Array.from(slider.querySelectorAll<HTMLButtonElement>('[data-hero-dot]'));
    const status = slider.querySelector<HTMLElement>('[data-hero-status]');
    if (slides.length < 2) return;

    let current = 0;
    let timer: number | undefined;
    const interval = Math.max(3000, Number(slider.dataset.interval) || 5000);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const autoplay = slider.dataset.autoplay === 'true' && !reducedMotion;

    const show = (index: number, announce = true) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, position) => {
            slide.hidden = position !== current;
        });
        dots.forEach((dot, position) =>
            dot.setAttribute('aria-selected', position === current ? 'true' : 'false'),
        );
        if (announce && status) status.textContent = `Slide ${current + 1} of ${slides.length}`;
    };
    const stop = () => {
        if (timer !== undefined) window.clearInterval(timer);
        timer = undefined;
    };
    const start = () => {
        stop();
        if (autoplay) timer = window.setInterval(() => show(current + 1, false), interval);
    };

    slider.querySelector('[data-hero-previous]')?.addEventListener('click', () => {
        show(current - 1);
        start();
    });
    slider.querySelector('[data-hero-next]')?.addEventListener('click', () => {
        show(current + 1);
        start();
    });
    dots.forEach((dot) =>
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.index));
            start();
        }),
    );
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', (event) => {
        if (!slider.contains(event.relatedTarget as Node | null)) start();
    });
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    show(0, false);
    start();
}

document.querySelectorAll<HTMLElement>('[data-hero-slider]').forEach(initializeHeroSlider);

function initializeCounters(section: HTMLElement) {
    if (section.dataset.animate !== 'true') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const numbers = Array.from(section.querySelectorAll<HTMLElement>('[data-counter-number]'));
    if (numbers.length === 0) return;

    const duration = Math.min(5000, Math.max(1000, Number(section.dataset.duration) || 2000));
    const grouping = section.dataset.grouping === 'true';
    const formatter = new Intl.NumberFormat(undefined, { useGrouping: grouping });
    numbers.forEach((number) => {
        number.textContent = formatter.format(0);
    });

    const play = () => {
        const started = performance.now();
        const frame = (now: number) => {
            const progress = Math.min(1, (now - started) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            numbers.forEach((number) => {
                const target = Math.max(0, Number(number.dataset.value) || 0);
                number.textContent = formatter.format(Math.round(target * eased));
            });
            if (progress < 1) window.requestAnimationFrame(frame);
        };
        window.requestAnimationFrame(frame);
    };

    if (!('IntersectionObserver' in window)) {
        play();
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            if (!entries.some((entry) => entry.isIntersecting)) return;
            observer.disconnect();
            play();
        },
        { threshold: 0.25 },
    );
    observer.observe(section);
}

document.querySelectorAll<HTMLElement>('[data-counter-section]').forEach(initializeCounters);

function initializeBrandCarousel(carousel: HTMLElement) {
    const track = carousel.querySelector<HTMLElement>('[data-brand-track]');
    if (!track) return;

    const status = carousel.querySelector<HTMLElement>('[data-brand-status]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const autoplay = carousel.dataset.autoplay === 'true' && !reducedMotion;
    const pauseOnInteraction = carousel.dataset.pause === 'true';
    const interval = Math.max(3000, Number(carousel.dataset.interval) || 4000);
    let timer: number | undefined;

    const step = () => {
        const item = track.querySelector<HTMLElement>('.site-brand-item');
        if (!item) return track.clientWidth;
        const gap = Number.parseFloat(window.getComputedStyle(track).columnGap) || 0;
        return item.getBoundingClientRect().width + gap;
    };
    const move = (direction: -1 | 1, announce = true) => {
        const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
        const atStart = track.scrollLeft <= 2;
        const left =
            direction === 1 && atEnd
                ? 0
                : direction === -1 && atStart
                  ? track.scrollWidth
                  : track.scrollLeft + step() * direction;
        track.scrollTo({ left, behavior: reducedMotion ? 'auto' : 'smooth' });
        if (announce && status)
            status.textContent =
                direction === 1 ? 'Showing next partner logos' : 'Showing previous partner logos';
    };
    const stop = () => {
        if (timer !== undefined) window.clearInterval(timer);
        timer = undefined;
    };
    const start = () => {
        stop();
        if (autoplay) timer = window.setInterval(() => move(1, false), interval);
    };

    carousel.querySelector('[data-brand-previous]')?.addEventListener('click', () => {
        move(-1);
        start();
    });
    carousel.querySelector('[data-brand-next]')?.addEventListener('click', () => {
        move(1);
        start();
    });
    if (pauseOnInteraction) {
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', (event) => {
            if (!carousel.contains(event.relatedTarget as Node | null)) start();
        });
    }
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    start();
}

document.querySelectorAll<HTMLElement>('[data-brand-carousel]').forEach(initializeBrandCarousel);

function initializeTestimonialCarousel(carousel: HTMLElement) {
    const track = carousel.querySelector<HTMLElement>('[data-testimonial-track]');
    if (!track) return;
    const cards = Array.from(track.querySelectorAll<HTMLElement>('.site-testimonial-card'));
    const dots = Array.from(carousel.querySelectorAll<HTMLButtonElement>('[data-testimonial-dot]'));
    const status = carousel.querySelector<HTMLElement>('[data-testimonial-status]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const autoplay = carousel.dataset.autoplay === 'true' && !reducedMotion;
    const pauseOnInteraction = carousel.dataset.pause === 'true';
    const interval = Math.max(3000, Number(carousel.dataset.interval) || 6000);
    let current = 0;
    let timer: number | undefined;

    const show = (index: number, announce = true) => {
        current = (index + cards.length) % cards.length;
        track.scrollTo({ left: cards[current].offsetLeft, behavior: reducedMotion ? 'auto' : 'smooth' });
        dots.forEach((dot, position) =>
            dot.setAttribute('aria-selected', position === current ? 'true' : 'false'),
        );
        if (announce && status) status.textContent = `Testimonial ${current + 1} of ${cards.length}`;
    };
    const stop = () => {
        if (timer !== undefined) window.clearInterval(timer);
        timer = undefined;
    };
    const start = () => {
        stop();
        if (autoplay) timer = window.setInterval(() => show(current + 1, false), interval);
    };

    carousel.querySelector('[data-testimonial-previous]')?.addEventListener('click', () => {
        show(current - 1);
        start();
    });
    carousel.querySelector('[data-testimonial-next]')?.addEventListener('click', () => {
        show(current + 1);
        start();
    });
    dots.forEach((dot) =>
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.index));
            start();
        }),
    );
    if (pauseOnInteraction) {
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', (event) => {
            if (!carousel.contains(event.relatedTarget as Node | null)) start();
        });
    }
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    show(0, false);
    start();
}

document.querySelectorAll<HTMLElement>('[data-testimonial-carousel]').forEach(initializeTestimonialCarousel);

function initializeGalleryLightbox(gallery: HTMLElement) {
    const dialog = gallery.querySelector<HTMLDialogElement>('[data-gallery-dialog]');
    const items = Array.from(gallery.querySelectorAll<HTMLButtonElement>('[data-gallery-item]'));
    const image = dialog?.querySelector<HTMLImageElement>('[data-gallery-dialog-image]');
    if (!dialog || !image || items.length === 0) return;
    const title = dialog.querySelector<HTMLElement>('[data-gallery-dialog-title]');
    const caption = dialog.querySelector<HTMLElement>('[data-gallery-dialog-caption]');
    const status = dialog.querySelector<HTMLElement>('[data-gallery-dialog-status]');
    let current = 0;

    const show = (index: number) => {
        current = (index + items.length) % items.length;
        const item = items[current];
        image.src = item.dataset.image ?? '';
        image.alt = item.dataset.alt ?? '';
        if (title) title.textContent = item.dataset.title ?? '';
        if (caption) caption.textContent = item.dataset.caption ?? '';
        if (status) status.textContent = `Image ${current + 1} of ${items.length}`;
    };
    items.forEach((item, index) =>
        item.addEventListener('click', () => {
            show(index);
            dialog.showModal();
        }),
    );
    dialog.querySelector('[data-gallery-close]')?.addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-gallery-previous]')?.addEventListener('click', () => show(current - 1));
    dialog.querySelector('[data-gallery-next]')?.addEventListener('click', () => show(current + 1));
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(current - 1);
        if (event.key === 'ArrowRight') show(current + 1);
    });
}

document.querySelectorAll<HTMLElement>('[data-gallery-lightbox]').forEach(initializeGalleryLightbox);

function initializeFaq(section: HTMLElement) {
    const items = Array.from(section.querySelectorAll<HTMLDetailsElement>('[data-faq-item]'));
    const search = section.querySelector<HTMLInputElement>('[data-faq-search]');
    const empty = section.querySelector<HTMLElement>('[data-faq-empty]');
    const allowMultiple = section.dataset.allowMultiple === 'true';

    if (!allowMultiple) {
        items.forEach((item) =>
            item.addEventListener('toggle', () => {
                if (!item.open) return;
                items.forEach((other) => {
                    if (other !== item) other.open = false;
                });
            }),
        );
    }
    search?.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        items.forEach((item) => {
            const matches = query === '' || (item.dataset.search ?? '').includes(query);
            item.hidden = !matches;
            if (matches) visible += 1;
        });
        if (empty) empty.hidden = visible !== 0;
    });
    section.querySelector('[data-faq-expand]')?.addEventListener('click', () => {
        items
            .filter((item) => !item.hidden)
            .forEach((item, index) => {
                item.open = allowMultiple || index === 0;
            });
    });
    section.querySelector('[data-faq-collapse]')?.addEventListener('click', () => {
        items.forEach((item) => (item.open = false));
    });
}

document.querySelectorAll<HTMLElement>('[data-faq-section]').forEach(initializeFaq);
