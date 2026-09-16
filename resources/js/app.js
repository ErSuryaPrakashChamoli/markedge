// Livewire ships Alpine; bundling both from the vendor ESM build gives one script
// for every page (see architecture §27.2) and lets us register Alpine components.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

import siteNav from './modules/nav';
import accordion from './modules/accordion';
import tabs from './modules/tabs';
import counter from './modules/counter';
import { initReveal } from './modules/reveal';

Alpine.data('siteNav', siteNav);
Alpine.data('accordion', accordion);
Alpine.data('tabs', tabs);
Alpine.data('counter', counter);

document.addEventListener('livewire:navigated', initReveal);
document.addEventListener('DOMContentLoaded', initReveal);

Livewire.start();
