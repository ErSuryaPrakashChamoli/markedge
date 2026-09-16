/**
 * Header navigation state: mega-menu panels on desktop, drawer on mobile.
 * Keyboard: Escape closes everything; focus returns to the trigger.
 */
export default function siteNav() {
    return {
        drawerOpen: false,
        openPanel: null,
        closeTimer: null,

        init() {
            this.$watch('drawerOpen', (open) => {
                document.documentElement.classList.toggle('overflow-hidden', open);
            });
        },

        toggleDrawer() {
            this.drawerOpen = !this.drawerOpen;
        },

        closeDrawer() {
            this.drawerOpen = false;
        },

        showPanel(key) {
            clearTimeout(this.closeTimer);
            this.openPanel = key;
        },

        scheduleClose() {
            clearTimeout(this.closeTimer);
            this.closeTimer = setTimeout(() => (this.openPanel = null), 120);
        },

        togglePanel(key) {
            this.openPanel = this.openPanel === key ? null : key;
        },

        closeAll() {
            this.openPanel = null;
            this.drawerOpen = false;
        },

        isOpen(key) {
            return this.openPanel === key;
        },
    };
}
