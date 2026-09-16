/** Accessible tab list; arrow keys move between tabs. */
export default function tabs(initial) {
    return {
        active: initial,

        select(id) {
            this.active = id;
        },

        isActive(id) {
            return this.active === id;
        },

        onKeydown(event, ids) {
            const index = ids.indexOf(this.active);
            if (index === -1) return;

            if (event.key === 'ArrowRight') this.active = ids[(index + 1) % ids.length];
            if (event.key === 'ArrowLeft') this.active = ids[(index - 1 + ids.length) % ids.length];
            if (event.key === 'Home') this.active = ids[0];
            if (event.key === 'End') this.active = ids[ids.length - 1];
        },
    };
}
