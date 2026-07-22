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

# Most shapes live on the Json8_Data builders, but some payloads are assembled
# elsewhere (e.g. preferences). A TYPES entry may name one of these instead.
SOURCES: dict[str, Path] = {
    "json8": JSON8_DATA,
    "preference_builder": REPO_ROOT / "src" / "Module" / "Api" / "Method" / "PreferenceItemBuilder.php",
    "api": REPO_ROOT / "src" / "Module" / "Api" / "Api.php",
    "search_model": REPO_ROOT / "src" / "Repository" / "Model" / "Search.php",
    "localplay": REPO_ROOT / "src" / "Module" / "Playback" / "Localplay" / "LocalPlay.php",
}

# ---------------------------------------------------------------------------
# Configuration: which object types to generate + how endpoints wire to them.
# Extend these tables to fan the generator out to more types.
# ---------------------------------------------------------------------------

# type key -> builder method (carrying the @return shape), object schema name,
# list-envelope schema name, and the JSON envelope key used by the list wrapper.
# "source" selects a SOURCES file other than the default Json8_Data.
TYPES: dict[str, dict[str, str]] = {
    "album": {"builder": "albums_array", "object": "AlbumObject", "list": "AlbumsResponse", "key": "album"},
    "album_disk": {"builder": "album_disks_array", "object": "AlbumDiskObject", "list": "AlbumDisksResponse", "key": "album_disk"},
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
    # last_shouts returns a bare {shout: [...]} (verified live); only the empty path adds total_count/md5.
    "shout": {"builder": "shouts_array", "object": "ShoutObject", "list": "ShoutsResponse", "key": "shout", "envelope": "bare"},
    # democratic playlist items: a reduced song shape plus the current vote count, in a bare envelope.
    "democratic": {"builder": "democratic_array", "object": "DemocraticSongObject", "list": "DemocraticSongsResponse", "key": "song", "envelope": "bare"},
    # preferences are assembled by PreferenceItemBuilder, not Json8_Data; single and list items share a shape.
    "preference": {"builder": "buildList", "object": "PreferenceObject", "list": "PreferencesResponse", "key": "preference", "envelope": "bare", "source": "preference_builder"},
    # handshake writes Api::server_details() straight out; ping wraps the same fields (see build_ping_schema).
    "handshake": {"builder": "server_details", "object": "HandshakeResponse", "list": "", "key": "", "source": "api"},
    # the advanced-search rule list a client needs to build a search, from Search::get_rule_types()
    # localplay_songs writes LocalPlay::get() straight out via objectArray(), in a bare envelope
    "localplay_song": {"builder": "get", "object": "LocalplaySongObject", "list": "LocalplaySongsResponse", "key": "localplay_songs", "envelope": "bare", "source": "localplay"},
    "search_rule": {"builder": "get_rule_types", "object": "SearchRuleObject", "list": "SearchRulesResponse", "key": "rule", "envelope": "bare", "source": "search_model"},
}

# ping returns the session/server counts of a handshake plus three fields it always
# emits, even unauthenticated (verified live: an anonymous ping returns only these three).
PING_ALWAYS = {
    "server": {"type": "string"},
    "version": {"type": "string"},
    "compatible": {"type": "string"},
}


def build_ping_schema(handshake: dict) -> dict:
    """PingResponse = the always-present ping fields + every (optional) handshake field."""
    properties = {**PING_ALWAYS, **handshake.get("properties", {})}
    return {
        "type": "object",
        "description": (
            "`server`, `version` and `compatible` are always returned. Sending a valid `auth` "
            "extends the session and adds the handshake fields (`session_expire`, server counts, ...)."
        ),
        "properties": properties,
        "required": sorted(PING_ALWAYS),
        "additionalProperties": False,
    }

# Within a generated object schema, replace a property's item/value subtree with
# a $ref to an already-defined schema (DRY reuse, mirroring the Folder schemas).
# (object schema name, property name) -> referenced schema name.
# Only applied when the property is an array; its items become the $ref.
# Prose for a property whose meaning is not obvious from its type alone. Keyed the same way as
# REF_REUSE; applied after the shape is built so it survives regeneration (these used to be
# hand-edits in openapi.json, which the generator then silently reverted).
PROPERTY_DESCRIPTIONS: dict[tuple[str, str], str] = {
    ("PlaylistObject", "items"): (
        "The expanded song list when songs are included, otherwise the total song count. Playlists "
        "are song lists here: a playlist may physically hold other media types, but this method "
        "reports only songs so that real playlists and song smartlists share one shape."
    ),
}

REF_REUSE: dict[tuple[str, str], str] = {
    # These properties embed another object's full shape inline (verified
    # field-for-field identical to the target builder), so reference the shared
    # schema instead of duplicating it, mirroring the Folder schemas.
    ("AlbumObject", "tracks"): "SongObject",
    ("AlbumDiskObject", "tracks"): "SongObject",
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
    "album_disks": "AlbumDisksResponse",
    "album_disk": "AlbumDiskObject",
    # Songs
    "songs": "SongsResponse",
    "album_songs": "SongsResponse",
    "album_disk_songs": "SongsResponse",
    "localplay_songs": "LocalplaySongsResponse",
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
    # Preferences (the single-item endpoints return a flat object, the list a bare envelope)
    "user_preferences": "PreferencesResponse",
    "system_preferences": "PreferencesResponse",
    "user_preference": "PreferenceObject",
    "system_preference": "PreferenceObject",
    # Session / server info
    "ping": "PingResponse",
    # Follower lists reuse the users envelope
    "followers": "UsersResponse",
    "following": "UsersResponse",
    # Advanced search metadata
    "search_rules": "SearchRulesResponse",
    "search_group": "SearchGroupResponse",
    # Plugin-backed lookups and the playlist hash
    "get_lyrics": "LyricsResponse",
    "get_external_metadata": "ExternalMetadataResponse",
    "playlist_hash": "PlaylistHashResponse",
    # These reuse an existing shape rather than defining their own
    "url_to_song": "SongsResponse",
    "system_update": "SuccessResponse",
    # Polymorphic
    "index": "IndexResponse",
    "playlist_generate": "PlaylistGenerateResponse",
    # Deleted (three distinct per-type responses)
    "deleted_songs": "DeletedSongsResponse",
    "deleted_podcast_episodes": "DeletedPodcastEpisodesResponse",
    "deleted_videos": "DeletedVideosResponse",
}

# Endpoints that never answer with JSON. `stream` and `download` hand back a 302 to the
# play url (Download8Method/AbstractStreamMethod both `withStatus(302)`), `download` only
# returns a body for the `zip=1` container case, and `get_art` writes the image itself.
_REDIRECT_RESPONSE = {
    "description": "Redirect to the media url; the stream itself is served from the `Location` header",
    "headers": {
        "Location": {
            "description": "Absolute url of the media stream",
            "schema": {"type": "string", "format": "uri"},
        }
    },
}

_ZIP_RESPONSE = {
    "description": "Zip archive of the container's media files (only when `zip=1` is sent for a zipable type)",
    "headers": {
        "Content-Type": {
            "description": "Always `application/zip`",
            "schema": {"type": "string"},
        },
        "Content-Disposition": {
            "description": "`attachment` with the archive name, RFC 5987 encoded",
            "schema": {"type": "string"},
        },
    },
    "content": {"application/zip": {"schema": {"type": "string", "format": "binary"}}},
}

_IMAGE_RESPONSE = {
    "description": "The image itself, written straight to the response body",
    "headers": {
        "Content-Type": {
            "description": "The stored art mime type (e.g. `image/jpeg`, `image/png`)",
            "schema": {"type": "string"},
        },
        "Content-Length": {
            "description": "Size of the image in bytes",
            "schema": {"type": "integer"},
        },
        "Access-Control-Allow-Origin": {
            "description": "Always `*`, so art can be loaded cross-origin",
            "schema": {"type": "string"},
        },
    },
    "content": {"image/*": {"schema": {"type": "string", "format": "binary"}}},
}

# action -> {"set": responses to install, "drop": status codes to remove}
BINARY_RESPONSES: dict[str, dict] = {
    "stream": {"set": {"302": _REDIRECT_RESPONSE}, "drop": ["200"]},
    "download": {"set": {"302": _REDIRECT_RESPONSE, "200": _ZIP_RESPONSE}, "drop": []},
    "get_art": {"set": {"200": _IMAGE_RESPONSE}, "drop": []},
}

# Actions whose shape depends on a request parameter that the REST path bakes into
# x-rpc-mappings (`/albums/search` -> `action=search&type=album`). Keyed by
# (action, parameter, value); a hit wins over the plain WIRING/WIRING_BY_METHOD entry.
# The type mappings mirror JsonOutput::searchResult() and the StatsMethod/GetSimilarMethod matches.
WIRING_BY_PARAM: dict[tuple[str, str, str], str] = {
    ("search", "type", "album"): "AlbumsResponse",
    ("search", "type", "album_disk"): "AlbumDisksResponse",
    ("search", "type", "artist"): "ArtistsResponse",
    ("search", "type", "album_artist"): "ArtistsResponse",
    ("search", "type", "song_artist"): "ArtistsResponse",
    ("search", "type", "genre"): "GenresResponse",
    ("search", "type", "tag"): "GenresResponse",
    ("search", "type", "label"): "LabelsResponse",
    ("search", "type", "playlist"): "PlaylistsResponse",
    ("search", "type", "podcast"): "PodcastsResponse",
    ("search", "type", "podcast_episode"): "PodcastEpisodesResponse",
    ("search", "type", "song"): "SongsResponse",
    ("search", "type", "user"): "UsersResponse",
    ("search", "type", "video"): "VideosResponse",
    ("stats", "type", "album"): "AlbumsResponse",
    ("stats", "type", "album_disk"): "AlbumDisksResponse",
    ("stats", "type", "artist"): "ArtistsResponse",
    ("stats", "type", "playlist"): "PlaylistsResponse",
    ("stats", "type", "podcast"): "PodcastsResponse",
    ("stats", "type", "podcast_episode"): "PodcastEpisodesResponse",
    ("stats", "type", "song"): "SongsResponse",
    ("stats", "type", "video"): "VideosResponse",
    ("get_similar", "type", "artist"): "ArtistsResponse",
    ("get_similar", "type", "song"): "SongsResponse",
    # every localplay command reports a boolean except `status`, which returns player state
    ("localplay", "command", "status"): "LocalplayStatusResponse",
}

# Non-GET operations that answer with data rather than the success envelope, keyed by
# (http method, action) because the same action can mix the two — `bookmark` PATCH returns
# the edited bookmark while `bookmark` DELETE returns SuccessResponse.
WIRING_BY_METHOD: dict[tuple[str, str], str] = {
    ("post", "democratic"): "DemocraticResponse",
    ("post", "handshake"): "HandshakeResponse",
    # the bookmark writers all call `bookmarks(..., $object: false)`, i.e. one flat object
    ("put", "bookmark_create"): "BookmarkObject",
    ("patch", "bookmark_edit"): "BookmarkObject",
    # the create endpoints answer with the object they just made (`$object: false`)
    ("post", "share_create"): "ShareObject",
    ("put", "share_create"): "ShareObject",
    ("put", "live_stream_create"): "LiveStreamObject",
    ("put", "playlist_create"): "PlaylistObject",
    ("put", "podcast_create"): "PodcastObject",
    ("put", "catalog_create"): "CatalogObject",
    ("post", "register"): "SuccessResponse",
    ("post", "player"): "NowPlayingResponse",
    ("post", "localplay"): "LocalplayResponse",
}

# HTTP methods considered when wiring; GET reads WIRING/WIRING_BY_TYPE, the rest WIRING_BY_METHOD.
WIRED_HTTP_METHODS = ("get", "post", "put", "patch")


# ---------------------------------------------------------------------------
# Docblock extraction
# ---------------------------------------------------------------------------

# Match a /** ... */ docblock immediately followed by a public method signature.
_METHOD_DOC_RE = re.compile(
    r"(/\*\*.*?\*/)\s*public\s+(?:static\s+)?function\s+([A-Za-z0-9_]+)\s*\(",
    re.DOTALL,
)


def extract_docblocks(php_source: str) -> dict[str, str]:
    """Return {method_name: raw_docblock_text} for every public method."""
    return {m.group(2): m.group(1) for m in _METHOD_DOC_RE.finditer(php_source)}


def load_sources() -> dict[str, dict[str, str]]:
    """Return {source name: {method: docblock}} for every configured source file."""
    return {
        name: extract_docblocks(path.read_text(encoding="utf-8"))
        for name, path in SOURCES.items()
    }


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
      | (?P<punct>[{}<>,:?|\[\]])
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
            # prefix nullable syntax: `?string` is the same as `string|null`
            if self.peek() == "?":
                self.next()
                nullable = True
            atom = self.parse_atom()
            # postfix list syntax: `string[]`, `array{...}[]` -> an array of that type
            while atom is not None and self.peek() == "[" and self.tokens[self.pos + 1 : self.pos + 2] == ["]"]:
                self.next()
                self.next()
                atom = {"type": "array", "items": atom}
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


def apply_property_descriptions(object_name: str, schema: dict) -> None:
    for (owner, prop), description in PROPERTY_DESCRIPTIONS.items():
        if owner != object_name:
            continue
        node = schema.get("properties", {}).get(prop)
        if isinstance(node, dict):
            node["description"] = description


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
    # democratic returns a different payload per `method` param (see DemocraticMethod).
    "DemocraticPlayResponse": {
        "type": "object",
        "description": "Returned by `method=play`: the stream URL of the democratic playlist.",
        "properties": {"url": {"type": "string"}},
        "required": ["url"],
        "additionalProperties": False,
    },
    "DemocraticVoteResponse": {
        "type": "object",
        "description": "Returned by `method=vote` and `method=devote`.",
        "properties": {"method": {"type": "string"}, "result": {"type": "boolean"}},
        "required": ["method", "result"],
        "additionalProperties": False,
    },
    "DemocraticResponse": {
        "description": (
            "Depends on the `method` parameter: `play` returns the stream url, `vote`/`devote` return the "
            "applied method and its result, and `playlist` returns the current democratic song list."
        ),
        "oneOf": [
            {"$ref": "#/components/schemas/DemocraticPlayResponse"},
            {"$ref": "#/components/schemas/DemocraticVoteResponse"},
            {"$ref": "#/components/schemas/DemocraticSongsResponse"},
        ],
    },
    # search_group runs one search per object type and returns them all under `search`,
    # keyed by type, each holding that type's normal object list (JsonOutput::searchGroup).
    "SearchGroupResponse": {
        "type": "object",
        "description": (
            "`search` is keyed by object type (`album`, `artist`, `album_artist`, `song_artist`, `song`, "
            "`playlist`, `podcast`, `podcast_episode`, `genre`, `label`, `user`, `video`); each value is "
            "that type's usual object list. Types with no matches are omitted."
        ),
        "properties": {
            "search": {
                "type": "object",
                "additionalProperties": {"type": "array", "items": {"type": "object"}},
            }
        },
        "required": ["search"],
        "additionalProperties": False,
    },
    # playlist_hash reports the md5 of the playlist's items, or null when the playlist is empty.
    "PlaylistHashResponse": {
        "type": "object",
        "properties": {"md5": {"type": "string", "nullable": True}},
        "required": ["md5"],
        "additionalProperties": False,
    },
    # get_lyrics and get_external_metadata share a shape: the object they were asked about
    # plus one entry per plugin that answered. Plugin payloads are plugin-defined.
    # `plugin` is a PHP associative array, so an empty one serialises as `[]` rather than `{}`
    "_PluginMap": {
        "oneOf": [
            {"type": "object", "additionalProperties": {}},
            {"type": "array", "maxItems": 0},
        ],
    },
    "LyricsResponse": {
        "type": "object",
        "description": (
            "`plugin` is keyed by lyric source (`database` plus any lyric-retriever plugin that "
            "answered). When nothing answered it is serialised as an empty array, not an empty object."
        ),
        "properties": {
            "object_id": {"type": "string"},
            "object_type": {"type": "string"},
            "plugin": {"$ref": "#/components/schemas/_PluginMap"},
        },
        "required": ["object_id", "object_type", "plugin"],
        "additionalProperties": False,
    },
    "ExternalMetadataObject": {
        "type": "object",
        "description": "`plugin` is keyed by metadata-retriever plugin name; each value is that plugin's payload.",
        "properties": {
            "object_id": {"type": "string"},
            "object_type": {"type": "string"},
            "plugin": {"$ref": "#/components/schemas/_PluginMap"},
        },
        "required": ["object_id", "object_type", "plugin"],
        "additionalProperties": False,
    },
    # no plugin answered -> the method falls back to the empty list envelope of the requested type
    "EmptyListResponse": {
        "type": "object",
        "description": "The standard empty envelope, with an empty list keyed by the requested type.",
        "properties": {"total_count": {"type": "integer"}, "md5": {"type": "string"}},
        "required": ["total_count", "md5"],
    },
    "ExternalMetadataResponse": {
        "description": (
            "Returns the plugin payloads when at least one metadata plugin answered, and the empty "
            "list envelope for the requested type when none did."
        ),
        "oneOf": [
            {"$ref": "#/components/schemas/ExternalMetadataObject"},
            {"$ref": "#/components/schemas/EmptyListResponse"},
        ],
    },
    # localplay wraps every reply as {localplay: {command: {<command>: result}}} (JsonOutput::localplayResult).
    "LocalplayStatusObject": {
        "type": "object",
        "description": (
            "Player state. The exact fields come from the configured Localplay controller "
            "(MPD, VLC, XBMC, UPnP, HTTPQ), so only `repeat` and `random` are guaranteed - "
            "the API coerces those two to booleans. The rest are what that controller reports."
        ),
        "properties": {
            "state": {"type": "string"},
            "volume": {"type": "integer"},
            "repeat": {"type": "boolean"},
            "random": {"type": "boolean"},
            "track": {"type": "integer"},
            "track_title": {"type": "string"},
            "track_artist": {"type": "string"},
            "track_album": {"type": "string"},
        },
        "required": ["repeat", "random"],
    },
    "LocalplayResponse": {
        "type": "object",
        "description": "The command name maps to `true` when the controller accepted it, `false` when it did not.",
        "properties": {
            "localplay": {
                "type": "object",
                "properties": {"command": {"type": "object", "additionalProperties": {"type": "boolean"}}},
                "required": ["command"],
                "additionalProperties": False,
            }
        },
        "required": ["localplay"],
        "additionalProperties": False,
    },
    "LocalplayStatusResponse": {
        "type": "object",
        "description": "The `status` command reports the player state instead of a boolean.",
        "properties": {
            "localplay": {
                "type": "object",
                "properties": {
                    "command": {
                        "type": "object",
                        "additionalProperties": {"$ref": "#/components/schemas/LocalplayStatusObject"},
                    }
                },
                "required": ["command"],
                "additionalProperties": False,
            }
        },
        "required": ["localplay"],
        "additionalProperties": False,
    },
    # playlist_generate switches shape on `format`: song/index both emit the song envelope
    # (index is Json8_Data::indexes() dispatching to songs()), id emits a bare id array.
    "PlaylistGenerateResponse": {
        "description": (
            "Depends on the `format` parameter: `song` (default) and `index` return the song list envelope, "
            "`id` returns a bare array of song ids."
        ),
        "oneOf": [
            {"$ref": "#/components/schemas/SongsResponse"},
            {
                "type": "array",
                "description": "Returned by `format=id`: song ids only, with no envelope.",
                "items": {"type": "string"},
            },
        ],
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


def build_schemas(sources: dict[str, dict[str, str]]) -> dict[str, dict]:
    docblocks = sources["json8"]
    schemas: dict[str, dict] = {}
    for cfg in TYPES.values():
        doc = sources[cfg.get("source", "json8")].get(cfg["builder"])
        if doc is None:
            raise SystemExit(f"builder method not found: {cfg['builder']}")
        obj = shape_to_object_schema(return_shape_text(doc))
        schemas[cfg["object"]] = obj
    for name, schema in schemas.items():
        apply_ref_reuse(name, schema)
        apply_property_descriptions(name, schema)
    for cfg in TYPES.values():
        if cfg.get("list"):
            schemas[cfg["list"]] = build_list_response(
                cfg["key"], cfg["object"], cfg.get("envelope", "standard")
            )
    schemas.update(build_deleted_schemas(docblocks))
    schemas["PingResponse"] = build_ping_schema(schemas["HandshakeResponse"])
    schemas.update(MANUAL_SCHEMAS)
    return schemas


# ---------------------------------------------------------------------------
# openapi.json merge
# ---------------------------------------------------------------------------

_ACTION_RE = re.compile(r"action=([A-Za-z0-9_]+)")
# `&name=value` pairs after the action; `{placeholder}` values deliberately do not match
_PARAM_RE = re.compile(r"[&?]([a-z_]+)=([A-Za-z0-9_]+)")
_MAPPING_KEY_RE = re.compile(r"^(?:(GET|POST|PUT|PATCH|DELETE)\s+)?(/\S*)$")


def mapping_operations(spec: dict):
    """Yield (path, http method, rpc string) for every x-rpc-mapping.

    A key may carry an explicit method prefix (`PATCH /bookmarks/{id}`); the unprefixed
    key for a path covers whichever operations no prefixed key already claims, so e.g.
    `/songs/{id}/bookmark` maps GET/PATCH/DELETE explicitly and PUT by default."""
    mappings = spec.get("x-rpc-mappings", {})
    paths = spec.get("paths", {})
    parsed: list[tuple[str, str | None, str]] = []
    claimed: dict[str, set[str]] = {}
    for key, rpc in mappings.items():
        match = _MAPPING_KEY_RE.match(key)
        if not match:
            continue
        method = match.group(1).lower() if match.group(1) else None
        parsed.append((match.group(2), method, rpc))
        if method:
            claimed.setdefault(match.group(2), set()).add(method)
    for path, method, rpc in parsed:
        operations = paths.get(path, {})
        if method is not None:
            if method in operations:
                yield path, method, rpc
            continue
        for candidate in WIRED_HTTP_METHODS:
            if candidate in operations and candidate not in claimed.get(path, set()):
                yield path, candidate, rpc


def wire_responses(spec: dict) -> list[str]:
    """Point matching 200 responses at their schema $ref. Returns a log of
    every (path, method, action, schema) wired."""
    wired: list[str] = []
    for path, method, rpc in mapping_operations(spec):
        match = _ACTION_RE.search(rpc)
        if not match:
            continue
        action   = match.group(1)
        override = next(
            (
                WIRING_BY_PARAM[(action, name, value)]
                for name, value in _PARAM_RE.findall(rpc)
                if (action, name, value) in WIRING_BY_PARAM
            ),
            None,
        )
        if override is not None:
            schema_name = override
        elif method == "get":
            schema_name = WIRING.get(action)
        else:
            schema_name = WIRING_BY_METHOD.get((method, action))
        if schema_name is None:
            continue
        content = (
            spec["paths"][path][method]
            .get("responses", {})
            .get("200", {})
            .get("content", {})
            .get("application/json")
        )
        if content is None:
            continue
        content["schema"] = {"$ref": f"#/components/schemas/{schema_name}"}
        wired.append(f"{method.upper():6s} {path}  ({action}) -> {schema_name}")
    return wired


def document_binary_responses(spec: dict) -> list[str]:
    """Install the non-JSON success responses (redirects, zip, image bodies) on the
    endpoints that serve media rather than data. Returns a log of what was set."""
    touched: list[str] = []
    for path, method, rpc in mapping_operations(spec):
        match = _ACTION_RE.search(rpc)
        if not match or method != "get":
            continue
        cfg = BINARY_RESPONSES.get(match.group(1))
        if cfg is None:
            continue
        op = spec["paths"][path][method]
        responses = op.setdefault("responses", {})
        for code in cfg["drop"]:
            responses.pop(code, None)
        for code, body in cfg["set"].items():
            responses[code] = json.loads(json.dumps(body))
        ordered = {code: responses[code] for code in sorted(responses)}
        op["responses"] = ordered
        touched.append(f"GET    {path}  ({match.group(1)}) -> {'/'.join(sorted(cfg['set']))}")
    return touched


def dumps_canonical(spec: dict) -> str:
    return json.dumps(spec, indent=2, ensure_ascii=False) + "\n"


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--check", action="store_true", help="exit 1 if file would change")
    args = ap.parse_args()

    schemas = build_schemas(load_sources())

    original = OPENAPI.read_text(encoding="utf-8")
    spec = json.loads(original)

    spec.setdefault("components", {}).setdefault("schemas", {})
    for name, schema in schemas.items():
        spec["components"]["schemas"][name] = schema

    wired = wire_responses(spec)
    binary = document_binary_responses(spec)

    updated = dumps_canonical(spec)
    changed = updated != original

    print(f"schemas generated: {', '.join(schemas)}")
    print(f"responses wired: {len(wired)}")
    for line in wired:
        print(f"  {line}")
    print(f"non-JSON responses documented: {len(binary)}")
    for line in binary:
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
