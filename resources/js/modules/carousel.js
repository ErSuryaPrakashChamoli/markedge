/** Hero slide carousel: autoplay with a progress bar, pause on hover, arrows, dots and keyboard. */
export default function carousel({ count = 1, autoplay = 0 } = {}) {
    return {
        index: 0,
        count,
        autoplay,
        playing: false,
        progress: 0,
        timer: null,
        elapsed: 0,

        init() {
            if (this.count > 1 && this.autoplay > 0 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.resume();
            }
        },

        destroy() {
            this.pause();
        },

        go(i) {
            this.index = (i + this.count) % this.count;
            this.elapsed = 0;
            this.progress = 0;
        },

        next() {
            this.go(this.index + 1);
        },

        prev() {
            this.go(this.index - 1);
        },

        resume() {
            if (this.timer || this.autoplay <= 0 || this.count < 2) return;
            this.playing = true;
            this.timer = setInterval(() => {
                this.elapsed += 100;
                this.progress = Math.min(100, (this.elapsed / (this.autoplay * 1000)) * 100);
                if (this.elapsed >= this.autoplay * 1000) this.next();
            }, 100);
        },

        pause() {
            this.playing = false;
            clearInterval(this.timer);
            this.timer = null;
        },
    };
}
