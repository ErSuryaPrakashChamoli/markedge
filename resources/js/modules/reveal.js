/**
 * Scroll reveal. Sections inside <main> (and the footer) reveal as they enter the viewport:
 * headings unmask word by word, grids and lists stagger their children, images settle from a
 * zoom. The visual signature comes from body[data-motion], set per page type in the layout.
 * Content is never hidden without JS: the initial state only applies once <html class="js-motion">
 * is present, and reduced-motion users get everything instantly.
 */
const STAGGER_SELECTOR = ':scope .grid, :scope ol, :scope ul:not([role="list"]):not(.divide-y), :scope ul.divide-y, :scope dl, :scope [data-stagger]';
const HEADING_SELECTOR = ':scope h1.text-h1, :scope h2.text-h2, :scope h2.text-h1, :scope .text-display';
const MEDIA_SELECTOR = ':scope picture, :scope img:not(picture img), :scope video';
const MAX_STAGGER_INDEX = 11;
const MAX_WORDS = 24;

function collectRoots() {
    const explicit = Array.from(document.querySelectorAll('[data-reveal]'));
    const main = document.getElementById('main');
    const sections = main ? Array.from(main.querySelectorAll(':scope > section, :scope > * > section, :scope > div:not([class*="grid"])')) : [];
    const footer = document.querySelector('body > footer');

    return [...new Set([...explicit, ...sections, ...(footer ? [footer] : [])])].filter((el) => !el.classList.contains('is-visible') && !el.closest('[data-no-reveal]'));
}

/** Wraps each word of a plain-text heading in a masked span so it can slide up into view. */
function splitWords(heading) {
    if (heading.dataset.split) return;
    if (Array.from(heading.children).some((child) => child.tagName !== 'SPAN')) return;
    const text = heading.textContent.trim();
    if (!text || text.split(/\s+/).length > MAX_WORDS) return;

    // Words become [text, className] pairs; inline spans (gradient highlights) keep their class.
    const words = [];
    heading.childNodes.forEach((node) => {
        const className = node.nodeType === 1 ? node.className : '';
        node.textContent.split(/\s+/).filter(Boolean).forEach((word) => words.push([word, className]));
    });

    heading.dataset.split = '1';
    heading.setAttribute('aria-label', text);
    heading.textContent = '';

    words.forEach(([word, className], index) => {
        const mask = document.createElement('span');
        mask.className = 'word';
        mask.setAttribute('aria-hidden', 'true');
        const inner = document.createElement('span');
        inner.className = 'word-inner' + (className ? ' ' + className : '');
        inner.style.setProperty('--w', String(index));
        inner.textContent = word;
        mask.appendChild(inner);
        heading.appendChild(mask);
        if (index < words.length - 1) heading.appendChild(document.createTextNode(' '));
    });
}

function prepare(root) {
    if (root.dataset.prepared) return;
    root.dataset.prepared = '1';

    root.querySelectorAll(HEADING_SELECTOR).forEach(splitWords);

    root.querySelectorAll(MEDIA_SELECTOR).forEach((media) => {
        if (media.closest('nav, header, a.logo')) return;
        media.classList.add('reveal-media');
    });

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
        { rootMargin: '0px 0px -12% 0px', threshold: 0.12 },
    );

    roots.forEach((el) => {
        el.classList.add('reveal');
        prepare(el);
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
