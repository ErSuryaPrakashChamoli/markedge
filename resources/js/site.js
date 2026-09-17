/**
 * Site behaviours shared by both entries. Registered against whichever Alpine instance is on the
 * page: Livewire's bundled Alpine (app.js) on pages with Livewire components, or the standalone
 * Alpine core (lean.js) everywhere else.
 */
import siteNav from './modules/nav';
import accordion from './modules/accordion';
import counter from './modules/counter';
import { initReveal } from './modules/reveal';

export function bootSite(Alpine) {
    Alpine.data('siteNav', siteNav);
    Alpine.data('accordion', accordion);
    Alpine.data('counter', counter);

    document.addEventListener('livewire:navigated', initReveal);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReveal, { once: true });
    } else {
        initReveal();
    }
}
