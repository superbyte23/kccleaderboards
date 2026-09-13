(() => {
    // Avatar client-side pre-compression.
    //
    // Originals from phones are multi-MB; the avatar only renders at ~112px
    // (256px stored). Downscaling in the browser before the upload keeps the
    // payload tiny (30-90 KB), so the 2MB server cap is effectively
    // unreachable and oversized POSTs never trip the host's php limits.
    //
    // Exposed as window.avatarCompress(file, $wire) and wired from the team
    // avatar input via Alpine x-on:change. When the image can't be decoded
    // (animated GIF, HEIC, exotic formats, old browsers) the raw file is
    // passed through untouched so behavior degrades gracefully.
    const MAX_DIM = 1024; // longest side, px
    const JPEG_Q = 0.85;  // fallback format quality
    const WEBP_Q = 0.8;   // preferred format quality
    const PASSTHROUGH_SIZE = 100 * 1024; // under this: don't bother

    function looksAnimatedGif(file) {
        if (!/gif/i.test(file.type) && !/gif$/i.test(file.name)) return false;
        return new Promise((resolve) => {
            const fr = new FileReader();
            fr.onload = () => {
                const dv = new DataView(fr.result);
                let frames = 0;
                let offset = 10; // skip GIF header
                try {
                    while (frames < 3 && offset + 10 <= dv.byteLength) {
                        const block = dv.getUint8(offset);
                        if (block === 0x3b) break; // trailer
                        const size = dv.getUint16(offset + 1, true);
                        offset += 3 + size;
                        if (block === 0x2c) frames += 1;
                    }
                } catch (e) {
                    frames = 1;
                }
                resolve(frames > 1);
            };
            fr.onerror = () => resolve(false);
            fr.readAsArrayBuffer(file);
        });
    }

    function decode(file) {
        // Prefer createImageBitmap for EXIF-safe, memory-friendly decode.
        if (window.createImageBitmap && ('imageOrientation' in window.createImageBitmap || !window.createImageBitmap)) {
            return window.createImageBitmap(file, { imageOrientation: 'from-image' })
                .catch(() => decodeViaImageElement(file));
        }
        return decodeViaImageElement(file);
    }

    function decodeViaImageElement(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.style.imageOrientation = 'from-image';
            img.onload = () => {
                URL.revokeObjectURL(url);
                resolve(img);
            };
            img.onerror = (e) => {
                URL.revokeObjectURL(url);
                reject(e);
            };
            img.src = url;
        });
    }

    function toBlob(canvas, mime, quality) {
        return new Promise((resolve) => {
            try {
                canvas.toBlob(resolve, mime, quality);
            } catch (e) {
                resolve(null);
            }
        });
    }

    function compressBitmap(bitmap) {
        const w = bitmap.width || bitmap.naturalWidth;
        const h = bitmap.height || bitmap.naturalHeight;
        if (!w || !h) return Promise.resolve(null);

        const scale = Math.min(1, MAX_DIM / Math.max(w, h));
        const cw = Math.max(1, Math.round(w * scale));
        const ch = Math.max(1, Math.round(h * scale));

        const canvas = document.createElement('canvas');
        canvas.width = cw;
        canvas.height = ch;
        const ctx = canvas.getContext('2d');
        const source = bitmap instanceof ImageBitmap ? bitmap : bitmap;
        ctx.drawImage(source, 0, 0, cw, ch);

        if ('close' in source && typeof source.close === 'function') {
            source.close();
        }

        const webpSupported = document.createElement('canvas').toDataURL('image/webp').indexOf('data:image/webp') === 0;
        const mime = webpSupported ? 'image/webp' : 'image/jpeg';
        const quality = webpSupported ? WEBP_Q : JPEG_Q;
        const ext = webpSupported ? 'webp' : 'jpg';

        return toBlob(canvas, mime, quality).then((blob) => {
            if (!blob) return null;
            const name = fileBaseName();
            return new File([blob], `${name}.${ext}`, { type: mime });
        });
    }

    function fileBaseName() {
        return 'avatar';
    }

    async function avatarCompress(file, $wire) {
        try {
            if (!file || !$wire) return;

            // Tiny files and non-decodable kinds go straight through.
            if (file.size <= PASSTHROUGH_SIZE || !/^image\//.test(file.type)) {
                $wire.upload('avatar', file);
                return;
            }

            const animated = await looksAnimatedGif(file);
            if (animated) {
                $wire.upload('avatar', file);
                return;
            }

            const bitmap = await decode(file);
            const compressed = await compressBitmap(bitmap);
            if (compressed && compressed.size <= file.size) {
                $wire.upload('avatar', compressed);
            } else {
                /* istanbul ignore next */ $wire.upload('avatar', file);
            }
        } catch (e) {
            /* istanbul ignore next */ if ($wire) $wire.upload('avatar', file);
        }
    }

    window.avatarCompress = avatarCompress;

    // Probe for automated verification.
    window.__avatarCompressor = {
        MAX_DIM,
        passthroughSize: PASSTHROUGH_SIZE,
        looksAnimatedGif,
        decode,
    };
})();