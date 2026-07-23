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

Task scripts live in `composer.json` and `package.json` — read those for the current list. `composer qa` is the pre-PR gate (syntax + cs:check + tests).

Test structure mirrors `src/` 1:1 (e.g. `src/Module/Album/Deletion/AlbumDeleter.php` -> `tests/Module/Album/Deletion/AlbumDeleterTest.php`).

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
- Several type lists are **shared by every version** — `ObjectTypeGate::INDEX_TYPES` (used by `IndexMethod`, `ListMethod` *and* `GetIndexes6Method`), `AbstractGetArtMethod::TYPES`, `AdvancedSearchMethod::isSearchableType()`. Appending a type to one of these exposes it on v5/v6 too, where no formatter can render it; add a v8-only list instead.
- **`album_disk` has no API representation at all** despite being a full browse/search/rating/art/stats type, and it is the browsing unit whenever the per-user `album_group` preference is off. Read `docs/PLAN-album-disk-api.md` before adding it anywhere — including the live bug where `advanced_search type=album_disk` renders album_disk ids as songs.

### REST API

The REST surface (`{ampacheURL}/rest/{version}/{format}/{resource}...`, versions `3`/`4`/`5`/`6`/`8`, formats `json`/`xml`) is documented in **`docs/openapi.json`** (a full OpenAPI 3.0.3 spec — 176 paths, tagged by resource, with `ApiKeyAuthQuery`/`ApiKeyAuthHeader`/`BearerAuth` security schemes) and **`docs/REST-to-RPC.md`** (a human-readable table mapping each REST path + HTTP verb to the equivalent legacy RPC `action=` call). Consult both when adding or changing a REST endpoint:

- Add/update the path, verb and params in `docs/openapi.json`, then run `composer api:docs` (needs python). **Response schemas and error responses are generated** — from the `@return` array-shape docblocks on the `Json8_Data` `*_array()` builders and from `Api::getHttpCode()` — so a hand-edit inside a generated region is reverted on the next run. Read `resources/scripts/api-docs/README.md` before editing the spec by hand; `composer api:docs:check` (run by its own CI job) fails when the committed documents drift.
- `docs/openapi.json` describes **API version 8**. `docs/openapi-6.json` is a separate, hand-maintained spec pinned to **API version 6** for contract-testing a single version. API6 is served by both Ampache7 and Ampache8, so it documents only the surface both honour: no `/folder`, `/folders`, `/playlists/{playlist_id}/remove` (API8-only) and no `/random` (API8 only); errors are not mapped onto HTTP status codes (API3–6 always return 200 with the error in the body); response schemas come from the `Json6_Data` builders. `tests/Module/Api/Api6SpecConformanceTest.php` fails if the code drifts from it — **update the spec and that test together when API6 changes**, and never let an API6 response gain or lose a field without checking `ampache-develop` (Ampache7) still matches.
- Add/update the corresponding row in the `docs/REST-to-RPC.md` table (REST path -> RPC action -> alternative action, if any).

REST requests are **not** routed by Slim; they go through `mod_rewrite` rules in `public/rest/.htaccess.dist`, which pattern-match the URL (resource/id/nested-child/verb-suffix shapes) and rewrite to `public/server/{xml,json}.rest.php?version=..&action=..&type=..&filter=..`. Those entry scripts instantiate `Ampache\Module\Api\XmlRestApiApplication`/`JsonRestApiApplication` (`src/Module/Api`), which:

1. Read the rewritten query params from the request.
2. Call `ApiHandler::normalizeType()`/`normalizeAction()` to translate REST resource/action naming (e.g. `albums_songs`, `fetch-info`, plural resource names) into the canonical snake_case RPC action names used by the method lists.
3. Suffix the action for non-GET verbs (`DELETE` -> `_delete`, `PATCH` -> `_edit`, `PUT` -> `_create`).
4. Delegate to the same `ApiHandler::handle()` used by the plain RPC API — so REST is a thin routing/naming adapter in front of the versioned RPC engine described above, not a separate implementation. A REST-specific bug is often actually in `normalizeAction()`/`normalizeType()` rather than in the method handler itself.

`public/rest/index.php` itself runs `SubsonicApiApplication` (Subsonic clients hitting `/rest/*.view`); the OpenAPI/REST-to-RPC docs above describe the Ampache-native REST paths handled via `public/server/{format}.rest.php`, not the Subsonic protocol (see `docs/API-subsonic.md` for that).

### Subsonic / OpenSubsonic protocol (separate engine — the `api_version` machinery does NOT apply)

The Subsonic surface is served by `SubsonicApiApplication` (legacy) and `OpenSubsonic_Api`, formatted by **two pairs** of output classes under `src/Module/Api`: `Subsonic_{Xml,Json}_Data` (plain Subsonic 1.16.1) and `OpenSubsonic_{Xml,Json}_Data` (its OpenSubsonic extension). None of the versioned RPC/REST engine above (`Api8`, `api_force_version`, `*8_Data`) is involved.

- **OpenSubsonic-only response fields (e.g. artist `roles`) go ONLY in the `OpenSubsonic_*_Data` classes — never the plain `Subsonic_*_Data` ones.** `tests/Module/Api/SubsonicSpecConformanceTest.php` validates the legacy-Subsonic XML corpus against the strict Subsonic **1.16.1 XSD** (an unknown element/attribute fails) and the OpenSubsonic JSON corpus against `docs/openapi-opensubsonic.json`; the other two corpora (OpenSubsonic XML, Subsonic JSON) have no schema and are unchecked. So a field added to a `Subsonic_*` class, or an OpenSubsonic JSON field the OpenAPI spec doesn't document, breaks conformance — add the field to that spec too (its schemas set no `additionalProperties`, so documented-or-not is what matters).
- The artist index builders (`_addArtistArray`/`_getArtistArray`, `_addIndex`/`_getIndex` in both `*_Data` classes) are fed by **two different SQL sources** — `Artist::get_id_arrays()` for `getArtists` and `Catalog::get_artist_arrays()` for `getIndexes`. A column added to one query but not the other makes the same builder emit different output per endpoint (this is how `getIndexes` once silently dropped the `artist` role). Keep the two row shapes in sync, and thread any added key through **every** `@param`/`@return` array-shape docblock in the call chain or PHPStan level 8 fails (`argument.type`, `nullCoalesce.offset`).
- OpenSubsonic *extensions* (named capabilities like `songLyrics`, `formPost`) are a different concept, hardcoded in `OpenSubsonic_Api::getopensubsonicextensions()`; a standard optional field is NOT an extension and does not go there.
- **Changelog:** Subsonic/OpenSubsonic changes are logged in `docs/CHANGELOG.md` under the `* Subsonic` header — **not** `docs/CHANGELOG-API.md`, which covers only the native API (versions 3–8).

### Lists: playlists, searches and collections

Ampache has two list primitives and both are shaped for media. `playlist` is static and ordered but `playlist_data.object_type` is a media-only enum; `search` is dynamic and rule-driven but its `Search::VALID_TYPES` already spans `user`/`label`/`genre`/`playlist`/`podcast`. Curating a list of non-media objects is not possible today.

**Collections** — a third primitive for exactly that — is designed and agreed but not implemented. Read `docs/PLAN-collections.md` before adding anything that curates a list of objects (it is gated on `docs/PLAN-album-disk-api.md` landing first). In particular: do **not** widen `playlist_data.object_type`. `Playlist::get_items()`, `get_media_count()`, `get_total_duration()`, `get_random_items()` and `Stream_Playlist` all assume streamable rows, so a non-media type in that table breaks them.

**Playlists are song lists at the API boundary, deliberately** — not because the data says so. `playlist_data` really does hold seven media types, but `playlists`/`playlist_songs`/`list`/`index` all merge playlists with smartlists (ids prefixed `smart_`), and a smartlist there is always a *song* search. Making the playlist half polymorphic would give one response shape two meanings. So API8 filters items to songs, counts with `get_media_count('song')`, and sums song time only. Mixed playlist contents get their own method path (`playlist_items`, a follow-on in the plan doc) — do not widen the `playlists` response to carry them.

Anything reading `Playlist::get_items()` directly still gets mixed `object_type` values and must honour them.

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

### Database migrations

- A migration that throws **aborts the whole update and does not record its version**, so every install on that version is stuck until the migration itself is fixed. When one is reported broken, **fix that migration in place — never add a follow-up migration to repair it**; the failed one never recorded, so the corrected version re-runs everywhere.
- There is no transaction around a migration, so a partial failure stays committed and the re-run replays earlier statements: every statement must be idempotent.
- Before writing or reviewing one, use the `database-migrations` skill (`.claude/skills/database-migrations/SKILL.md`) — unique-key collisions, nullable-column joins, the case-insensitive collation, the Ampache7 downgrade obligation and how to prove it against real data.

### Coding conventions

- PHP 8.5+ syntax, `declare(strict_types=1)`, PSR-12 + `@PER-CS3x0` style — all mechanically enforced by `composer cs:fix` (see `.php-cs-fixer.php`), so let the fixer settle formatting rather than hand-matching it.
- Every new PHP file needs the AGPL-3.0 license header — see `CONTRIBUTING.md` for the exact template.
- Prefer interface + final implementation class pairs (`FooInterface` / `final class Foo implements FooInterface`) with constructor-injected dependencies assigned to typed private properties, matching the existing `Module`/`Repository` pattern (see `AlbumDeleter.php` for a representative example). `readonly` classes/properties are used for immutable services (e.g. `Config\Init\Init`).
- Don't reformat or reorder unrelated/unchanged lines in a diff (explicitly called out in `CONTRIBUTING.md`).
- **Comments (hard rule — check the diff before you finish):** every comment line you write must be filled to **100-120 characters** before it wraps; the only line allowed to be shorter is the last one of a wrapped block, a `@param`/`@return` tag, or the method-name line of a docblock. Never end a comment line early because the phrase ended — keep filling to the margin.
  - One comment per idea, not one per statement. If consecutive lines of code each carry their own short `//`, that is the fragmentation this rule forbids: delete them and write one wide comment above the block.
  - A comment inside a function body is at most 2 lines. Prefer `//` over `/* */`.
  - Verify, don't assume: run `git diff -U0 -- '*.php' | grep -E '^\+\s*(//|\*\s)' | awk '{ print length($0)-1, $0 }' | sort -n` and rewrite every added line under 100 chars that is not one of the exceptions above.
- PHPStan v2 runs at level 8 (`phpstan.neon`) against `src` and `public`, with `phpstan-baseline.neon` grandfathering existing violations — don't add new baseline entries; fix or annotate types instead.
- Rector v2 (`rector.php`) now covers `src/Module` in addition to `tests`, `src/Application`, `src/Config/Init`, `src/Gui`, `src/Plugin`, `src/Repository` — but explicitly **skips** `src/Module/Api`, `src/Module/System/Update/Migration`, and `src/Repository/Model`, targeting the PHP 8.5 rule set plus dead-code/code-quality/coding-style prepared sets.

### Testing

- Tests extend `Ampache\MockeryTestCase` and mock dependencies via its `mock(FooInterface::class)` helper rather than instantiating real collaborators.

### Frontend: reborn theme & mobile layout

The `reborn` theme is desktop-only by design; mobile is one `@media (max-width: 768px)` block at the end of `default.css`. Before touching theme CSS or responsive layout, use the `reborn-mobile` skill (`.claude/skills/reborn-mobile/SKILL.md`) — it holds the zoom trap, off-canvas nav and stacking-context gotchas.

### Frontend: page navigation & AJAX URLs

Two unrelated things are both called "ajax URLs"; don't confuse them.

- **The RPC endpoint** — `jsAjaxUrl` / `Ajax::url()` -> `<web_path>/server/ajax.server.php?page=X&action=Y`, built by `src/Module/Util/AjaxUriRetriever.php` and exposed in `public/templates/js_globals.php`. Consistent, no fragment, fine as-is.
- **Page navigation** — `src/js/ajax.js` intercepts link clicks and navigates with the History API, so the URL bar always shows the real, server-routable page (`/browse.php?action=album`). Two helpers in `src/js/base.js` do the work: `ampacheUrl()` decides whether a link is internal (real origin + pathname prefix test — never substring matching on `jsWebPath`), and `navigateToUrl()` pushes state and swaps `#guts`. `NavigateTo()` delegates to it, which is why the ~25 template call sites need no changes.
- Navigation gotchas: `popstate` handles back/forward but must ignore fragment-only moves (the prettyPhoto lightbox writes `#prettyPhoto`), which is what `loadedPage` in `ajax.js` tracks. Old `/index.php#browse.php?…` bookmarks still resolve via a one-shot shim that upgrades them to the real URL. Never re-introduce a hand-injected `#` in PHP — templates emit plain hrefs and the click delegate handles them.
- The `ajax_load` preference is **gone** (`Migration800022`), along with the popup web player it selected. Playback is always the embedded `#webplayer`, and `check_autoplay_append()`/`check_autoplay_next()` depend only on `play_type`. Don't reintroduce a preference to gate navigation: link interception was never conditional on it.
- **`web_path` is an absolute URL at runtime** (`src/Config/Init/InitializationHandlerConfig.php` rewrites it to `https://host[:port][/subdir]`), NOT the path from the config file. `raw_web_path` holds the path-only form. This trips up anything doing string math on link prefixes.

### Local dev & UI testing

Running or verifying the app in the real UI? Use the `reborn-mobile` skill (`.claude/skills/reborn-mobile/SKILL.md`) — Docker dev instance, credentials, cache behaviour and Playwright driving.

## Changelog rules

Writing `docs/CHANGELOG.md` or `docs/CHANGELOG-API.md`? Use the `changelog` skill (`.claude/skills/changelog/SKILL.md`) — it carries the section order, category headers and scope rules.
