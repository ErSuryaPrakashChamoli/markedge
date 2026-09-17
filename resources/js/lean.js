// Lean entry: Alpine core plus the collapse plugin for pages without Livewire components.
// Pinned to the Alpine version Livewire bundles so behaviour is identical on both entries.
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { bootSite } from './site';

Alpine.plugin(collapse);
window.Alpine = Alpine;

bootSite(Alpine);

Alpine.start();
