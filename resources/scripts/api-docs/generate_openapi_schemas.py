#!/usr/bin/env python3
"""Generate OpenAPI response schemas for docs/openapi.json from the PHPStan
``@return array{...}`` docblocks on the ``*_array`` builder methods in
``src/Module/Api/Json8_Data.php``.

Those docblocks are the machine-readable source of truth for what every API v8
JSON response returns and which fields are optional / nullable. This script:

  1. Extracts each configured builder method's ``@return`` array-shape.
  2. Parses the PHPStan array-shape mini-language into JSON Schema.
  3. Adds an ``<Type>Object`` (item) schema and, for list endpoints, an
     ``<Types>Response`` envelope ({total_count, md5, <key>: [Object]}) to
     ``components.schemas``.
  4. Rewires the matching 200-response ``schema`` node to ``$ref`` the new
     schema, matching operations to builders via the ``x-rpc-mappings`` action
     names. Existing inline ``examples`` are preserved untouched.

openapi.json is written back with the exact on-disk formatting
(``json.dumps(indent=2, ensure_ascii=False)`` + trailing newline) so untouched
parts of the file produce no diff.

Usage:
    python resources/scripts/api-docs/generate_openapi_schemas.py [--check]

    --check   Do not write; exit 1 if the file would change (for CI).
"""
from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[3]
JSON8_DATA = REPO_ROOT / "src" / "Module" / "Api" / "Json8_Data.php"
OPENAPI = REPO_ROOT / "docs" / "openapi.json"

# ---------------------------------------------------------------------------
# Configuration: which object types to generate + how endpoints wire to them.
# Extend these tables to fan the generator out to more types.
# ---------------------------------------------------------------------------

# type key -> builder method (carrying the @return shape), object schema name,
# list-envelope schema name, and the JSON envelope key used by the list wrapper.
TYPES: dict[str, dict[str, str]] = {
    "album": {"builder": "albums_array", "object": "AlbumObject", "list": "AlbumsResponse", "key": "album"},
    "song": {"builder": "songs_array", "object": "SongObject", "list": "SongsResponse", "key": "song"},
    "artist": {"builder": "artists_array", "object": "ArtistObject", "list": "ArtistsResponse", "key": "artist"},
    "genre": {"builder": "genres_array", "object": "GenreObject", "list": "GenresResponse", "key": "genre"},
    "label": {"builder": "labels_array", "object": "LabelObject", "list": "LabelsResponse", "key": "label"},
    "live_stream": {"builder": "live_streams_array", "object": "LiveStreamObject", "list": "LiveStreamsResponse", "key": "live_stream"},
    "playlist": {"builder": "playlists_array", "object": "PlaylistObject", "list": "PlaylistsResponse", "key": "playlist"},
    "podcast_episode": {"builder": "podcast_episodes_array", "object": "PodcastEpisodeObject", "list": "PodcastEpisodesResponse", "key": "podcast_episode"},
    "podcast": {"builder": "podcasts_array", "object": "PodcastObject", "list": "PodcastsResponse", "key": "podcast"},
    "video": {"builder": "videos_array", "object": "VideoObject", "list": "VideosResponse", "key": "video"},
    "catalog": {"builder": "catalogs_array", "object": "CatalogObject", "list": "CatalogsResponse", "key": "catalog"},
    "license": {"builder": "licenses_array", "object": "LicenseObject", "list": "LicensesResponse", "key": "license"},
    "share": {"builder": "shares_array", "object": "ShareObject", "list": "SharesResponse", "key": "share"},
    "bookmark": {"builder": "bookmarks_array", "object": "BookmarkObject", "list": "BookmarksResponse", "key": "bookmark"},
    # users list returns only {id, username}; the single user endpoint returns the fuller UserObject.
    # Verified against a live server: the users list uses a bare envelope (no total_count/md5).
    "user_list": {"builder": "users_array", "object": "UserSummaryObject", "list": "UsersResponse", "key": "user", "envelope": "bare"},
    "user": {"builder": "user_array", "object": "UserObject", "list": "", "key": ""},
    "song_tag": {"builder": "song_tags_array", "object": "SongTagObject", "list": "SongTagsResponse", "key": "song_tag"},
    # Fixed-shape list/activity endpoints (note the bare envelopes with no total_count/md5).
    "list": {"builder": "lists_array", "object": "ListObject", "list": "ListsResponse", "key": "list"},
    "browse": {"builder": "browses_array", "object": "BrowseObject", "list": "BrowseResponse", "key": "browse", "envelope": "browse"},
    "now_playing": {"builder": "now_playing_array", "object": "NowPlayingObject", "list": "NowPlayingResponse", "key": "now_playing", "envelope": "bare"},
    "activity": {"builder": "timeline_array", "object": "ActivityObject", "list": "TimelineResponse", "key": "activity", "envelope": "bare"},
    "shout": {"builder": "shouts_array", "object": "ShoutObject", "list": "ShoutsResponse", "key": "shout", "envelope": "bare"},
}

# Within a generated object schema, replace a property's item/value subtree with
# a $ref to an already-defined schema (DRY reuse, mirroring the Folder schemas).
# (object schema name, property name) -> referenced schema name.
# Only applied when the property is an array; its items become the $ref.
REF_REUSE: dict[tuple[str, str], str] = {
    # These properties embed another object's full shape inline (verified
    # field-for-field identical to the target builder), so reference the shared
    # schema instead of duplicating it, mirroring the Folder schemas.
    ("AlbumObject", "tracks"): "SongObject",
    ("ArtistObject", "albums"): "AlbumObject",
    ("ArtistObject", "songs"): "SongObject",
    ("PodcastObject", "podcast_episode"): "PodcastEpisodeObject",
    # bookmark optional includes (documented loosely; the real payloads are these objects).
    ("BookmarkObject", "song"): "SongObject",
    ("BookmarkObject", "podcast_episode"): "PodcastEpisodeObject",
    ("BookmarkObject", "video"): "VideoObject",
}

# RPC action name (from x-rpc-mappings) -> schema to $ref on its 200 response.
WIRING: dict[str, str] = {
    # Albums
    "albums": "AlbumsResponse",
    "artist_albums": "AlbumsResponse",
    "genre_albums": "AlbumsResponse",
    "album": "AlbumObject",
    # Songs
    "songs": "SongsResponse",
    "album_songs": "SongsResponse",
    "artist_songs": "SongsResponse",
    "genre_songs": "SongsResponse",
    "license_songs": "SongsResponse",
    "playlist_songs": "SongsResponse",
    "smartlist_songs": "SongsResponse",
    "song": "SongObject",
    # Artists
    "artists": "ArtistsResponse",
    "genre_artists": "ArtistsResponse",
    "label_artists": "ArtistsResponse",
    "artist": "ArtistObject",
    # Genres
    "genres": "GenresResponse",
    "genre": "GenreObject",
    # Labels
    "labels": "LabelsResponse",
    "label": "LabelObject",
    # Live streams
    "live_streams": "LiveStreamsResponse",
    "live_stream": "LiveStreamObject",
    # Playlists (smartlists share the playlist shape)
    "playlists": "PlaylistsResponse",
    "user_playlists": "PlaylistsResponse",
    "smartlists": "PlaylistsResponse",
    "user_smartlists": "PlaylistsResponse",
    "playlist": "PlaylistObject",
    "smartlist": "PlaylistObject",
    # Podcasts
    "podcasts": "PodcastsResponse",
    "podcast": "PodcastObject",
    "podcast_episodes": "PodcastEpisodesResponse",
    "podcast_episode": "PodcastEpisodeObject",
    # Videos
    "videos": "VideosResponse",
    "video": "VideoObject",
    # Catalogs
    "catalogs": "CatalogsResponse",
    "catalog": "CatalogObject",
    # Licenses
    "licenses": "LicensesResponse",
    "license": "LicenseObject",
    # Shares
    "shares": "SharesResponse",
    "share": "ShareObject",
    # Bookmarks
    "bookmarks": "BookmarksResponse",
    "bookmark": "BookmarkObject",
    # Users (list summary vs full single)
    "users": "UsersResponse",
    "user": "UserObject",
    # Song tags — the REST /songs/{id}/tags endpoint returns a single flat tag
    # object (verified live), not the multi-song SongTagsResponse envelope.
    "song_tags": "SongTagObject",
    # Folder — /folder returns a single node; only /folders returns the envelope
    # (verified live: /folder was mis-wired to the response envelope).
    "folder": "FolderBrowseNode",
    "folders": "FolderBrowseResponse",
    # Fixed-shape / activity endpoints
    "list": "ListsResponse",
    "browse": "BrowseResponse",
    "now_playing": "NowPlayingResponse",
    "timeline": "TimelineResponse",
    "friends_timeline": "TimelineResponse",
    "last_shouts": "ShoutsResponse",
    # Polymorphic
    "index": "IndexResponse",
    # Deleted (three distinct per-type responses)
    "deleted_songs": "DeletedSongsResponse",
    "deleted_podcast_episodes": "DeletedPodcastEpisodesResponse",
    "deleted_videos": "DeletedVideosResponse",
}

# Only rewire these HTTP methods (data reads). Mutations keep SuccessResponse.
WIRED_HTTP_METHODS = ("get",)


# ---------------------------------------------------------------------------
# Docblock extraction
# ---------------------------------------------------------------------------

# Match a /** ... */ docblock immediately followed by a static method signature.
_METHOD_DOC_RE = re.compile(
    r"(/\*\*.*?\*/)\s*public static function\s+([A-Za-z0-9_]+)\s*\(",
    re.DOTALL,
)


def extract_docblocks(php_source: str) -> dict[str, str]:
    """Return {method_name: raw_docblock_text} for every static method."""
    return {m.group(2): m.group(1) for m in _METHOD_DOC_RE.finditer(php_source)}


def return_shape_text(docblock: str) -> str:
    """Pull the type expression that follows ``@return`` from a docblock,
    stripped of leading `` * `` line noise. Trailing prose (e.g.
    ``JSON Object "album"``) is left in place; the parser stops at the balanced
    end of the type."""
    body = re.search(r"@return\s+(.*?)\*/", docblock, re.DOTALL)
    if not body:
        raise ValueError("no @return found in docblock")
    lines = body.group(1).splitlines()
    cleaned = [re.sub(r"^\s*\*\s?", "", ln) for ln in lines]
    return " ".join(cleaned)


# ---------------------------------------------------------------------------
# PHPStan array-shape parser -> JSON Schema
# ---------------------------------------------------------------------------

_TOKEN_RE = re.compile(
    r"""\s+
      | (?P<str>"[^"]*")
      | (?P<punct>[{}<>,:?|])
      | (?P<ident>[A-Za-z_][A-Za-z0-9_\\-]*)
    """,
    re.VERBOSE,
)

_SCALARS = {
    "string": {"type": "string"},
    "int": {"type": "integer"},
    "integer": {"type": "integer"},
    "bool": {"type": "boolean"},
    "boolean": {"type": "boolean"},
    "true": {"type": "boolean"},
    "false": {"type": "boolean"},
    "float": {"type": "number"},
    "double": {"type": "number"},
    "mixed": {},
}


def tokenize(text: str) -> list[str]:
    tokens: list[str] = []
    pos = 0
    while pos < len(text):
        m = _TOKEN_RE.match(text, pos)
        if not m:
            # Stop at first unrecognised char (e.g. trailing prose punctuation).
            break
        pos = m.end()
        tok = m.group("str") or m.group("punct") or m.group("ident")
        if tok is not None:
            tokens.append(tok)
    return tokens


class ShapeParser:
    """Recursive-descent parser for the PHPStan array-shape subset used in
    Json8_Data: array{...}, array<int, T>, array<string, T>, unions with null,
    and the scalars string/int/bool/float."""

    def __init__(self, tokens: list[str]):
        self.tokens = tokens
        self.pos = 0

    def peek(self) -> str | None:
        return self.tokens[self.pos] if self.pos < len(self.tokens) else None

    def next(self) -> str:
        tok = self.tokens[self.pos]
        self.pos += 1
        return tok

    def expect(self, tok: str) -> None:
        got = self.next()
        if got != tok:
            raise ValueError(f"expected {tok!r}, got {got!r}")

    def parse_type(self) -> dict:
        atoms: list[dict] = []
        nullable = False
        while True:
            atom = self.parse_atom()
            if atom is None:  # 'null'
                nullable = True
            else:
                atoms.append(atom)
            if self.peek() == "|":
                self.next()
                continue
            break
        if not atoms:
            schema: dict = {}
        elif len(atoms) == 1:
            schema = dict(atoms[0])
        else:
            schema = {"oneOf": atoms}
        if nullable:
            schema["nullable"] = True
        return schema

    def parse_atom(self) -> dict | None:
        tok = self.peek()
        if tok == "null":
            self.next()
            return None
        if tok in _SCALARS:
            self.next()
            return dict(_SCALARS[tok])
        if tok == "array":
            self.next()
            nxt = self.peek()
            if nxt == "{":
                return self.parse_shape()
            if nxt == "<":
                return self.parse_generic()
            return {"type": "array"}
        # Unknown token (class-string, literal, trailing prose word): treat as a
        # loose string and consume it so parsing can continue.
        self.next()
        return {"type": "string"}

    def parse_shape(self) -> dict:
        self.expect("{")
        properties: dict[str, dict] = {}
        required: list[str] = []
        while self.peek() != "}":
            key = self.next().strip('"')
            optional = False
            if self.peek() == "?":
                self.next()
                optional = True
            self.expect(":")
            properties[key] = self.parse_type()
            if not optional:
                required.append(key)
            if self.peek() == ",":
                self.next()
        self.expect("}")
        schema: dict = {"type": "object", "properties": properties}
        if required:
            schema["required"] = required
        schema["additionalProperties"] = False
        return schema

    def parse_generic(self) -> dict:
        self.expect("<")
        first = self.parse_type()
        second = None
        if self.peek() == ",":
            self.next()
            second = self.parse_type()
        self.expect(">")
        if second is None:
            return {"type": "array", "items": first}
        if first.get("type") == "integer":
            return {"type": "array", "items": second}
        return {"type": "object", "additionalProperties": second}


def shape_to_object_schema(shape_text: str) -> dict:
    """Parse a builder's ``@return array<int, array{...}>`` and return the item
    object schema (the element type)."""
    parser = ShapeParser(tokenize(shape_text))
    outer = parser.parse_type()
    if outer.get("type") == "array" and "items" in outer:
        return outer["items"]
    # Some builders may document the bare item shape directly.
    return outer


# ---------------------------------------------------------------------------
# Schema assembly
# ---------------------------------------------------------------------------


def apply_ref_reuse(object_name: str, schema: dict) -> None:
    for (owner, prop), target in REF_REUSE.items():
        if owner != object_name:
            continue
        node = schema.get("properties", {}).get(prop)
        if isinstance(node, dict) and node.get("type") == "array":
            node["items"] = {"$ref": f"#/components/schemas/{target}"}


def build_list_response(key: str, object_name: str, envelope: str = "standard") -> dict:
    items = {"type": "array", "items": {"$ref": f"#/components/schemas/{object_name}"}}
    if envelope == "bare":
        # {<key>: [...]} with no total_count/md5 (now_playing, timeline, shouts).
        props = {key: items}
        required = [key]
    elif envelope == "browse":
        props = {
            "total_count": {"type": "integer"},
            "md5": {"type": "string"},
            "catalog_id": {"type": "string"},
            "parent_id": {"type": "string"},
            "parent_type": {"type": "string"},
            "child_type": {"type": "string"},
            key: items,
        }
        required = ["total_count", "md5", "catalog_id", "parent_id", "parent_type", "child_type", key]
    else:  # standard
        props = {"total_count": {"type": "integer"}, "md5": {"type": "string"}, key: items}
        required = ["total_count", "md5", key]
    return {"type": "object", "properties": props, "required": required, "additionalProperties": False}


# Hand-written schemas for endpoints whose output cannot be derived from a single
# builder shape (genuinely polymorphic). Merged verbatim into components.schemas.
MANUAL_SCHEMAS: dict[str, dict] = {
    "IndexReferenceObject": {
        "type": "object",
        "properties": {"id": {"type": "string"}, "type": {"type": "string"}},
        "required": ["id", "type"],
        "additionalProperties": False,
    },
    # index returns { <type>: <value> } where <value> varies with the `include`
    # flag and object type — a plain id list, a list of {id,type} references, or
    # (for parents like playlists) a map of parent-id -> reference list.
    "IndexResponse": {
        "type": "object",
        "description": (
            "Keyed by the requested `type` (e.g. `album`, `artist`, `song`). Without `include` the "
            "value is an array of object ids; with `include` it is an array of `{id, type}` references, "
            "or a map of parent id -> reference array for parent types such as playlists."
        ),
        "additionalProperties": {
            "oneOf": [
                {"type": "array", "items": {"type": "string"}},
                {"type": "array", "items": {"$ref": "#/components/schemas/IndexReferenceObject"}},
                {
                    "type": "object",
                    "additionalProperties": {
                        "type": "array",
                        "items": {"$ref": "#/components/schemas/IndexReferenceObject"},
                    },
                },
            ]
        },
    },
}

# deleted_array returns a union of three shapes; split it into named schemas by a
# discriminating property and wire each deleted endpoint to its own response.
# (discriminating property that must / must not be present, object, response, key)
DELETED_MEMBERS = [
    ("artist", "DeletedSongObject", "DeletedSongsResponse", "deleted_song"),
    ("podcast", "DeletedPodcastEpisodeObject", "DeletedPodcastEpisodesResponse", "deleted_podcast_episode"),
    (None, "DeletedVideoObject", "DeletedVideosResponse", "deleted_video"),
]


def build_deleted_schemas(docblocks: dict[str, str]) -> dict[str, dict]:
    shape = shape_to_object_schema(return_shape_text(docblocks["deleted_array"]))
    members = shape.get("oneOf", [shape])
    discriminated = {d for d, *_ in DELETED_MEMBERS if d is not None}
    schemas: dict[str, dict] = {}
    for discriminator, obj_name, resp_name, key in DELETED_MEMBERS:
        if discriminator is not None:
            match = next((m for m in members if discriminator in m.get("properties", {})), None)
        else:
            # video: the member carrying none of the discriminating keys.
            match = next(
                (m for m in members if discriminated.isdisjoint(m.get("properties", {}))),
                None,
            )
        if match is None:
            raise SystemExit(f"deleted: no union member for {obj_name}")
        schemas[obj_name] = match
        schemas[resp_name] = build_list_response(key, obj_name)
    return schemas


def build_schemas(docblocks: dict[str, str]) -> dict[str, dict]:
    schemas: dict[str, dict] = {}
    for cfg in TYPES.values():
        doc = docblocks.get(cfg["builder"])
        if doc is None:
            raise SystemExit(f"builder method not found: {cfg['builder']}")
        obj = shape_to_object_schema(return_shape_text(doc))
        schemas[cfg["object"]] = obj
    for name, schema in schemas.items():
        apply_ref_reuse(name, schema)
    for cfg in TYPES.values():
        if cfg.get("list"):
            schemas[cfg["list"]] = build_list_response(
                cfg["key"], cfg["object"], cfg.get("envelope", "standard")
            )
    schemas.update(build_deleted_schemas(docblocks))
    schemas.update(MANUAL_SCHEMAS)
    return schemas


# ---------------------------------------------------------------------------
# openapi.json merge
# ---------------------------------------------------------------------------

_ACTION_RE = re.compile(r"action=([A-Za-z0-9_]+)")


def wire_responses(spec: dict) -> list[str]:
    """Point matching 200 responses at their schema $ref. Returns a log of
    every (path, method, action, schema) wired."""
    mappings = spec.get("x-rpc-mappings", {})
    wired: list[str] = []
    for path, rpc in mappings.items():
        match = _ACTION_RE.search(rpc)
        if not match:
            continue
        action = match.group(1)
        schema_name = WIRING.get(action)
        if schema_name is None:
            continue
        path_item = spec.get("paths", {}).get(path, {})
        for method in WIRED_HTTP_METHODS:
            op = path_item.get(method)
            if not op:
                continue
            content = (
                op.get("responses", {})
                .get("200", {})
                .get("content", {})
                .get("application/json")
            )
            if content is None:
                continue
            content["schema"] = {"$ref": f"#/components/schemas/{schema_name}"}
            wired.append(f"{method.upper():6s} {path}  ({action}) -> {schema_name}")
    return wired


def dumps_canonical(spec: dict) -> str:
    return json.dumps(spec, indent=2, ensure_ascii=False) + "\n"


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--check", action="store_true", help="exit 1 if file would change")
    args = ap.parse_args()

    docblocks = extract_docblocks(JSON8_DATA.read_text(encoding="utf-8"))
    schemas = build_schemas(docblocks)

    original = OPENAPI.read_text(encoding="utf-8")
    spec = json.loads(original)

    spec.setdefault("components", {}).setdefault("schemas", {})
    for name, schema in schemas.items():
        spec["components"]["schemas"][name] = schema

    wired = wire_responses(spec)

    updated = dumps_canonical(spec)
    changed = updated != original

    print(f"schemas generated: {', '.join(schemas)}")
    print(f"responses wired: {len(wired)}")
    for line in wired:
        print(f"  {line}")

    if args.check:
        print("CHANGED" if changed else "up to date")
        return 1 if changed else 0

    if changed:
        OPENAPI.write_text(updated, encoding="utf-8", newline="\n")
        print(f"wrote {OPENAPI.relative_to(REPO_ROOT)}")
    else:
        print("no changes")
    return 0


if __name__ == "__main__":
    sys.exit(main())
