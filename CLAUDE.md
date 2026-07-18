# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Ampache is a PHP web-based audio/video streaming application and file manager. This is the `develop8` branch — a work-in-progress major rewrite (Ampache8) that **requires PHP 8.5+ only** (not 8.2/8.3/8.4 like `develop`/`release7`). It progressively migrates legacy procedural/static code into a DI-based, domain-driven architecture under `src/Module`. Expect legacy and new patterns to coexist in the same codebase; prefer the new pattern (see Architecture below) for any new code. This branch periodically merges in `develop`, so legacy code and API-version handling still closely track the stable branch.

## Repository structures & branches (READ FIRST)

This CLAUDE.md is the **single authoritative copy**. It is maintained on `develop8` and copied verbatim into the other structure working trees — the `.claude/` directories in the client/squashed trees are throwaway copies, so only ever edit the `develop8` one. The point of this section is to stop structure-specific edits from cross-contaminating branches.

The same `ampache/ampache` repo is checked out in several sibling working trees, one per long-lived branch. They differ in **on-disk layout ("structure"), not in features**. `develop8` is authoritative; the others are downstream distribution layouts that periodically **merge from** `develop8` (they lag it between merges — a fix on `develop8` is absent downstream until the next merge).

| Branch | Working dir | `STRUCTURE` | Web root |
|---|---|---|---|
| `develop8` | `ampache-develop8` (this tree) | `public` | `public/` |
| `client8` | `ampache-develop8-client` | `client` | user-facing pages under `public/client/`; infra dirs (`rest/`, `play/`, `server/`, `admin/`, `daap/`, `upnp/`, `webdav/`, `lib/`, `dist/`) stay at `public/` |
| `squashed8` | `ampache-develop8-squashed` | `squashed` | repo root — every `public/*` file is flattened up to `/` (the classic release layout) |

v7 siblings (`develop`, `client7`, `squashed7`) mirror the same three structures for the stable line. `patch8`/`patch7` are **prerelease/staging branches** on the `public` structure, built from `develop8` for release testing — **not** a distinct layout and **not** a place to author code; changes still originate on `develop8`.

**Source of truth for the active structure:** the `STRUCTURE` constant in `src/Config/Init/InitializationHandlerConfig.php` (`'public'` | `'client'` | `'squashed'`), surfaced as `AmpConfig::get('structure')` and consumed e.g. by `src/Module/System/AutoUpdate.php` to pick the matching release zip (`_squashed` suffix).

### Golden rule: where changes go

- **Almost everything** — features, bug fixes, API, DI, tests, docs, changelog — is authored on **`develop8`** and flows down to `client8`/`squashed8` via merge. Never write a feature or fix directly on a downstream branch.
- **Structure-specific edits stay on their own branch and must never leak into `develop8`.** These are the files that legitimately differ per layout; copying them across trees is the mistake this section exists to prevent:
  - `src/Config/Init/InitializationHandlerConfig.php` — the `STRUCTURE` constant itself (differs on every branch).
  - Path bases that hard-code `public/`: `src/Config/Bootstrap.php` (`.maintenance` path), `src/Config/functions.php` (theme/htaccess globs), `src/Module/Util/Ui.php` (template/icon globs), `src/Module/Util/FileSystem.php`, `src/Module/Cli/HtaccessCommand.php`. The `squashed` layout drops the `public/` segment from every `__DIR__ . '/../../public/...'` literal; `client` mostly keeps `public/`.
  - **`client` only:** call sites append the web-root suffix — `getWebPath('/client')` instead of `getWebPath()` — across `src/Module/Application/**`, `src/Gui/**`, and `src/Application/Api/Ajax/**`. On `public`/`squashed` the same calls take **no** argument. (`getWebPath(?string $suffix = '')` exists on all branches; only the passed argument differs.)
  - Build/tooling config: `.php-cs-fixer.php` (scans `public/ src/ tests/` on `public`, `./` on `squashed`), `package.json` (npm `copy:*` targets point at `public/lib/...` vs `lib/...`), `.gitignore` (`public/`-prefixed ignore paths), `vite.config.js`, `index.php`.
  - The relocated web-root files themselves (`public/*` vs `public/client/*` vs `/*`).

### Working across trees

- When a change must touch a file in the structure-specific list above, make it on `develop8` in the structure-neutral way and let each downstream branch's merge reconcile its own paths. Do **not** hand-edit the path in a downstream tree to "make it match".
- Before copying any file between trees, verify it is not one of the layout-divergent files above — if it is, the copy will break that tree's structure.
- To confirm which structure a tree is, read the `STRUCTURE` constant in `InitializationHandlerConfig.php`, not the directory name.

## Commands

Composer scripts (see `composer.json`):

```shell
composer qa            # syntax check + cs:check + tests — run this before submitting a PR
composer tests         # vendor/bin/phpunit -c phpunit.xml
composer stan          # vendor/bin/phpstan analyse (level 8, phpstan v2, see phpstan.neon)
composer stan-baseline # vendor/bin/phpstan --generate-baseline
composer cs:check      # php-cs-fixer dry-run (PSR-12 + PER-CS3x0, see .php-cs-fixer.php)
composer cs:fix        # php-cs-fixer, applies fixes
composer syntax        # resources/scripts/tests/syntax.sh (php -l across the codebase)
composer coverage      # phpunit with HTML coverage report in build/coverage
composer rector:dry    # rector process -n (dry-run codemods, rector v2, see rector.php)
composer rector:fix    # rector process
```

Run a single test file or method directly with phpunit:

```shell
vendor/bin/phpunit tests/Module/Album/Deletion/AlbumDeleterTest.php
vendor/bin/phpunit --filter testDeleteDeletesExpectedEntities tests/Module/Album/Deletion/AlbumDeleterTest.php
```

Test structure mirrors `src/` 1:1 (e.g. `src/Module/Album/Deletion/AlbumDeleter.php` -> `tests/Module/Album/Deletion/AlbumDeleterTest.php`).

Frontend assets (bootstrap, jquery, etc. — vendored into `public/lib/components`, plus Vite build):

```shell
npm run dev      # vite dev server on port 5177
npm run build    # vite build --minify false
```

Local dev via Docker: `docker-compose.yml` builds `docker/Dockerfilephp85`, maps the repo into `/var/www/html`, serves on port 8084.

CI (`.github/workflows/qa.yml`) runs `composer validate`, `composer update`, `composer run-script qa`, and `npm audit --production`.

## Architecture

### Bootstrapping (two-stage)

Startup is split into two files under `src/Config/`, both invoked via `src/Config/Init.php` (loaded by `public/*.php` entry points):

1. `Bootstrap.php` — registers the composer autoloader, builds the DI container from `DicBuilder.php`, loads `functions.php`, checks the minimum PHP version, includes `public/.maintenance` if present, and merges `$_GET`/`$_POST` into `$_REQUEST`.
2. `Init.php` (top-level) — takes the container from `Bootstrap.php` and runs `Ampache\Config\Init\Init::init()`, which is a **chain of `InitializationHandlerInterface` handlers** (`InitializationHandlerEnvironment`, `Config`, `GetText`, `DatabaseUpdate`, `Globals`, `Auth`, run in DI-array order). Each handler throws a specific exception (`ConfigFileNotFoundException`, `DatabaseOutdatedException`, `RequireAuthException`, etc.) that `Init::init()` catches and maps to a redirect (`install.php`, `update.php`, `login.php`, ...). On CLI, exceptions are rethrown instead of redirecting.

When adding a new startup concern, add an `InitializationHandler*` implementing `InitializationHandlerInterface` under `src/Config/Init/` rather than expanding `Bootstrap.php` or `Init.php` directly.

### Entry points and request flow

- `public/` is the web root (NOT the repo root — `index.php` at the repo root only shows a warning redirecting to `public/`).
- Most legacy pages (`public/*.php`, e.g. `public/albums.php`) boot via `src/Config/Init.php` then hand off to `Ampache\Module\Application\ApplicationRunner`, which dispatches based on an `action` query/body param through an `action_list` map of request-key -> handler class (implementing `ApplicationActionInterface`), resolved out of the container. Handlers live under `src/Module/Application/<Domain>/*Action.php`.
- CLI tooling is under `bin/cli`.

### API: multi-version architecture (important, non-obvious)

`src/Module/Api` implements **several concurrent, versioned API surfaces** that must keep working simultaneously — versions `3`, `4`, `5`, `6`, and the current `8` (version `7` was skipped/is unsupported). This is the single most complex part of the codebase:

- `ApiHandler::handle()` (`src/Module/Api/ApiHandler.php`) resolves the effective `api_version` per-request from the `version` param, the stored session (`Session::get_api_version`), and per-user preferences (`api_enable_3` .. `api_enable_8`, `api_force_version`), rolling old/disabled versions forward to the next enabled one.
- Each version has its own method-list class (`Api3`, `Api4`, `Api5`, `Api6`, `Api::METHOD_LIST` for v8) mapping action name -> handler, its own deprecated-action list (`$deprecated` for v3-6, `$deprecated8` for v8), its own output formatter (`Xml3_Data`/`Json4_Data`.../`Xml8_Data`, under `src/Module/Api/Output` and version-named `*_Data.php` files), and its own error method (`error3()`...`error6()`, `error()` for v8) on `ApiOutputInterface`.
- New API methods should implement `MethodInterface` and live under `src/Module/Api/Method/Api8/`; `ApiHandler::_executeHandler()` checks whether the resolved handler class implements `MethodInterface` via the DI container and, if so, calls it the "new" way — otherwise it falls back to a legacy `call_user_func_array` static-method call. Both paths must keep working until the legacy methods are fully migrated (marked `@todo cleanup` in the source).
- `ApiHandler::normalizeAction()`/`normalizeType()` translate REST-style resource/action pairs (e.g. `albums_songs`, `fetch-info`) into the canonical snake_case action names used by the method lists — REST routing lives in `XmlRestApiApplication`/`JsonRestApiApplication`, plain XML/JSON in `XmlApiApplication`/`JsonApiApplication`, plus dedicated `Daap`, `Subsonic`/`OpenSubsonic`, `Upnp`, and `Sse` application classes, all under `src/Module/Api`.
- When changing API behavior, check whether it needs to apply (or explicitly not apply) across all live versions — a fix in `Api8` alone will silently not apply to clients pinned to v6 via `api_force_version`.

### REST API

The REST surface (`{ampacheURL}/rest/{version}/{format}/{resource}...`, versions `3`/`4`/`5`/`6`/`8`, formats `json`/`xml`) is documented in **`docs/openapi.json`** (a full OpenAPI 3.0.3 spec — 176 paths, tagged by resource, with `ApiKeyAuthQuery`/`ApiKeyAuthHeader`/`BearerAuth` security schemes) and **`docs/REST-to-RPC.md`** (a human-readable table mapping each REST path + HTTP verb to the equivalent legacy RPC `action=` call). Consult both when adding or changing a REST endpoint:

- Add/update the path, verb, params, and schema in `docs/openapi.json`.
- Add/update the corresponding row in the `docs/REST-to-RPC.md` table (REST path -> RPC action -> alternative action, if any).

REST requests are **not** routed by Slim; they go through `mod_rewrite` rules in `public/rest/.htaccess.dist`, which pattern-match the URL (resource/id/nested-child/verb-suffix shapes) and rewrite to `public/server/{xml,json}.rest.php?version=..&action=..&type=..&filter=..`. Those entry scripts instantiate `Ampache\Module\Api\XmlRestApiApplication`/`JsonRestApiApplication` (`src/Module/Api`), which:

1. Read the rewritten query params from the request.
2. Call `ApiHandler::normalizeType()`/`normalizeAction()` to translate REST resource/action naming (e.g. `albums_songs`, `fetch-info`, plural resource names) into the canonical snake_case RPC action names used by the method lists.
3. Suffix the action for non-GET verbs (`DELETE` -> `_delete`, `PATCH` -> `_edit`, `PUT` -> `_create`).
4. Delegate to the same `ApiHandler::handle()` used by the plain RPC API — so REST is a thin routing/naming adapter in front of the versioned RPC engine described above, not a separate implementation. A REST-specific bug is often actually in `normalizeAction()`/`normalizeType()` rather than in the method handler itself.

`public/rest/index.php` itself runs `SubsonicApiApplication` (Subsonic clients hitting `/rest/*.view`); the OpenAPI/REST-to-RPC docs above describe the Ampache-native REST paths handled via `public/server/{format}.rest.php`, not the Subsonic protocol (see `docs/API-subsonic.md` for that).

### Dependency injection

- Uses `php-di/php-di`. The container is assembled in `src/Config/DicBuilder.php`, which aggregates one `service_definition.php` file per domain (e.g. `src/Module/Album/service_definition.php`, `src/Repository/service_definition.php`). When adding a new service/domain, register it in that domain's `service_definition.php` — don't edit `DicBuilder.php` unless adding an entirely new domain (note: `Module/Folder` is a recently-added domain not yet wired into `DicBuilder.php`'s alphabetical grouping — check before assuming every domain is registered there).
- Almost everything is programmed against an interface (`FooInterface`) with a single concrete implementation (`Foo`) bound in the service definitions — constructor-inject the interface, never the concrete class.

### `src/` domain layout

- `Module/<Domain>/` — business logic, organized by domain (Album, Artist, Song, Playlist, Catalog, Playback, Api, Authentication, Authorization, User, Folder, ...). This is where new logic should go. Domains are further split by responsibility, e.g. `Module/Album/Deletion/AlbumDeleter.php` + `AlbumDeleterInterface.php`.
- `Repository/` — repository classes for DB access (`FooRepository` + `FooRepositoryInterface`, extending `BaseRepository`/`BaseRepositoryInterface`) alongside `Repository/Model/` which holds the ORM-style model classes (`Album`, `Song`, `Artist`, etc.). Model classes still carry a fair amount of legacy application logic (including static methods like `Preference::get_by_user()`/`Userflag::garbage_collection()` called directly rather than through DI) that is meant to be progressively migrated into `Module/` domains — don't be surprised to see a `Module` class call directly into a `Repository/Model` static method.
- `Application/` — deprecated; legacy API code not yet moved into `Module/Api`.
- `Gui/` — deprecated; legacy PHPTal-based templating system, being merged into `Module/` domains over time.
- `Config/` — DI container bootstrap (`Bootstrap.php`, `DicBuilder.php`, `Init.php` + `Init/`), `AmpConfig`/`ConfigContainer` for reading `config/ampache.cfg.php`.
- `Plugin/` — third-party integration plugins (`AmpacheLastfm.php`, `AmpacheDiscogs.php`, etc.), each implementing `AmpachePluginInterface`.

### Coding conventions

- PHP 8.5+ syntax, `declare(strict_types=1)`, PSR-12 + `@PER-CS3x0` style (enforced by php-cs-fixer, see `.php-cs-fixer.php`) — notably: aligned `=` operators, alpha-sorted imports, short array syntax, trailing commas in multiline parameter lists, and a fixed `ordered_class_elements` layout (traits, cases, constants, static properties, properties, constructor, then methods by visibility, magic methods last — all alpha-sorted within each group).
- Every new PHP file needs the AGPL-3.0 license header — see `CONTRIBUTING.md` for the exact template.
- Prefer interface + final implementation class pairs (`FooInterface` / `final class Foo implements FooInterface`) with constructor-injected dependencies assigned to typed private properties, matching the existing `Module`/`Repository` pattern (see `AlbumDeleter.php` for a representative example). `readonly` classes/properties are used for immutable services (e.g. `Config\Init\Init`).
- Don't reformat or reorder unrelated/unchanged lines in a diff (explicitly called out in `CONTRIBUTING.md`).
- Comments: don't fragment into many short lines — use up to 120 chars of width per line; prefer single-line `//` comments over `/* */` blocks; keep any comment inside a function body to under 2 lines.
- PHPStan v2 runs at level 8 (`phpstan.neon`) against `src` and `public`, with `phpstan-baseline.neon` grandfathering existing violations — don't add new baseline entries; fix or annotate types instead.
- Rector v2 (`rector.php`) now covers `src/Module` in addition to `tests`, `src/Application`, `src/Config/Init`, `src/Gui`, `src/Plugin`, `src/Repository` — but explicitly **skips** `src/Module/Api`, `src/Module/System/Update/Migration`, and `src/Repository/Model`, targeting the PHP 8.5 rule set plus dead-code/code-quality/coding-style prepared sets.

### Testing

- PHPUnit 11, tests extend `Ampache\MockeryTestCase` (wraps `Mockery\Adapter\Phpunit\MockeryTestCase`) and mock dependencies via its `mock(FooInterface::class)` helper rather than instantiating real collaborators.
- Test suite root is `tests/`, bootstrapped by `tests/bootstrap.php` (just loads `src/Config/functions.php`).

### Frontend: reborn theme & mobile layout

The `reborn` theme (`public/themes/reborn/templates/`) is **desktop-only** by design. Mobile support was added as one `@media (max-width: 768px)` block at the END of `default.css`, plus small matching `@media` blocks in `dark.css`/`light.css` for drawer/toast backgrounds only. Everything is scoped to ≤768px so the desktop layout is untouched.

- `default.css` holds all layout geometry; `dark.css`/`light.css` carry ONLY colors (safe place for theme-specific mobile backgrounds).
- **The `body#main-page { min-width: 1024px }` trap**: on a phone the page is wider than the viewport, so mobile browsers shrink-to-fit *zoom*, and a zoomed page pins `position: fixed` (the webplayer) to the zoomed document, not the screen. The fix is to give content full width so there is NO horizontal overflow (→ no zoom). Detect regressions with `document.documentElement.scrollWidth > window.innerWidth` at 360px — NOT by eyeballing headless screenshots (headless browsers don't reproduce shrink-to-fit zoom).
- **Off-canvas nav**: `#sidebar` is a fixed left drawer (`transform: translateX(-100%)`; `body.sidebar-open` reveals it); content is full-width. Chrome added: hamburger `#mobile-menu-toggle` (first child of `#header`), backdrop `#mobile-nav-backdrop`, close `#mobile-drawer-close`. The toggle JS (`ToggleMobileSidebar`/`CloseMobileNav`) is **inline in `footer.inc.php`** — `src/js/*.js` is Vite-bundled (`src/js/main.js`), so inline avoids a rebuild.
- Non-obvious gotchas: (1) `#maincontainer { position: relative; z-index: 1 }` traps the fixed drawer below the body-level backdrop — set it `position: static; z-index: auto` on mobile. (2) The menu panel `#sidebar-page.sidebar-page-float` MUST stay `position: absolute`; making it static inflates its floated tab `<li>` and shoves the other tab icons below the menu. (3) The `<<< / >>>` collapse (`src/js/sidebar.js` + `sidebar_state` cookie) only works if you DON'T `!important`-force `#sidebar-content`/`#sidebar-content-light` display. (4) Hide the hamburger on desktop with `#header #mobile-menu-toggle { display: none }` — scoped to `#header` to outrank `#header a { display: inline-block }`.
- `#header` becomes a sticky flex top bar. The temp-playlist `#rightbar` drops down from the header button via the original `ToggleRightbarVisibility()` slideDown — NOT off-canvas, because `RightbarInit()` sets it `display: none` when the basket is empty. The AJAX `#ajax-loading` indicator is pinned top-right.
- Detail pages (album/artist/song) use `.item_right_info` (`float: right; max-width: 60%`) holding a floated `Art::display` image; on mobile it overlaps the Actions — fix with `float: none; display: flow-root` and pull the art left via `#content .info-box .box-content .item_art { float: left }`.

### Local dev & UI testing

- Docker dev instance on `localhost:8084` (`docker-compose.yml` → `docker/Dockerfilephp85`); log in as the local admin (`admin` / `demodemo`).
- Windows + Git-Bash: prefix `docker exec` with `MSYS_NO_PATHCONV=1` when passing unix paths (e.g. `MSYS_NO_PATHCONV=1 docker exec ampache php -l /var/www/html/...`).
- Dev cache: CSS/JS are cache-busted with a STATIC `?v=<version>`, so edits don't change the URL. `docker/data/sites-enabled/001-ampache.conf` sends `Cache-Control: no-cache` for `.css/.js/.map` (needs `mod_headers`, enabled in the Dockerfile) so a normal reload picks up edits; a browser may still need one hard-refresh (Ctrl+Shift+R) to drop an already-cached file.
- Drive/verify the real UI headlessly with Playwright: log in via `document.querySelector('form').submit()`, then `ajaxPut(jsAjaxUrl + '?page=stream&action=directplay&object_type=song&object_id=1&playtype=web_player')` to play into the webplayer. Test both Chromium and a mobile-UA Firefox for responsive checks.

## Changelog rules

Rules for writing `docs/CHANGELOG.md` and `docs/CHANGELOG-API.md`. Follow the existing entries — these files are append-at-top, newest release first.

### Shared rules (both files)

1. Release header is `## <Product> X.Y.Z` (`## Ampache 8.0.0` / `## API 6.9.2 Build 2`). Newest release at the top of the file.
2. After the header comes an optional **blurb**: one sentence per line, each on its own paragraph line. Use it only for large/ongoing themes, upgrade warnings, or versioning notes — not to restate bullets. Keep it short and plain: 1–4 lines, no marketing language, no multi-clause sentences. Prefix warnings with `**NOTE**`.
3. Sections appear in this fixed order and ONLY when non-empty: `### Added`, `### Changed`, `### Removed`, `### Fixed`. Every section title carries the version in parens to keep markdown anchors unique — the release string in CHANGELOG.md 7.x entries (`### Added (7.10.0)`), the int build/database version in CHANGELOG-API.md and Ampache8 entries (`### Added (800000)`, `### Added (692001)`).
4. One change per bullet. Short declarative line, no trailing period needed. Backtick every identifier: config keys, preference names, methods, parameters, columns, tables, file names, CLI commands.
5. Group related changes under a **category header bullet** with two-space-indented sub-items. Use a header when 2+ items share the category; a single one-off change stays a plain top-level bullet.
6. Log only what matters to someone upgrading **between released versions**, not the churn of building the current one. While the top release is a work-in-progress (e.g. `## Ampache 8.0.0`, marked WIP), do NOT log incremental fixes to a feature that was itself introduced in that same unreleased version — a fix to something that never shipped is invisible to users. Also skip trivial cosmetic tweaks ("moved a few pixels", "adjusted a colour"). Log the notable feature or behavior once, not each refinement to it. When unsure whether a change clears this bar, ask before adding it.

### CHANGELOG.md specifics

7. Established category headers (reuse these, don't invent synonyms): `Database` (suffix the update version when there is one, e.g. `Database 794004`), `Subsonic`, `Search`, `CLI`, `API`, `Browse`, `Plugins`, `Ampache Remote Catalogs`, `Config version NN`, `Translations YYYY-MM-DD`, `Upload`, `User`. Use `Theme` (not `Reborn theme` or a specific theme name) for theme/interface CSS changes.
8. Database sub-items name the exact tables/columns/preferences added or changed (e.g. "New `api_enable_8` preference ...", "New database tables `folder` and `folder_map`").
9. New config options get their own header (`Config version NN` or a "New Config Options" doc link) and each key is named exactly as it appears in `ampache.cfg.php.dist`.

### CHANGELOG-API.md specifics

10. Top-level bullets are **scope headers, not changes**: an API version (`API3`…`API8`), `ALL` (every live version: 3/4/5/6/8), `REST`, or a method-scoped header like `` `random` (API6 and API8)``. The actual changes are the indented sub-items.
11. Method-level sub-items use the `method: description` form (e.g. "flag: Use the `UserFlag::is_valid()` function for object type validation").
12. Never leave scope implicit. A fix that only applies to one version goes under that version header; a change under `ALL` is asserted to apply to every live API version.
13. Deprecations must name the removal version in bold: "deprecated and will be removed in **API9** (Use playlist_remove)". Parameter deprecations list each method + old parameter + replacement.
14. The blurb states which Ampache release ships the API version ("This version is being released for Ampache7 **only**") plus any client-facing version-number caveats.
