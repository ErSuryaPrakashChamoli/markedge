/**
 * Pointer-driven polish: cards tilt toward the cursor with a moving spotlight, hero orbs drift
 * with the pointer. Only on fine pointers with hover, never with reduced motion, and every
 * effect is purely visual (no layout, no content).
 */
const TILT_SELECTOR = '.card-hover';
const ORB_SELECTOR = '.orb';
const MAX_TILT = 5;

function supportsPointerEffects() {
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function bindTilt(card) {
    if (card.dataset.tilt) return;
    card.dataset.tilt = '1';

    card.addEventListener('pointermove', (event) => {
        const rect = card.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width;
        const y = (event.clientY - rect.top) / rect.height;
        card.style.setProperty('--mx', `${(x * 100).toFixed(1)}%`);
        card.style.setProperty('--my', `${(y * 100).toFixed(1)}%`);
        card.style.setProperty('--rx', `${((0.5 - y) * MAX_TILT * 2).toFixed(2)}deg`);
        card.style.setProperty('--ry', `${((x - 0.5) * MAX_TILT * 2).toFixed(2)}deg`);
    });

    card.addEventListener('pointerleave', () => {
        card.style.setProperty('--rx', '0deg');
        card.style.setProperty('--ry', '0deg');
    });
}

function bindParallax() {
    const orbs = Array.from(document.querySelectorAll(ORB_SELECTOR));
    if (!orbs.length || document.body.dataset.parallax) return;
    document.body.dataset.parallax = '1';

    let frame = null;
    window.addEventListener('pointermove', (event) => {
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = null;
            const dx = event.clientX / window.innerWidth - 0.5;
            const dy = event.clientY / window.innerHeight - 0.5;
            orbs.forEach((orb, index) => {
                const depth = 18 + (index % 3) * 10;
                orb.style.setProperty('--px', `${(dx * depth).toFixed(1)}px`);
                orb.style.setProperty('--py', `${(dy * depth).toFixed(1)}px`);
            });
        });
    }, { passive: true });
}

export function initInteractive() {
    if (!supportsPointerEffects()) return;
    document.documentElement.classList.add('js-pointer');
    document.querySelectorAll(TILT_SELECTOR).forEach(bindTilt);
    bindParallax();
}
