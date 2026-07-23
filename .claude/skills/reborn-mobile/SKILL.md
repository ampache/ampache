---
name: reborn-mobile
description: Reborn theme mobile/responsive layout gotchas (the min-width 1024px zoom trap, off-canvas nav, stacking contexts, webplayer positioning) plus how to run and drive the local Docker dev instance with Playwright. Use when editing theme CSS under public/themes, working on responsive/mobile layout, or verifying a change in the real running UI.
---

# Reborn theme, mobile layout, and local UI testing

## Frontend: reborn theme & mobile layout

The `reborn` theme (`public/themes/reborn/templates/`) is **desktop-only** by design. Mobile support was added as one `@media (max-width: 768px)` block at the END of `default.css`, plus small matching `@media` blocks in `dark.css`/`light.css` for drawer/toast backgrounds only. Everything is scoped to ≤768px so the desktop layout is untouched.

- `default.css` holds all layout geometry; `dark.css`/`light.css` carry ONLY colors (safe place for theme-specific mobile backgrounds).
- **The `body#main-page { min-width: 1024px }` trap**: on a phone the page is wider than the viewport, so mobile browsers shrink-to-fit *zoom*, and a zoomed page pins `position: fixed` (the webplayer) to the zoomed document, not the screen. The fix is to give content full width so there is NO horizontal overflow (→ no zoom). Detect regressions with `document.documentElement.scrollWidth > window.innerWidth` at 360px — NOT by eyeballing headless screenshots (headless browsers don't reproduce shrink-to-fit zoom).
- **Off-canvas nav**: `#sidebar` is a fixed left drawer (`transform: translateX(-100%)`; `body.sidebar-open` reveals it); content is full-width. Chrome added: hamburger `#mobile-menu-toggle` (first child of `#header`), backdrop `#mobile-nav-backdrop`, close `#mobile-drawer-close`. The toggle JS (`ToggleMobileSidebar`/`CloseMobileNav`) is **inline in `footer.inc.php`** — `src/js/*.js` is Vite-bundled (`src/js/main.js`), so inline avoids a rebuild.
- Non-obvious gotchas: (1) `#maincontainer { position: relative; z-index: 1 }` traps the fixed drawer below the body-level backdrop — set it `position: static; z-index: auto` on mobile. (2) The menu panel `#sidebar-page.sidebar-page-float` MUST stay `position: absolute`; making it static inflates its floated tab `<li>` and shoves the other tab icons below the menu. (3) The `<<< / >>>` collapse (`src/js/sidebar.js` + `sidebar_state` cookie) only works if you DON'T `!important`-force `#sidebar-content`/`#sidebar-content-light` display. (4) Hide the hamburger on desktop with `#header #mobile-menu-toggle { display: none }` — scoped to `#header` to outrank `#header a { display: inline-block }`.
- `#header` becomes a sticky flex top bar. The temp-playlist `#rightbar` drops down from the header button via the original `ToggleRightbarVisibility()` slideDown — NOT off-canvas, because `RightbarInit()` sets it `display: none` when the basket is empty. The AJAX `#ajax-loading` indicator is pinned top-right.
- Detail pages (album/artist/song) use `.item_right_info` (`float: right; max-width: 60%`) holding a floated `Art::display` image; on mobile it overlaps the Actions — fix with `float: none; display: flow-root` and pull the art left via `#content .info-box .box-content .item_art { float: left }`.

## Local dev & UI testing

- Docker dev instance on `localhost:8084` (`docker-compose.yml` → `docker/Dockerfilephp85`); log in as the local admin (`admin` / `demodemo`).
- Windows + Git-Bash: prefix `docker exec` with `MSYS_NO_PATHCONV=1` when passing unix paths (e.g. `MSYS_NO_PATHCONV=1 docker exec ampache php -l /var/www/html/...`).
- Dev cache: CSS/JS are cache-busted with a STATIC `?v=<version>`, so edits don't change the URL. `docker/data/sites-enabled/001-ampache.conf` sends `Cache-Control: no-cache` for `.css/.js/.map` (needs `mod_headers`, enabled in the Dockerfile) so a normal reload picks up edits; a browser may still need one hard-refresh (Ctrl+Shift+R) to drop an already-cached file.
- Drive/verify the real UI headlessly with Playwright: log in via `document.querySelector('form').submit()`, then `ajaxPut(jsAjaxUrl + '?page=stream&action=directplay&object_type=song&object_id=1&playtype=web_player')` to play into the webplayer. Test both Chromium and a mobile-UA Firefox for responsive checks.
