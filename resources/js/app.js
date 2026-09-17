// Livewire entry: used only on pages that render a Livewire component (forms). Livewire ships its
// own Alpine, so this bundle must never be combined with lean.js on the same page.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { bootSite } from './site';

bootSite(Alpine);

Livewire.start();
