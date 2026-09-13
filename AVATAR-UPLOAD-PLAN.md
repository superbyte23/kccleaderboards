# PLAN — Adaptive Team Avatar Uploads

**Project:** `/var/www/hinugyawintramurals` — Rally leaderboards (Laravel 12 + Livewire + Flux)
**Status:** Proposed (not yet implemented)
**Owner:** opencode + user
Relates to: `OptimizeAvatar` (256×256 webp, quality 82), live host = InfinityFree free tier (no `symlink()`, limited PHP upload caps).

---

## 1. Problem statement

Team avatar uploads currently hit two ceilings on the live host:

1. **JS-side validation error** (what the user saw):
   `the avatar field must not be greater than 2048 kilobytes.`
   - Rule: `'avatar' => 'nullable|image|max:2048'` in `⚡teams.blade.php:79`.
   - Modern phone photos are 3–10 MB → most real uploads are rejected outright.

2. **InfinityFree PHP caps** (root enabler):
   - Host `upload_max_filesize` / `post_max_size` limits (free tier ~8 MB-ish / 10 MB).
   - Oversized POST bodies also silently drop the CSRF token → can surface as the **419 "Page Expired"** we chased earlier. Smaller payloads avoid the whole class.

Blocker for the current UX: the avatar only ever renders at **112 px** on the podium and 256 px in storage — the user does not need a 10 MB original. The pipeline should shrink before it ever hits the wire.

---

## 2. Goal

Let anyone pick ANY photo (e.g. a 12 MP phone shot) and have it:

- upload successfully (no 2048 error, no 419),
- never require the host's upload caps to be big,
- render sharp at 112 px (and store at 256 px webp as today),
- fail gracefully with a *helpful* message when genuinely rejected.

---

## 3. Solution options

### Option A — Client-side pre-compression (RECOMMENDED)
Downscale + re-encode the image **in the browser** (canvas) before Livewire uploads it, to a max dimension (~512–1024 px) at JPEG/WebP q≈0.8 ≈ **30–90 KB**. Then feed the result to `wire:model="avatar"` via Livewire's JS file-set.

- ✅ Uploads become tiny — 2 MB cap effectively unreachable with real photos.
- ✅ Avoids InfinityFree `post_max_size` → no 419 from oversized bodies.
- ✅ Zero new server deps (plain canvas + File API).
- ✅ Server `OptimizeAvatar` still finalizes 256 px webp off the compressed source (nothing changes there).
- ⚠️ Requires a small Alpine/vanilla JS upload handler in the teams component.
- ⚠️ EXIF orientation must be applied (`createImageBitmap`/`image-orientation`) or photos appear rotated.

### Option B — Raise the cap + rely on server resize only
Change `max:2048` → `max:10240` and hope InfinityFree's caps hold.

- ✅ Simplest code change.
- ❌ Uploads real megabytes on every choose → slow on shared hosting, still risks 419/500 when a phone shoots >host cap.
- ❌ Bandwidth wasted; no real UX win.

### Option C — Hybrid: client pre-compression + sane safety-net cap
Option A, plus bump `max:2048` → `max:5120` and give the `avatar` rule a friendly custom message as a fallback for exotic cases (animated GIFs, HEIC that canvas can't decode).

- ✅ Best of both; recommended final state.

> **Decision: Option C.**

---

## 4. Adaptive behavior matrix

| What the user picks | Client step | Server step | Outcome |
|---|---|---|---|
| Normal JPG/PNG photo (≤ ~10 MB) | canvas downscale to ≤1024 px, q 0.8 webp | `OptimizeAvatar` → 256 webp | Success, ~50 KB uploaded |
| Huge 12 MP photo (20+ MB) | same, still shrinks to ≤1024 px | `OptimizeAvatar` → 256 webp | Success |
| Animated GIF | canvas flatten loses animation → **skip client transform**, send original | `OptimizeAvatar` (first frame) | Works, may hit cap; friendly message if over |
| Corruption / non-image | — | `image` rule | Friendly message |
| File genuinely > safety cap after client step | — | `max:5120` custom message | Friendly message |

Client only transforms when it can decode the image; otherwise it passes the raw file through so behavior degrades gracefully.

---

## 5. Implementation steps

### 5.1 Client compressor — `resources/js/avatar-upload.js` (NEW, ~60 lines)
```
on choose(file):
  if file.size <= 100_000 (≈100 KB)  -> hand straight to Livewire (no-op)
  if not image mime / animated gif    -> hand straight to Livewire
  decode via createImageBitmap(blob) + image-orientation: 'from-image'
  width = min(1024, natural)  ; scale height proportionally (square-crop is done server-side)
  canvas → toBlob('image/webp', quality 0.8); if webp unsupported → 'image/jpeg', q 0.85
  new File([outBlob], name.withExtJpeg, {type: ...})
  Livewire.upload('avatar', file)   // same mechanism as wire:model
```
Expose guard `window.avatarCompress = fn` (probe for tests). Wire into the file input via `x-on:change` in `⚡teams.blade.php`.

### 5.2 Blade wiring — `resources/views/components/events/⚡teams.blade.php`
- Replace plain `<input type="file" wire:model="avatar">` with:
  ```html
  <input type="file" accept="image/*"
         x-data x-on:change="window.avatarCompress($event.target.files[0], $wire)"
         class="...same classes..." />
  ```
- Keep existing `@error('avatar')` block but show the **custom message** below.
- Add a `wire:loading` overlay (already present for `avatar` target — reuse).

### 5.3 Validation — same file
```php
'avatar' => 'nullable|image|max:5120', // 5 MB safety net; client compresses first
```
Custom message:
```php
$messages = [
  'avatar.max' => 'That image is still too large after compression (limit 5 MB). Please pick a smaller photo.',
  'avatar.image' => 'The avatar must be a JPG, PNG, WebP or GIF image.',
];
```

### 5.4 Server-side — `app/Actions/OptimizeAvatar.php`
No change required (already squash → 256 webp). Verify webp encode handles a JPEG-decoded canvas source (it will — same decode path). GIF animation stays as-is: current action encodes first frame to webp.

### 5.5 Config note — `config/livewire.php` (`temporary_file_upload`)
Leave `disk: env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', null)` default (private disk) — confirmed writable on live. Optionally add `'rules' => ['image', 'max:5120']` if we want hard fail at temp-upload stage in addition to validation.

---

## 6. Edge cases & risks

| Case | Handling |
|---|---|
| EXIF rotation (phone portrait) | `createImageBitmap(..., {imageOrientation:'from-image'})`; fallback for Safari → `image-orientation` CSS canvas draw |
| WebP unavailable (older Safari) | fallback to JPEG q 0.85 |
| Transparent PNG | keep PNG alpha by drawing on transparent bg + webp/jpeg keeps; webp preserves alpha anyway |
| Animated GIF | skip client compression; server encodes first frame (current behavior) |
| Non-image file | `image` rule catches with friendly message |
| Multiple rapid uploads | Livewire upload token per request already handles; keep input re-enabled after `livewire:update` |
| Electron/very old browsers | feature-detect `createImageBitmap`/`canvas.toBlob`; if missing → send raw file (server cap message may appear) |

---

## 7. Testing plan

Unit/feature (Pest):
- `tests/Feature/AvatarUploadPlanTest.php` (NEW):
  - validation accepts ≤ 5120 KB image, rejects > cap with custom message (assert `avatar.max` message text).
  - `OptimizeAvatar::run` still outputs 256×256 webp from a compressed-source JPEG/PNG.
  - non-image rejected with custom `avatar.image` message.

Browser (manual / Playwright in `/tmp/opencode`):
- Upload 12-MP JPG → no 2048 error, preview shows, save → live avatar loads.
- Upload animated GIF → friendly flow (either works or cap message, no crash).
- Mobile portrait photo → avatar not rotated.
- Re-edit team → old avatar removed, new shows.

Regression:
- `php artisan test` full suite stays green; Pint clean on new files.

---

## 8. Deployment (InfinityFree)

1. `npm run build` (or `node node_modules/vite/bin/vite.js build`) → ship `public/build/*` so `avatar-upload.js` is bundled.
2. Files to upload: `resources/js/avatar-upload.js` (source), rebuilt `public/build/*`, `resources/views/components/events/⚡teams.blade.php`.
3. No `php artisan config:cache` on host (no shell); bootstrap cache untouched (only `packages.php`/`services.php` present — fine).
4. Live: confirm temp uploads land in `storage/app/private/livewire-tmp` (writable, verified earlier).

---

## 9. Out of scope

- Raising InfinityFree PHP upload caps via custom `php.ini` (nice-to-have safety, but client compression makes it unnecessary).
- Multi-image / gallery uploads.
- Server-side image dimension limits (`dimensions` rule) — already capped by resize.

---

## 10. Acceptance criteria

- [ ] Picking a 12 MB phone photo succeeds end-to-end with no error message.
- [ ] Oversized / non-image files produce the friendly messages (no raw 2048 text anywhere — remove `max:2048`).
- [ ] Avatar still renders 256×256 webp; no regression in existing 55 tests.
- [ ] Portrait photos retain orientation.
- [ ] No 419 when uploading on live host.
```