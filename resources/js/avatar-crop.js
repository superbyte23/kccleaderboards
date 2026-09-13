// Square-crop avatar picker for Rally team avatars.
//
// Flow: user picks a file -> instant local preview (object URL) -> drag /
// resize a square crop box over the image -> "Apply" crops + compresses in
// the browser -> hands the resulting File to Livewire via $wire.upload().
// The server-side OptimizeAvatar still finalizes everything to 256x256 webp.
//
// Exposes window.avatarCrop() as an Alpine data factory used by the
// avatar field in the teams component. Degrades to a plain upload when the
// image can't be decoded (animated GIFs, HEIC, old browsers).

(() => {
    const MAX_DIM = 1024; // longest side after crop, px
    const JPEG_Q = 0.85; // fallback format quality
    const WEBP_Q = 0.8; // preferred format quality
    const MIN_BOX = 48; // smallest crop square, display px

    const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));

    function toBlob(canvas, mime, quality) {
        return new Promise((resolve) => {
            try {
                canvas.toBlob(resolve, mime, quality);
            } catch (e) {
                resolve(null);
            }
        });
    }

    async function cropAndCompress(image, box, scale) {
        // Native-pixel crop rect from display coords.
        let sx = box.x / scale;
        let sy = box.y / scale;
        let size = box.size / scale;
        sx = clamp(Math.round(sx), 0, image.naturalWidth - 1);
        sy = clamp(Math.round(sy), 0, image.naturalHeight - 1);
        size = Math.min(Math.round(size), image.naturalWidth - sx, image.naturalHeight - sy);

        const out = Math.min(MAX_DIM, size);
        const canvas = document.createElement('canvas');
        canvas.width = out;
        canvas.height = out;
        const ctx = canvas.getContext('2d');
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(image, sx, sy, size, size, 0, 0, out, out);

        const webpOk = canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        const mime = webpOk ? 'image/webp' : 'image/jpeg';
        const quality = webpOk ? WEBP_Q : JPEG_Q;
        const ext = webpOk ? 'webp' : 'jpg';

        const blob = await toBlob(canvas, mime, quality);
        if (!blob) return null;
        return new File([blob], `avatar.${ext}`, { type: mime });
    }

    function avatarCrop() {
        return {
            rawFile: null,
            previewUrl: '',
            image: null, // loaded <img> DOM node
            loaded: false,
            cropModal: false,
            busy: false,
            error: '',
            uploading: false,

            box: { x: 0, y: 0, size: 0 }, // display px square
            displayW: 0,
            displayH: 0,
            scale: 1,
            dragging: null,

            // ---- lifecycle ----
            init() {
                this.$el.querySelector('input[type="file"]')
                    ?.addEventListener('change', (e) => this.onFile(e));

                // Flux modals render as native <dialog> (browser "top layer",
                // above everything with a z-index). Our crop dialog must be a
                // native <dialog> too so it can stack above the team modal.
                this.$watch('cropModal', (open) => {
                    const d = this.$refs.cropDialog;
                    if (!d) return;
                    if (open) {
                        if (!d.open) d.showModal();
                    } else if (d.open) {
                        d.close();
                    }
                });
            },

            reset() {
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.rawFile = null;
                this.previewUrl = '';
                this.image = null;
                this.loaded = false;
                this.cropModal = false;
                this.busy = false;
                this.error = '';
                this.$refs.file.value = '';
            },

            onFile(e) {
                const file = e.target.files && e.target.files[0];
                this.error = '';
                if (!file) return;
                this.rawFile = file;

                // Animated GIFs can't be cropped/compressed sensibly -> pass
                // straight through to Livewire like before.
                if (/gif/i.test(file.type) || /gif$/i.test(file.name)) {
                    this.startUpload(file);
                    return;
                }

                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = URL.createObjectURL(file);
                this.loaded = false;

                const img = new Image();
                img.onload = () => {
                    this.image = img;
                    this.loaded = true;
                    this.cropModal = true;
                    this.$nextTick(() => this.layoutBox());
                };
                img.onerror = () => this.passThrough(file, 'preview failed');
                img.src = this.previewUrl;
            },

            layoutBox() {
                const img = this.image;
                if (!img) return;
                const pad = 8; // frame padding, px
                const maxW = Math.max(160, (this.$refs.frame ? this.$refs.frame.clientWidth : 320) - pad);
                const maxH = 320; // crop dialog height cap keeps it compact
                const dprW = Math.min(1, maxW / img.naturalWidth);
                const dprH = Math.min(1, maxH / img.naturalHeight);
                const dpr = Math.min(dprW, dprH);
                this.displayW = Math.max(1, Math.round(img.naturalWidth * dpr));
                this.displayH = Math.max(1, Math.round(img.naturalHeight * dpr));
                this.scale = this.displayW / img.naturalWidth;
                const size = Math.max(MIN_BOX, Math.round(Math.min(this.displayW, this.displayH) * 0.7));
                this.box = {
                    x: Math.round((this.displayW - size) / 2),
                    y: Math.round((this.displayH - size) / 2),
                    size,
                };
            },

            boxStyle() {
                const b = this.box;
                return {
                    width: b.size + 'px',
                    height: b.size + 'px',
                    transform: `translate(${b.x}px, ${b.y}px)`,
                };
            },

            // ---- interaction ----
            startMove(e, action) {
                if (!this.loaded || this.busy) return;
                e.preventDefault();
                this.dragging = {
                    action,
                    startX: e.clientX,
                    startY: e.clientY,
                    orig: { ...this.box },
                };
                window.addEventListener('pointermove', this._pm || (this._pm = (ev) => this.onPointer(ev)));
                window.addEventListener('pointerup', this._pu || (this._pu = () => this.endMove()));
            },

            _pm: null,
            _pu: null,

            onPointer(e) {
                const d = this.dragging;
                if (!d) return;
                const dx = e.clientX - d.startX;
                const dy = e.clientY - d.startY;

                if (d.action === 'move') {
                    this.box.x = clamp(d.orig.x + dx, 0, this.displayW - this.box.size);
                    this.box.y = clamp(d.orig.y + dy, 0, this.displayH - this.box.size);
                    return;
                }

                // Corner resize only: size = orig + dominant delta, anchored
                // at the box's top-left corner.
                const delta = Math.max(Math.abs(dx), Math.abs(dy));
                const sign = (dx + dy) < 0 ? -1 : 1;
                let size = Math.round(d.orig.size + sign * delta);
                size = clamp(size, MIN_BOX, Math.min(this.displayW - d.orig.x, this.displayH - d.orig.y));
                this.box = { ...d.orig, size };
            },

            endMove() {
                this.dragging = null;
                window.removeEventListener('pointermove', this._pm);
                window.removeEventListener('pointerup', this._pu);
            },

            async applyCrop() {
                if (!this.image || this.busy) return;
                this.busy = true;
                this.error = '';
                try {
                    const file = await cropAndCompress(this.image, this.box, this.scale);
                    if (file) this.startUpload(file);
                    else this.passThrough(this.rawFile, 'encode failed');
                } catch (err) {
                    this.passThrough(this.rawFile, err && err.message ? err.message : 'crop failed');
                } finally {
                    this.busy = false;
                }
            },

            passThrough(file, why) {
                if (!file) return;
                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = '';
                this.loaded = false;
                this.cropModal = false;
                this.startUpload(file);
            },

            startUpload(file) {
                this.uploading = true;
                this.cropModal = false;
                const done = () => {
                    this.uploading = false;
                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = '';
                    this.loaded = false;
                    if (this.$refs.file) this.$refs.file.value = '';
                };
                this.$wire.upload('avatar', file, done, done, () => {});
            },
        };
    }

    window.avatarCrop = avatarCrop;
    window.__avatarCropper = { cropAndCompress, MAX_DIM };
})();