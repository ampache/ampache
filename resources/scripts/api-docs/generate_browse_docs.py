#!/usr/bin/env python3
"""Generate docs/browse/*-browse.md, one page per API browse type.

The filter and sort names are read straight out of the PHP query classes in
src/Module/Database/Query, so the pages cannot drift from what the code
accepts. The human description of each name comes from browse_reference.py,
and a name with no description there is an error: a new filter or sort has to
be documented in the commit that adds it.

The generator also reads which case labels get_sql_filter()/get_sql_sort()
actually handle. A name a query class declares but does not implement is
accepted by Browse and then does nothing, so it is listed separately instead of
being documented as if it worked.

Each page lists the API methods that build the browse, found by pairing the
ACTION constant of every method class under src/Module/Api/Method with the
Browse::set_type() calls it makes.

Usage:
    python resources/scripts/api-docs/generate_browse_docs.py [--check]
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

import browse_reference as ref
import format_md_tables

REPO_ROOT = Path(__file__).resolve().parents[3]
QUERY_DIR = REPO_ROOT / "src" / "Module" / "Database" / "Query"
METHOD_DIR = REPO_ROOT / "src" / "Module" / "Api" / "Method"
OUT_DIR = REPO_ROOT / "docs" / "browse"
INDEX_MD = REPO_ROOT / "docs" / "API-browse.md"

# Method actions that reach a browse type through a variable set_type() call, so the
# source scan cannot see it. `browse` picks its type from the catalog it is given.
EXTRA_METHOD_TYPES: dict[str, list[str]] = {
    "catalog": ["browse"],
}

# The API method files that serve older versions; their browses take the same filters
# and sorts, so listing them all would just be noise.
OLD_VERSION_DIRS = ("Api3", "Api4", "Api5", "Api6")

INDEX_INTRO = """
# API Browse methods

A browse method returns many items that can be filtered and sorted further, so `artists` is a browse
and `artist` is not. Browse methods use the Ampache Browse class, which lets a client ask for a
narrower or differently ordered list without running a search or post-processing the response.

Every browse method takes two extra parameters on top of its own: `cond` and `sort`.

## cond

Filter the objects the browse returns.

Send comma separated filter and value pairs, and split additional filters with `;`

e.g. `&cond=artist,1240;catalog,2`

A filter a browse does not list is ignored, so check the page for the type you are browsing.

Example:

* The `songs` method uses a song browse to return `song` objects.
* Filtering it by `genre` returns every song with that genre.

e.g. `https://music.com.au/server/json.server.php?action=songs&auth=eeb9f1b6056246a7d563f479f518bb34&cond=genre,111`

A method that already sets a filter of its own is overwritten by the same filter in `cond`, which can
change the response in ways the method never intended. `genre_artists?filter=215&cond=tag,111` returns
the artists for genre 111, not 215.
"""

INDEX_SORT = """
## sort

Change the order of the response.

Send the sort name and the direction, `ASC` or `DESC`.

e.g. `https://music.com.au/server/json.server.php?action=users&auth=f57766d256df0ad5e5ec163d35f05a21&sort=username,DESC`

**NOTE** Only one sort is applied to a browse. Sending a second replaces the first.

The default sort is usually `name`, ascending. Each method's `sort` docstring names its own default.

A sort the browse does not list is ignored and the default is kept, so nothing tells the client that
the order it asked for was refused.
"""

INDEX_TYPE_NOTES = """
**NOTE** A browse usually maps to one database table. `playlist_search` is the exception: it reads
`playlist` and `search` together so playlists and smartlists arrive as one list, with smartlist ids
prefixed, so search `2256` is returned as `smart_2256`.

**NOTE** `album_artist` and `song_artist` are the artist browse with that filter already applied, so
they take the artist filters and sorts.

**NOTE (API8)** `catalog` is an optional filter on the `album_artist`, `artist`, `album`, `album_disk`
and `podcast` browse types instead of a required parameter. Send it to restrict the children to one
catalog, or leave it out to get them from every catalog you can see. An album, disk or podcast belongs
to a single catalog and an artist reaches its catalogs through `catalog_map`, so the parent object
never needed a catalog to be addressed. API6 keeps the parameter mandatory, because Ampache7 serves
that version too.
"""

_FILTERS_RE = re.compile(r"const array FILTERS = \[(.*?)\];", re.S)
_SORTS_RE = re.compile(r"protected array \$sorts = \[(.*?)\];", re.S)
_STRING_RE = re.compile(r"'([^']*)'")
_ACTION_RE = re.compile(r"const string ACTION\s*=\s*'([^']+)'")
_SET_TYPE_RE = re.compile(r"set_type\('([a-z_]+)'\)")
_CASE_RE = re.compile(r"^\s*case '([^']+)':", re.M)
_MATCH_ARM_RE = re.compile(r"^\s*((?:'[^']+'\s*,\s*)*'[^']+')\s*=>", re.M)


def function_body(source: str, name: str) -> str:
    """The body of a PHP method, found by matching braces from its signature."""
    start = source.find("function %s(" % name)
    if start < 0:
        return ""

    brace = source.find("{", start)
    if brace < 0:
        return ""

    depth = 0
    for index in range(brace, len(source)):
        if source[index] == "{":
            depth += 1
        elif source[index] == "}":
            depth -= 1
            if depth == 0:
                return source[brace : index + 1]

    return source[brace:]


def handled_labels(body: str) -> set[str]:
    """Every case label a switch or match in this body answers to."""
    labels = set(_CASE_RE.findall(body))
    for arm in _MATCH_ARM_RE.findall(body):
        labels.update(_STRING_RE.findall(arm))

    return labels


def read_query(class_name: str) -> dict[str, object]:
    source = (QUERY_DIR / ("%s.php" % class_name)).read_text()
    filters_block = _FILTERS_RE.search(source)
    sorts_block = _SORTS_RE.search(source)

    return {
        "filters": _STRING_RE.findall(filters_block.group(1)) if filters_block else [],
        "sorts": _STRING_RE.findall(sorts_block.group(1)) if sorts_block else [],
        "filters_handled": handled_labels(function_body(source, "get_sql_filter")),
        "sorts_handled": handled_labels(function_body(source, "get_sql_sort")),
    }


def read_methods() -> dict[str, list[str]]:
    """browse type -> the API method actions that build a browse of that type."""
    types: dict[str, set[str]] = {}
    for path in sorted(METHOD_DIR.rglob("*Method.php")):
        if any(part in OLD_VERSION_DIRS for part in path.parts):
            continue

        source = path.read_text()
        action = _ACTION_RE.search(source)
        if action is None:
            continue

        for browse_type in _SET_TYPE_RE.findall(source):
            types.setdefault(browse_type, set()).add(action.group(1))

    for browse_type, actions in EXTRA_METHOD_TYPES.items():
        types.setdefault(browse_type, set()).update(actions)

    resolved: dict[str, set[str]] = {}
    for browse_type, actions in types.items():
        page = ref.TYPE_ALIASES.get(browse_type, browse_type)
        resolved.setdefault(page, set()).update(actions)

    return {page: sorted(actions) for page, actions in resolved.items()}


def fill(text: str, meta: dict[str, object]) -> str:
    item = str(meta["item"])
    items = str(meta["items"])
    match = str(meta["match"])

    return text.format(
        item=item,
        items=items,
        match=match,
        Item=item[:1].upper() + item[1:],
        Items=items[:1].upper() + items[1:],
        Match=match[:1].upper() + match[1:],
    )


def describe_filter(slug: str, name: str, meta: dict[str, object]) -> tuple[str, str]:
    entry = ref.FILTER_OVERRIDES.get(slug, {}).get(name) or ref.FILTERS.get(name)
    if entry is None:
        raise KeyError("filter '%s' (%s browse) has no description in browse_reference.py" % (name, slug))

    return entry[0], fill(entry[1], meta)


def describe_sort(slug: str, name: str, meta: dict[str, object]) -> str:
    entry = ref.SORT_OVERRIDES.get(slug, {}).get(name) or ref.SORTS.get(name)
    if entry is None:
        raise KeyError("sort '%s' (%s browse) has no description in browse_reference.py" % (name, slug))

    return fill(entry, meta)


def render_page(slug: str, meta: dict[str, object], query: dict[str, object], actions: list[str]) -> str:
    lines: list[str] = ["# %s" % meta["title"], ""]
    lines.append(
        "This page lists the filters and sorts the `%s` browse accepts. "
        "Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them." % meta["type"]
    )
    lines.append("")

    for note in meta["notes"]:
        lines.append("**NOTE** %s" % note)
        lines.append("")

    if actions:
        lines.append("## API methods using this browse")
        lines.append("")
        lines.append(", ".join("`%s`" % action for action in actions))
        lines.append("")

    declared_filters = sorted(str(name) for name in query["filters"])
    unhandled_filters = [name for name in declared_filters if name not in query["filters_handled"]]
    filters = [name for name in declared_filters if name not in unhandled_filters]

    lines.append("## Available browse filters")
    lines.append("")
    if filters:
        lines.append("Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`")
        lines.append("")
        lines.append("e.g. `cond=%s`" % example_condition(filters))
        lines.append("")
        lines.append("| Filter | Value | Description |")
        lines.append("|--------|-------|-------------|")
        for name in filters:
            value, description = describe_filter(slug, name, meta)
            lines.append("| `%s` | %s | %s |" % (name, value, description))
    else:
        lines.append("This browse takes no filters, so a `cond` parameter sent with it is ignored.")

    lines.append("")

    if unhandled_filters:
        lines.append(
            "**NOTE** These filters are listed by the browse but have no implementation, so sending them changes nothing: %s"
            % ", ".join("`%s`" % name for name in unhandled_filters)
        )
        lines.append("")

    declared_sorts = sorted(str(name) for name in query["sorts"])
    unhandled_sorts = [
        name for name in declared_sorts if name not in query["sorts_handled"] and name != "rand"
    ]
    sorts = [name for name in declared_sorts if name not in unhandled_sorts]

    lines.append("## Available browse sorts")
    lines.append("")
    if sorts:
        lines.append("Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.")
        lines.append("")
        lines.append("e.g. `sort=%s,DESC`" % ("name" if "name" in sorts else sorts[0]))
        lines.append("")
        lines.append("| Sort | Description |")
        lines.append("|------|-------------|")
        for name in sorts:
            lines.append("| `%s` | %s |" % (name, describe_sort(slug, name, meta)))
    else:
        lines.append("This browse takes no sorts. Its rows keep the order they are stored in.")

    lines.append("")

    if unhandled_sorts:
        lines.append(
            "**NOTE** These sorts are listed by the browse but have no implementation, so the rows come back in the default order: %s"
            % ", ".join("`%s`" % name for name in unhandled_sorts)
        )
        lines.append("")

    return format_md_tables.format_tables("\n".join(lines).rstrip() + "\n")


def render_index(pages: dict[str, dict[str, object]], methods: dict[str, list[str]]) -> str:
    """The hub page: how cond and sort work, plus which browse accepts what."""
    lines = INDEX_INTRO.strip().split("\n")
    lines.append("")

    valueless = sorted(name for name, entry in ref.FILTERS.items() if entry[0] == "none")
    lines.append(
        "These filters carry no value, so `filter,` with an empty value is enough: %s"
        % ", ".join("`%s`" % name for name in valueless)
    )
    lines.append("")
    lines.append(
        "Every other filter needs one. `unplayed` in particular only works as `cond=unplayed,1`; "
        "an empty value is read as `0` and the filter is dropped."
    )
    lines.append("")
    lines.append(INDEX_SORT.strip())
    lines.append("")
    lines.append("## Browse types and available methods")
    lines.append("")
    lines.append("Each page lists every filter and sort that browse accepts, and what each one does.")
    lines.append("")
    lines.append("| Browse | Type | API methods |")
    lines.append("|--------|------|-------------|")
    for slug, meta in sorted(pages.items()):
        actions = methods.get(slug, [])
        lines.append(
            "| [%s](https://ampache.org/api/browse/%s-browse) | `%s` | %s |"
            % (
                meta["title"],
                slug,
                meta["type"],
                ", ".join("`%s`" % action for action in actions) if actions else "-",
            )
        )

    lines.append("")
    lines.append(INDEX_TYPE_NOTES.strip())
    lines.append("")
    lines.append("## Which browse takes which filter")
    lines.append("")
    lines.append("A filter sent to a browse that does not list it is ignored, and logged as an unknown filter.")
    lines.append("")
    lines.append("| Filter | Browse types |")
    lines.append("|--------|--------------|")
    for name, slugs in sorted(pages_by_filter(pages).items()):
        lines.append("| `%s` | %s |" % (name, ", ".join("`%s`" % slug for slug in slugs)))

    lines.append("")

    return format_md_tables.format_tables("\n".join(lines).rstrip() + "\n")


def pages_by_filter(pages: dict[str, dict[str, object]]) -> dict[str, list[str]]:
    """filter name -> the browse pages that implement it."""
    owners: dict[str, list[str]] = {}
    for slug, meta in sorted(pages.items()):
        query = read_query(str(meta["query"]))
        for name in sorted(str(item) for item in query["filters"]):
            if name in query["filters_handled"]:
                owners.setdefault(name, []).append(slug)

    return owners


def example_condition(filters: list[str]) -> str:
    """A `cond` example built from filters this browse really has."""
    for candidate in ("starts_with,a", "like,a", "user,1", "follow_user,1"):
        if candidate.split(",")[0] in filters:
            first = candidate
            break
    else:
        first = "%s,1" % filters[0]

    if "catalog" in filters and not first.startswith("catalog,"):
        return "%s;catalog,2" % first

    return first


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check", action="store_true", help="exit 1 if a page would change")
    args = parser.parse_args()

    methods = read_methods()
    OUT_DIR.mkdir(parents=True, exist_ok=True)

    changed = False
    drift: list[str] = []
    for slug, meta in ref.TYPES.items():
        query = read_query(str(meta["query"]))
        try:
            page = render_page(slug, meta, query, methods.get(slug, []))
        except KeyError as error:
            print("ERROR %s" % error.args[0], file=sys.stderr)
            return 2

        target = OUT_DIR / ("%s-browse.md" % slug)

        for name in sorted(str(item) for item in query["sorts"]):
            if name != "rand" and name not in query["sorts_handled"]:
                drift.append("%s browse: sort '%s' is declared but get_sql_sort() ignores it" % (slug, name))

        for name in sorted(str(item) for item in query["filters"]):
            if name not in query["filters_handled"]:
                drift.append("%s browse: filter '%s' is declared but get_sql_filter() ignores it" % (slug, name))

        if target.exists() and target.read_text() == page:
            continue

        changed = True
        if args.check:
            print("OUTDATED docs/browse/%s-browse.md" % slug)
        else:
            target.write_text(page)
            print("UPDATED docs/browse/%s-browse.md" % slug)

    index = render_index(ref.TYPES, methods)
    if not INDEX_MD.exists() or INDEX_MD.read_text() != index:
        changed = True
        if args.check:
            print("OUTDATED docs/API-browse.md")
        else:
            INDEX_MD.write_text(index)
            print("UPDATED docs/API-browse.md")

    for line in drift:
        print("DRIFT %s" % line, file=sys.stderr)

    stale = sorted(
        path.name
        for path in OUT_DIR.glob("*-browse.md")
        if path.name[: -len("-browse.md")] not in ref.TYPES
    )
    for name in stale:
        print("STALE docs/browse/%s (no TYPES entry, delete it)" % name, file=sys.stderr)

    if args.check:
        return 1 if changed or stale else 0

    return 1 if stale else 0


if __name__ == "__main__":
    sys.exit(main())
