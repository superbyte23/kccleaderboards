(() => {
    // Constellation / plexus background: drifting gold dots linked into
    // futuristic geometric shapes. Subtle by design; static when the user
    // prefers reduced motion.
    //
    // NOTE: the bundle runs once per full page load, but wire:navigate
    // swaps page HTML without reloading — so boot() re-binds to the fresh
    // canvas on every Livewire navigation.
    let canvas = null;
    let ctx = null;
    let raf = 0;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const LINK = 130;
    // Star-universe palette: blue-white, white, gold, amber, violet.
    const PALETTE = [
        [220, 230, 255],
        [255, 255, 255],
        [229, 182, 75],
        [255, 190, 120],
        [196, 160, 255],
        [255, 90, 90],
    ];

    let w = 0;
    let h = 0;
    let parts = [];
    let frames = 0;
    let lastScrollY = window.scrollY;
    let lastT = performance.now();
    const mouse = { x: -9999, y: -9999 };

    function spawn() {
        return {
            x: Math.random() * w,
            y: Math.random() * h,
            vx: (Math.random() - 0.5) * 0.35,
            vy: (Math.random() - 0.5) * 0.35,
            r: Math.random() * 1.4 + 0.6,
            c: PALETTE[(Math.random() * PALETTE.length) | 0],
            tw: 0.4 + Math.random() * 1.2,
            ph: Math.random() * Math.PI * 2,
            depth: 0.5 + Math.random(),
            ox: 0,
            oy: 0,
            ovx: 0,
            ovy: 0,
        };
    }

    function targetCount() {
        return Math.max(48, Math.min(200, Math.floor((w * h) / 6500)));
    }

    function seed() {
        parts = Array.from({ length: targetCount() }, spawn);
    }

    // Re-fit after navigation/resize WITHOUT reshuffling: keep every
    // dot's position, velocity, and color; only wrap into the new
    // viewport, trim the surplus, or top up the shortfall.
    function fit() {
        const target = targetCount();
        for (const p of parts) {
            p.x = ((p.x % w) + w) % w;
            p.y = ((p.y % h) + h) % h;
            p.ox = 0;
            p.oy = 0;
            p.ovx = 0;
            p.ovy = 0;
        }
        if (parts.length > target) {
            parts.length = target;
        } else {
            while (parts.length < target) parts.push(spawn());
        }
    }

    function resize() {
        if (!canvas || !ctx) return;
        const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        w = window.innerWidth;
        h = window.innerHeight;
        canvas.width = Math.floor(w * dpr);
        canvas.height = Math.floor(h * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        if (parts.length) fit();
        else seed();
    }

    function link(ax, ay, ca, bx, by, cb, maxAlpha) {
        const dx = ax - bx;
        const dy = ay - by;
        const d = Math.hypot(dx, dy);
        if (d >= LINK || d === 0) return;
        const r = (ca[0] + cb[0]) >> 1;
        const g = (ca[1] + cb[1]) >> 1;
        const b = (ca[2] + cb[2]) >> 1;
        ctx.strokeStyle = `rgba(${r},${g},${b},${((1 - d / LINK) * maxAlpha).toFixed(3)})`;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(ax, ay);
        ctx.lineTo(bx, by);
        ctx.stroke();
    }

    const MOUSE_COLOR = [229, 182, 75];

    function step(now) {
        if (!ctx) return;
        ctx.clearRect(0, 0, w, h);

        // Frame-rate independent motion: normalize all decay/drift by dt
        // so the physics feel identical at 30fps or 120fps.
        const dt = Math.min(Math.max((now - lastT) / 16.667, 0.25), 3) || 1;
        lastT = now;
        const friction = Math.pow(0.93, dt);
        const easeBack = Math.pow(0.985, dt);

        // Law of motion: scroll velocity injects momentum; friction bleeds
        // it off so dots lag behind the scroll, then ease back to drifting.
        const sy = window.scrollY;
        const dy = Math.max(-60, Math.min(60, sy - lastScrollY));
        lastScrollY = sy;

        for (const p of parts) {
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            if (p.x < -20) p.x = w + 20;
            else if (p.x > w + 20) p.x = -20;
            if (p.y < -20) p.y = h + 20;
            else if (p.y > h + 20) p.y = -20;

            p.ovy = p.ovy * friction + dy * 0.03 * p.depth;
            p.ovx = p.ovx * friction + dy * 0.005 * p.depth;
            p.oy = (p.oy + p.ovy) * easeBack;
            p.ox = (p.ox + p.ovx) * easeBack;
        }

        for (let i = 0; i < parts.length; i++) {
            const pi = parts[i];
            const ax = pi.x + pi.ox;
            const ay = pi.y + pi.oy;
            for (let j = i + 1; j < parts.length; j++) {
                const pj = parts[j];
                link(ax, ay, pi.c, pj.x + pj.ox, pj.y + pj.oy, pj.c, 0.14);
            }
            link(ax, ay, pi.c, mouse.x, mouse.y, MOUSE_COLOR, 0.22);
        }

        for (const p of parts) {
            // Gentle star twinkle.
            const a = (0.3 + 0.3 * (0.5 + 0.5 * Math.sin(now * 0.001 * p.tw + p.ph))).toFixed(3);
            ctx.fillStyle = `rgba(${p.c[0]},${p.c[1]},${p.c[2]},${a})`;
            ctx.beginPath();
            ctx.arc(p.x + p.ox, p.y + p.oy, p.r, 0, Math.PI * 2);
            ctx.fill();
        }

        frames += 1;
    }

    window.addEventListener(
        'pointermove',
        (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        },
        { passive: true },
    );
    window.addEventListener('resize', resize);

    // Probe for automated verification.
    window.__constellation = {
        get count() {
            return parts.length;
        },
        get frames() {
            return frames;
        },
        get energy() {
            if (!parts.length) return 0;
            return parts.reduce((s, p) => s + Math.abs(p.ox) + Math.abs(p.oy), 0) / parts.length;
        },
        get hues() {
            return new Set(parts.map((p) => p.c.join(','))).size;
        },
        get snapshot() {
            return parts.map((p) => `${Math.round(p.x)},${Math.round(p.y)}`).join(';');
        },
    };

    function boot() {
        canvas = document.getElementById('constellation');
        if (!canvas) return;
        ctx = canvas.getContext('2d');
        lastScrollY = window.scrollY;
        lastT = performance.now();
        cancelAnimationFrame(raf);
        resize();

        if (reduceMotion) {
            step(lastT);
        } else {
            raf = requestAnimationFrame(function loop(t) {
                step(t);
                raf = requestAnimationFrame(loop);
            });
        }
    }

    document.addEventListener('livewire:navigated', boot);
    boot();
})();
