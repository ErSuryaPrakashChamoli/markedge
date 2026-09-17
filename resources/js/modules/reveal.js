/**
 * Scroll reveal. Sections inside <main> (and the footer) fade in as they enter the viewport;
 * lists and grids inside them stagger their children. The visual character of the motion comes
 * from body[data-motion], set per page type in the layout, so every section of the site has its
 * own signature while sharing one restrained timing model. Content is never hidden without JS:
 * the initial state only applies once <html class="js-motion"> is present.
 */
const STAGGER_SELECTOR = ':scope .grid, :scope ol, :scope ul:not([role="list"]):not(.divide-y), :scope ul.divide-y, :scope dl, :scope [data-stagger]';
const MAX_STAGGER_INDEX = 11;

function collectRoots() {
    const explicit = Array.from(document.querySelectorAll('[data-reveal]'));
    const main = document.getElementById('main');
    const sections = main ? Array.from(main.querySelectorAll(':scope > section, :scope > * > section, :scope > div:not([class*="grid"])')) : [];
    const footer = document.querySelector('body > footer');

    return [...new Set([...explicit, ...sections, ...(footer ? [footer] : [])])].filter((el) => !el.classList.contains('is-visible') && !el.closest('[data-no-reveal]'));
}

function prepareStagger(root) {
    if (root.dataset.staggered) return;
    root.dataset.staggered = '1';

    root.querySelectorAll(STAGGER_SELECTOR).forEach((group) => {
        if (group.closest('nav, header') || group.dataset.staggerDone) return;
        const items = Array.from(group.children).filter((child) => child.nodeType === 1);
        if (items.length < 2) return;
        group.dataset.staggerDone = '1';
        items.forEach((item, index) => {
            item.classList.add('reveal-item');
            item.style.setProperty('--i', String(Math.min(index, MAX_STAGGER_INDEX)));
        });
    });
}

export function initReveal() {
    document.documentElement.classList.add('js-motion');

    const roots = collectRoots();
    if (!roots.length) return;

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced || !('IntersectionObserver' in window)) {
        roots.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );

    roots.forEach((el) => {
        el.classList.add('reveal');
        prepareStagger(el);
        observer.observe(el);
    });

    // Anything already in view (the hero) reveals on the next frame so the transition runs.
    requestAnimationFrame(() => {
        roots.forEach((el) => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                el.classList.add('is-visible');
                observer.unobserve(el);
            }
        });
    });
}
