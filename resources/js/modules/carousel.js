// Galerie : sur mobile, carrousel qui défile tout seul (pause dès que le visiteur touche la galerie).
// Sur ordinateur, la galerie reste une grille (voir _event.scss).

const MOBILE = window.matchMedia('(max-width: 900px)');
const DELAY = 4000;

export function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('.gallery-track');
        const dots = carousel.querySelector('[data-dots]');
        const slides = [...track.children];
        if (slides.length < 2) return;

        const buttons = slides.map((_, i) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.setAttribute('aria-label', `Photo ${i + 1}`);
            b.addEventListener('click', () => go(i));
            dots.append(b);
            return b;
        });

        let index = 0;
        let timer = null;
        const go = (i) => {
            index = (i + slides.length) % slides.length;
            track.scrollTo({ left: index * track.clientWidth });
            mark();
        };
        const mark = () => buttons.forEach((b, i) => b.setAttribute('aria-current', i === index ? 'true' : 'false'));
        const start = () => {
            stop();
            if (MOBILE.matches && !matchMedia('(prefers-reduced-motion: reduce)').matches) timer = setInterval(() => go(index + 1), DELAY);
        };
        const stop = () => clearInterval(timer);

        track.addEventListener('scroll', () => {
            index = Math.round(track.scrollLeft / track.clientWidth);
            mark();
        }, { passive: true });
        ['touchstart', 'pointerdown'].forEach((t) => carousel.addEventListener(t, stop, { passive: true }));
        MOBILE.addEventListener('change', start);
        mark();
        start();
    });
}
