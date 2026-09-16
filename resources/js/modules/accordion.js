/** Single-open accordion (FAQs, mobile menu groups). */
export default function accordion(initial = null) {
    return {
        active: initial,

        toggle(id) {
            this.active = this.active === id ? null : id;
        },

        isActive(id) {
            return this.active === id;
        },
    };
}
