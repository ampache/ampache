#!/usr/bin/env python3
"""Enrich the response sections of docs/API-JSON-methods.md and
docs/API-XML-methods.md with per-field tables generated from the response
schemas in docs/openapi.json.

For every ``### <action>`` method whose GET 200 response has been wired to a
``$ref`` schema (see generate_openapi_schemas.py), this replaces ONLY the block
between the ``* return`` marker and the ``* throws`` marker with a
``Field | Type | Nullable | Optional | Notes`` table describing exactly what the
endpoint returns and which fields are optional / nullable. The block is wrapped
in ``<!-- GENERATED:RESPONSE ... -->`` anchors and regenerated deterministically,
so re-runs are idempotent.

The hand-written input-parameter tables, prose, ``* throws`` blocks and
``[Example]`` links are left untouched. Actions whose response has no schema yet
are skipped (so MD coverage grows automatically as more schemas are added).

XML method tables are derived from the same JSON schema for now (the field set is
identical; only serialisation differs) until Xml8_Data carries its own docblocks.

Usage:
    python resources/scripts/api-docs/generate_api_methods_md.py [--check]
"""
from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

import format_md_tables

REPO_ROOT = Path(__file__).resolve().parents[3]
OPENAPI = REPO_ROOT / "docs" / "openapi.json"
JSON_MD = REPO_ROOT / "docs" / "API-JSON-methods.md"
XML_MD = REPO_ROOT / "docs" / "API-XML-methods.md"

BEGIN = "<!-- GENERATED:RESPONSE:BEGIN -->"
END = "<!-- GENERATED:RESPONSE:END -->"

SHARED_BEGIN = "<!-- GENERATED:SHARED-REFS:BEGIN -->"
SHARED_END = "<!-- GENERATED:SHARED-REFS:END -->"

_ACTION_RE = re.compile(r"action=([A-Za-z0-9_]+)")

# Mutations answer with the generic success envelope; only POST responses carrying a
# real payload (democratic) are worth a generated field table.
POST_SCHEMA_SKIP = {"SuccessResponse"}

# schema name -> MD method anchor (a `### <action>` heading, GitHub-slugged) that
# documents that schema, so ref names in tables can link to it. Populated in main.
SCHEMA_ANCHOR: dict[str, str] = {}


# ---------------------------------------------------------------------------
# openapi helpers
# ---------------------------------------------------------------------------


def load_spec() -> dict:
    return json.loads(OPENAPI.read_text(encoding="utf-8"))


def resolve_ref(spec: dict, ref: str) -> dict:
    node = spec
    for part in ref.lstrip("#/").split("/"):
        node = node[part.replace("~1", "/").replace("~0", "~")]
    return node


def action_to_schema_ref(spec: dict) -> dict[str, str]:
    """Map RPC action -> the $ref set on its 200 response (only where one exists
    and points into components/schemas). GET wins; POST covers the few actions
    that return data from a mutation (e.g. democratic).

    x-rpc-mapping keys are `VERB /path` (verb-prefixed since the key-format
    normalisation); the verb selects the operation and the rest is the path."""
    out: dict[str, str] = {}
    for method in ("get", "post"):
        for key, rpc in spec.get("x-rpc-mappings", {}).items():
            match = _ACTION_RE.search(rpc)
            if not match:
                continue
            verb, _, path = key.partition(" ")
            if verb.lower() != method:
                continue
            op = spec.get("paths", {}).get(path, {}).get(method)
            if not op:
                continue
            schema = (
                op.get("responses", {})
                .get("200", {})
                .get("content", {})
                .get("application/json", {})
                .get("schema", {})
            )
            ref = schema.get("$ref", "")
            if not ref.startswith("#/components/schemas/"):
                continue
            if method == "get":
                out[match.group(1)] = ref
            elif ref_name(ref) not in POST_SCHEMA_SKIP:
                out.setdefault(match.group(1), ref)
    return out


# ---------------------------------------------------------------------------
# schema -> markdown
# ---------------------------------------------------------------------------


def ref_name(ref: str) -> str:
    return ref.rsplit("/", 1)[-1]


def link_ref(name: str) -> str:
    """Render a schema name as a link to the method that documents it, or as
    inline code when no such method exists."""
    anchor = SCHEMA_ANCHOR.get(name)
    return f"[{name}](#{anchor})" if anchor else f"`{name}`"


def type_str(schema: dict) -> str:
    if "$ref" in schema:
        return link_ref(ref_name(schema["$ref"]))
    if "oneOf" in schema:
        return " \\| ".join(type_str(s) for s in schema["oneOf"])
    kind = schema.get("type")
    if kind == "array":
        return f"array&lt;{type_str(schema.get('items', {}))}&gt;"
    if kind == "object":
        ap = schema.get("additionalProperties")
        if isinstance(ap, dict):
            return f"object&lt;string, {type_str(ap)}&gt;"
        return "object"
    return kind or "mixed"


def notes_for(schema: dict) -> str:
    """Compact one-level shape hint for inline objects / arrays of objects."""
    target = schema
    if schema.get("type") == "array":
        target = schema.get("items", {})
    if "$ref" in target:
        return f"see {link_ref(ref_name(target['$ref']))} fields"
    if target.get("type") == "object" and "properties" in target:
        return "`{" + ", ".join(target["properties"]) + "}`"
    return ""


def yn(flag: bool) -> str:
    return "YES" if flag else "NO"


def object_table(obj: dict) -> list[str]:
    required = set(obj.get("required", []))
    rows = [
        "| Field | Type | Nullable | Optional | Notes |",
        "|-------|------|:--------:|:--------:|-------|",
    ]
    for field, sub in obj.get("properties", {}).items():
        nullable = bool(sub.get("nullable"))
        optional = field not in required
        rows.append(
            f"| {field} | {type_str(sub)} | {yn(nullable)} | {yn(optional)} | {notes_for(sub)} |"
        )
    return rows


# Envelope metadata fields that wrap a list payload (standard, bare and browse
# envelopes). A wrapper is an object whose only non-meta property is the array.
_ENVELOPE_META = {"total_count", "md5", "catalog_id", "parent_id", "parent_type", "child_type"}


def describe_freeform(schema: dict) -> list[str]:
    """Render a schema that has no fixed properties (a free-form/polymorphic map)
    as prose rather than an empty table."""
    lines: list[str] = []
    desc = schema.get("description")
    if desc:
        lines.append(desc)
        lines.append("")
    ap = schema.get("additionalProperties")
    if isinstance(ap, dict):
        lines.append(f"Open map — each value is: {type_str(ap)}.")
    elif not lines:
        lines.append("Free-form object.")
    return lines


def is_list_envelope(schema: dict) -> tuple[bool, str | None]:
    """Detect a list envelope ({...meta, <key>: [items]}) and return the item
    array's property key. Handles standard, bare and browse envelopes."""
    props = schema.get("properties", {})
    data = [(k, v) for k, v in props.items() if k not in _ENVELOPE_META]
    if len(data) == 1 and data[0][1].get("type") == "array":
        return True, data[0][0]
    return False, None


# Xml8_Data emits XML by string concatenation (no array intermediate to document),
# so the XML field list is derived from the JSON data model. This note keeps the XML
# docs honest about how that model maps onto XML structure.
XML_NOTE = [
    "> **XML structure:** serialised inside a `<root>` element. Each object is an element",
    "> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also",
    "> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,",
    "> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON",
    "> model below, but element nesting/repetition differs from the JSON representation.",
]


def describe_schema(spec: dict, schema: dict) -> tuple[str, list[str]]:
    """Render one concrete schema. Returns (kind, lines) where kind is 'list',
    'object' or 'other' (used to pick the `* return` marker wording)."""
    is_list, key = is_list_envelope(schema)
    lines: list[str] = []
    if is_list and key is not None:
        lines.append(f"Returns a `{key}` list.")
        lines.append("")
        lines.extend(object_table(schema))
        item = schema["properties"][key].get("items", {})
        if "$ref" in item:
            item_name = ref_name(item["$ref"])
            item_schema = resolve_ref(spec, item["$ref"])
            lines.append("")
            lines.append(f"Each `{key}` entry ({link_ref(item_name)}):")
            lines.append("")
            lines.extend(object_table(item_schema))
        return "list", lines
    if schema.get("properties"):
        lines.append(schema.get("description") or "Returns a single object.")
        lines.append("")
        lines.extend(object_table(schema))
        return "object", lines
    return "other", describe_freeform(schema)


def render_variants(spec: dict, schema: dict) -> list[str]:
    """Render a top-level `oneOf` response: the parent description followed by one
    labelled block per alternative shape."""
    blocks: list[list[str]] = []
    if schema.get("description"):
        blocks.append([schema["description"]])
    for variant in schema["oneOf"]:
        target = resolve_ref(spec, variant["$ref"]) if "$ref" in variant else variant
        label = link_ref(ref_name(variant["$ref"])) if "$ref" in variant else type_str(variant)
        block = [f"**{label}**"]
        if target.get("description"):
            block += ["", target["description"]]
        # A bare array variant has no fields to tabulate; the label says it all.
        if target.get("type") != "array":
            block += ["", *describe_schema(spec, target)[1]]
        blocks.append(block)
    lines: list[str] = []
    for block in blocks:
        if lines:
            lines.append("")
        lines.extend(block)
    return lines


def render_body(spec: dict, ref: str, fmt: str) -> tuple[str, list[str]]:
    """Return (marker, body_lines) for the response block. ``fmt`` is 'json' or
    'xml' (controls the `* return` marker wording and adds an XML-structure note)."""
    schema = resolve_ref(spec, ref)
    lines: list[str] = []
    if fmt == "xml":
        lines.extend(XML_NOTE)
        lines.append("")
    if "oneOf" in schema:
        marker = "* return object|array" if fmt == "json" else "* return"
        lines.extend(render_variants(spec, schema))
        return marker, lines
    kind, body = describe_schema(spec, schema)
    marker = ("* return array" if kind == "list" else "* return object") if fmt == "json" else "* return"
    lines.extend(body)
    return marker, lines


# ---------------------------------------------------------------------------
# markdown surgery
# ---------------------------------------------------------------------------

_HEADING_RE = re.compile(r"^### (\S+)\s*$")


def replace_return_block(section: str, marker: str, body: list[str]) -> str | None:
    """Within one method section, replace the region from the `* return` marker
    up to (excluding) the `* throws` marker with the generated block. Returns the
    new section, or None if the anchors/markers were not found."""
    lines = section.splitlines()
    start = next((i for i, l in enumerate(lines) if l.strip().startswith("* return")), None)
    throws = next((i for i, l in enumerate(lines) if l.strip().startswith("* throws")), None)
    if start is None or throws is None or throws < start:
        return None
    block = [marker, "", BEGIN, *body, END, ""]
    new_lines = lines[:start] + block + lines[throws:]
    return "\n".join(new_lines) + ("\n" if section.endswith("\n") else "")


def enrich(text: str, spec: dict, action_ref: dict[str, str], fmt: str) -> tuple[str, list[str]]:
    """Split the file into `### ` sections and rewrite the return block of any
    section whose action has a schema. Returns (new_text, touched_actions)."""
    parts = re.split(r"(?m)^(### \S+\s*)$", text)
    # parts = [pre, heading1, body1, heading2, body2, ...]
    touched: list[str] = []
    out = [parts[0]]
    for i in range(1, len(parts), 2):
        heading = parts[i]
        body = parts[i + 1] if i + 1 < len(parts) else ""
        action = heading[4:].strip()
        ref = action_ref.get(action)
        if ref:
            marker, block = render_body(spec, ref, fmt)
            new_body = replace_return_block(body, marker, block)
            if new_body is not None:
                body = new_body
                touched.append(action)
        out.append(heading)
        out.append(body)
    return "".join(out), touched


def _rendered_sub_schemas(spec: dict, name: str) -> set[str]:
    """Schema names whose field tables actually render inside the section for a
    top-level response schema: a list envelope's item, and any oneOf variant
    (plus a variant's own list item). Mirrors describe_schema/render_variants, so
    an anchor is only claimed for a schema the section really documents."""
    schema = spec.get("components", {}).get("schemas", {}).get(name, {})
    out: set[str] = set()
    for variant in schema.get("oneOf", []):
        if "$ref" in variant:
            vname = ref_name(variant["$ref"])
            out.add(vname)
            out |= _rendered_sub_schemas(spec, vname)
    is_list, key = is_list_envelope(schema)
    if is_list and key is not None:
        item = schema["properties"][key].get("items", {})
        if "$ref" in item:
            out.add(ref_name(item["$ref"]))
    return out


def build_schema_anchors(spec: dict, action_ref: dict[str, str]) -> dict[str, str]:
    """Map each documented schema to the method section that shows its fields.

    First action that returns a given schema as its top-level payload wins
    (AlbumObject -> album, SongObject -> song); GitHub slugs a `### album` heading
    to `#album`. A second pass claims the schemas rendered *inside* a section — a
    list envelope's item (NowPlayingObject -> now_playing) and oneOf variants
    (DemocraticSongObject -> democratic) — without overriding a top-level owner."""
    anchors: dict[str, str] = {}
    for action, ref in action_ref.items():
        anchors.setdefault(ref_name(ref), action)
    for action, ref in action_ref.items():
        for name in _rendered_sub_schemas(spec, ref_name(ref)):
            anchors.setdefault(name, action)
    return anchors


def _all_referenced_schemas(spec: dict) -> set[str]:
    """Every schema name reached by a $ref from within another schema."""
    names: set[str] = set()

    def walk(node: object) -> None:
        if isinstance(node, list):
            for item in node:
                walk(item)
        elif isinstance(node, dict):
            ref = node.get("$ref")
            if isinstance(ref, str) and ref.startswith("#/components/schemas/"):
                names.add(ref_name(ref))
            for value in node.values():
                walk(value)

    for schema in spec.get("components", {}).get("schemas", {}).values():
        walk(schema)
    return names


def shared_reference_schemas(spec: dict, anchors: dict[str, str]) -> list[str]:
    """The small stubs referenced by other schemas that no method section documents
    (e.g. NamedReference, GenreReference). These get the shared-reference section."""
    referenced = _all_referenced_schemas(spec)
    return sorted(
        name
        for name in referenced
        if name not in anchors and not name.startswith("_")
    )


def render_shared_section(spec: dict, names: list[str]) -> list[str]:
    """Field tables for the shared reference stubs, each under its own `###` anchor
    so the `see <name> fields` links from the method tables resolve."""
    lines = [
        "Objects referenced by the field tables above (as `see <name> fields`) that no single "
        "method response documents on its own — the shared reference shapes and a few payloads "
        "carried inside another response.",
        "",
    ]
    schemas = spec.get("components", {}).get("schemas", {})
    for name in names:
        schema = schemas[name]
        lines.append(f"### {name}")
        lines.append("")
        if schema.get("description"):
            lines.append(schema["description"])
            lines.append("")
        lines.extend(object_table(schema))
        lines.append("")
    return lines


def replace_shared_section(text: str, body: list[str]) -> str:
    """Fill the block between the shared-reference markers. If the skeleton (the
    `## Shared reference objects` heading and markers) is absent, the file is left
    unchanged."""
    if SHARED_BEGIN not in text or SHARED_END not in text:
        return text
    pre, rest = text.split(SHARED_BEGIN, 1)
    _, post = rest.split(SHARED_END, 1)
    return f"{pre}{SHARED_BEGIN}\n" + "\n".join(body).rstrip("\n") + f"\n{SHARED_END}{post}"


def process_file(
    path: Path,
    spec: dict,
    action_ref: dict[str, str],
    shared: list[str],
    fmt: str,
    check: bool,
) -> bool:
    original = path.read_text(encoding="utf-8")
    updated, touched = enrich(original, spec, action_ref, fmt)
    updated = replace_shared_section(updated, render_shared_section(spec, shared))
    # Lint/align all GFM tables so the generated tables match the hand-written style.
    updated = format_md_tables.format_tables(updated)
    changed = updated != original
    rel = path.relative_to(REPO_ROOT)
    print(f"{rel}: {'CHANGED' if changed else 'up to date'} ({len(touched)} methods: {', '.join(touched) or '-'})")
    if changed and not check:
        path.write_text(updated, encoding="utf-8", newline="\n")
    return changed


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--check", action="store_true", help="exit 1 if a file would change")
    args = ap.parse_args()

    spec = load_spec()
    action_ref = action_to_schema_ref(spec)
    # Guard against the regression this generator once had: when the x-rpc-mapping key format changed
    # to `VERB /path`, action_to_schema_ref silently matched nothing and the response tables froze.
    # The spec always wires many GET responses to schemas, so an empty map means the mapping is broken.
    if not action_ref:
        print(
            "ERROR: no actions resolved to a response schema — the x-rpc-mappings key format likely "
            "changed and action_to_schema_ref no longer matches it. The response tables would freeze.",
            file=sys.stderr,
        )
        return 2
    SCHEMA_ANCHOR.clear()
    SCHEMA_ANCHOR.update(build_schema_anchors(spec, action_ref))
    shared = shared_reference_schemas(spec, SCHEMA_ANCHOR)
    # the shared stubs are documented under `### <Name>` in the shared section, which GitHub slugs
    # to the lowercased name; register those anchors so `see <name> fields` links resolve to them.
    SCHEMA_ANCHOR.update({name: name.lower() for name in shared})
    print(f"actions with response schemas: {', '.join(sorted(action_ref)) or '-'}")
    print(f"shared reference objects: {', '.join(shared) or '-'}")

    changed = False
    changed |= process_file(JSON_MD, spec, action_ref, shared, "json", args.check)
    changed |= process_file(XML_MD, spec, action_ref, shared, "xml", args.check)

    if args.check:
        return 1 if changed else 0
    return 0


if __name__ == "__main__":
    sys.exit(main())
