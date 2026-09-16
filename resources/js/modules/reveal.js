/** Adds .is-visible to [data-reveal] elements once, as they enter the viewport. */
export function initReveal() {
    const elements = document.querySelectorAll('[data-reveal]:not(.is-visible)');
    if (!elements.length) return;

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced || !('IntersectionObserver' in window)) {
        elements.forEach((el) => el.classList.add('is-visible'));
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
        { rootMargin: '0px 0px -10% 0px', threshold: 0.1 },
    );

    elements.forEach((el) => {
        el.classList.add('reveal');
        observer.observe(el);
    });
}
