---
name: changelog
description: Rules for writing docs/CHANGELOG.md and docs/CHANGELOG-API.md entries — section order, category headers, scope headers, and what is and isn't worth logging. Use whenever adding, editing or reviewing a changelog entry for Ampache or the API.
---

# Changelog rules

Rules for writing `docs/CHANGELOG.md` and `docs/CHANGELOG-API.md`. Follow the existing entries — these files are append-at-top, newest release first.

## Shared rules (both files)

1. Release header is `## <Product> X.Y.Z` (`## Ampache 8.0.0` / `## API 6.9.2 Build 2`). Newest release at the top of the file.
2. After the header comes an optional **blurb**: one sentence per line, each on its own paragraph line. Use it only for large/ongoing themes, upgrade warnings, or versioning notes — not to restate bullets. Keep it short and plain: 1–4 lines, no marketing language, no multi-clause sentences. Prefix warnings with `**NOTE**`.
3. Sections appear in this fixed order and ONLY when non-empty: `### Added`, `### Changed`, `### Removed`, `### Fixed`. Every section title carries the version in parens to keep markdown anchors unique — the release string in CHANGELOG.md 7.x entries (`### Added (7.10.0)`), the int build/database version in CHANGELOG-API.md and Ampache8 entries (`### Added (800000)`, `### Added (692001)`).
4. One change per bullet. Short declarative line, no trailing period needed. Backtick every identifier: config keys, preference names, methods, parameters, columns, tables, file names, CLI commands.
5. Group related changes under a **category header bullet** with two-space-indented sub-items. Use a header when 2+ items share the category; a single one-off change stays a plain top-level bullet.
6. Log only what matters to someone upgrading **between released versions**, not the churn of building the current one. While the top release is a work-in-progress (e.g. `## Ampache 8.0.0`, marked WIP), do NOT log incremental fixes to a feature that was itself introduced in that same unreleased version — a fix to something that never shipped is invisible to users. Also skip trivial cosmetic tweaks ("moved a few pixels", "adjusted a colour"). Log the notable feature or behavior once, not each refinement to it. When unsure whether a change clears this bar, ask before adding it.

## CHANGELOG.md specifics

7. Established category headers (reuse these, don't invent synonyms): `Database` (suffix the update version when there is one, e.g. `Database 794004`), `Subsonic`, `Search`, `CLI`, `API`, `Browse`, `Plugins`, `Ampache Remote Catalogs`, `Config version NN`, `Translations YYYY-MM-DD`, `Upload`, `User`. Use `Theme` (not `Reborn theme` or a specific theme name) for theme/interface CSS changes.
8. Database sub-items name the exact tables/columns/preferences added or changed (e.g. "New `api_enable_8` preference ...", "New database tables `folder` and `folder_map`").
9. New config options get their own header (`Config version NN` or a "New Config Options" doc link) and each key is named exactly as it appears in `ampache.cfg.php.dist`.

## CHANGELOG-API.md specifics

10. Top-level bullets are **scope headers, not changes**: an API version (`API3`…`API8`), `ALL` (every live version: 3/4/5/6/8), `REST`, or a method-scoped header like `` `random` (API6 and API8)``. The actual changes are the indented sub-items. This file is the **native** API only — Subsonic/OpenSubsonic protocol changes belong in `CHANGELOG.md` under the `Subsonic` header, not here.
11. Method-level sub-items use the `method: description` form (e.g. "flag: Use the `UserFlag::is_valid()` function for object type validation").
12. Never leave scope implicit. A fix that only applies to one version goes under that version header; a change under `ALL` is asserted to apply to every live API version.
13. Deprecations must name the removal version in bold: "deprecated and will be removed in **API9** (Use playlist_remove)". Parameter deprecations list each method + old parameter + replacement.
14. The blurb states which Ampache release ships the API version ("This version is being released for Ampache7 **only**") plus any client-facing version-number caveats.
