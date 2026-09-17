/**
 * Site behaviours shared by both entries. Registered against whichever Alpine instance is on the
 * page: Livewire's bundled Alpine (app.js) on pages with Livewire components, or the standalone
 * Alpine core (lean.js) everywhere else.
 */
import siteNav from './modules/nav';
import accordion from './modules/accordion';
import counter from './modules/counter';
import { initReveal } from './modules/reveal';
import { initInteractive } from './modules/interactive';

export function bootSite(Alpine) {
    Alpine.data('siteNav', siteNav);
    Alpine.data('accordion', accordion);
    Alpine.data('counter', counter);

    const boot = () => {
        initReveal();
        initInteractive();
    };

    document.addEventListener('livewire:navigated', boot);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
}
