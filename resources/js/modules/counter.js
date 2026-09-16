/** Counts a number up when it scrolls into view. Respects reduced motion. */
export default function counter(target, { duration = 1200, prefix = '', suffix = '' } = {}) {
    return {
        value: 0,
        display: prefix + '0' + suffix,

        init() {
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (reduced || !('IntersectionObserver' in window)) {
                this.render(target);
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) return;
                observer.disconnect();
                this.animate();
            });

            observer.observe(this.$el);
        },

        animate() {
            const start = performance.now();
            const step = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                this.render(Math.round(target * eased));
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        },

        render(value) {
            this.value = value;
            this.display = prefix + value.toLocaleString() + suffix;
        },
    };
}
