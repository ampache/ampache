#!/usr/bin/env python3
"""Verify that the response schemas in docs/openapi.json match the ACTUAL output
of a live Ampache server (option A: drive the real API).

For every GET operation that is wired to a `$ref` schema, this:
  1. resolves the RPC action + params from the spec's `x-rpc-mappings`,
  2. auto-discovers a sample id for single-item / nested endpoints,
  3. fetches the JSON response and compares its keys against the schema
     (envelope + first list item): reports keys the server returned that the
     schema doesn't document, and required keys the schema documents that the
     server didn't return,
  4. optionally fetches the XML response and reports where its element/attribute
     set diverges from the JSON keys (the known object-vs-element differences).

Config comes from a `verify.env` file (see verify.env.example) and/or real
environment variables. Auth uses the API key when set, else user+password.

Usage:
    python resources/scripts/api-docs/verify_openapi_shapes.py [options]

    --env PATH     env file to load (default: verify.env next to this script)
    --format F     json | xml | both   (default: both)
    --only ACTION  verify a single RPC action (repeatable), for debugging
    --limit N      cap the number of endpoints checked
    --strict       exit non-zero if any divergence is found
    --out PATH     also write the full report (markdown) to PATH

This script only READS from the server (GET), but it authenticates as your user;
point it at a server you own.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import ssl
import sys
import time
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from pathlib import Path

SCRIPT_DIR = Path(__file__).resolve().parent
REPO_ROOT = SCRIPT_DIR.parents[2]
OPENAPI = REPO_ROOT / "docs" / "openapi.json"

_ACTION_RE = re.compile(r"action=([A-Za-z0-9_]+)")

# param placeholder in a path -> object type used to discover a sample id
PARAM_TYPE = {
    "album_id": "album", "artist_id": "artist", "song_id": "song", "genre_id": "genre",
    "label_id": "label", "live_stream_id": "live_stream", "podcast_id": "podcast",
    "episode_id": "podcast_episode", "playlist_id": "playlist", "smartlist_id": "smartlist",
    "catalog_id": "catalog", "license_id": "license", "share_id": "share",
    "bookmark_id": "bookmark", "user_id": "user",
}

# placeholders that name an object type rather than an id, so no lookup is needed
PARAM_LITERAL = {"search_type": "song", "object_type": "song", "preference_name": "ajax_load"}

# Extra params some actions require that x-rpc-mappings doesn't carry. Values may
# contain {placeholders} resolved the same way as path params (e.g. {user_id}).
DEFAULT_PARAMS = {
    "list": {"type": "album"},
    "index": {"type": "album"},
    "last_shouts": {"filter": "{user_id}"},
    # search needs at least one rule to run; stats needs a filter to pick a chart
    "search": {"rule_1": "title", "rule_1_operator": "0", "rule_1_input": "a"},
    "search_group": {"rule_1": "title", "rule_1_operator": "0", "rule_1_input": "a"},
    "stats": {"filter": "random"},
}

# object type -> (list action, envelope key) used to pull a sample id
TYPE_DISCOVERY = {
    "album": ("albums", "album"), "artist": ("artists", "artist"), "song": ("songs", "song"),
    "genre": ("genres", "genre"), "label": ("labels", "label"),
    "live_stream": ("live_streams", "live_stream"), "podcast": ("podcasts", "podcast"),
    "podcast_episode": ("podcast_episodes", "podcast_episode"),
    "playlist": ("playlists", "playlist"), "smartlist": ("smartlists", "playlist"),
    "catalog": ("catalogs", "catalog"), "license": ("licenses", "license"),
    "share": ("shares", "share"), "bookmark": ("bookmarks", "bookmark"),
    "user": ("users", "user"),
}


# ---------------------------------------------------------------------------
# config + auth
# ---------------------------------------------------------------------------


def load_env(path: Path) -> dict[str, str]:
    cfg: dict[str, str] = {}
    if path.is_file():
        for line in path.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, _, val = line.partition("=")
            cfg[key.strip()] = val.strip()
    # Real environment variables win.
    for key in ("AMPACHE_HOST", "AMPACHE_USER", "AMPACHE_PASSWORD", "AMPACHE_APIKEY",
                "AMPACHE_API_VERSION", "AMPACHE_VERIFY_SSL"):
        if os.environ.get(key):
            cfg[key] = os.environ[key]
    return cfg


class Client:
    def __init__(self, cfg: dict[str, str]):
        self.host = cfg.get("AMPACHE_HOST", "").rstrip("/")
        self.user = cfg.get("AMPACHE_USER", "")
        self.password = cfg.get("AMPACHE_PASSWORD", "")
        self.apikey = cfg.get("AMPACHE_APIKEY", "")
        self.version = cfg.get("AMPACHE_API_VERSION", "8") or "8"
        verify = cfg.get("AMPACHE_VERIFY_SSL", "1") not in ("0", "false", "False", "")
        self.ssl_ctx = None if verify else ssl._create_unverified_context()
        self.auth = ""
        self.effective_version = ""
        if not self.host:
            raise SystemExit("AMPACHE_HOST is not set (see verify.env.example)")

    def _url(self, fmt: str, params: dict[str, str]) -> str:
        query = urllib.parse.urlencode(params)
        return f"{self.host}/server/{fmt}.server.php?{query}"

    def get(self, fmt: str, params: dict[str, str]) -> str:
        req = urllib.request.Request(self._url(fmt, params), headers={"User-Agent": "ampache-shape-verify"})
        with urllib.request.urlopen(req, context=self.ssl_ctx, timeout=30) as resp:
            return resp.read().decode("utf-8", "replace")

    def call(self, action: str, params: dict[str, str], fmt: str = "json") -> str:
        merged = {"action": action, "auth": self.auth, "version": self.version, **params}
        return self.get(fmt, merged)

    def handshake(self) -> None:
        if self.apikey:
            # Modern hashed form (works on encrypted-key servers); no user param.
            key = hashlib.sha256(self.apikey.encode()).hexdigest()
            passphrase = hashlib.sha256((self.user + key).encode()).hexdigest()
            for auth in (passphrase, self.apikey):
                data = self._try_handshake({"auth": auth})
                if data:
                    return self._store(data)
            raise SystemExit("handshake failed with API key (check AMPACHE_APIKEY / AMPACHE_USER)")
        # Password auth.
        if not (self.user and self.password):
            raise SystemExit("set AMPACHE_APIKEY, or both AMPACHE_USER and AMPACHE_PASSWORD")
        ts = str(int(time.time()))
        key = hashlib.sha256(self.password.encode()).hexdigest()
        passphrase = hashlib.sha256((ts + key).encode()).hexdigest()
        data = self._try_handshake({"auth": passphrase, "user": self.user, "timestamp": ts})
        if not data:
            raise SystemExit("handshake failed with password (check credentials / server URL)")
        self._store(data)

    def _try_handshake(self, params: dict[str, str]) -> dict | None:
        try:
            raw = self.get("json", {"action": "handshake", "version": self.version, **params})
            data = json.loads(raw)
        except Exception:
            return None
        return data if isinstance(data, dict) and data.get("auth") else None

    def _store(self, data: dict) -> None:
        self.auth = data["auth"]
        self.effective_version = str(data.get("api", ""))


# ---------------------------------------------------------------------------
# schema helpers
# ---------------------------------------------------------------------------

_ENVELOPE_META = {"total_count", "md5", "catalog_id", "parent_id", "parent_type", "child_type"}


def resolve_ref(spec: dict, ref: str) -> dict:
    node = spec
    for part in ref.lstrip("#/").split("/"):
        node = node[part.replace("~1", "/").replace("~0", "~")]
    return node


def deref(spec: dict, schema: dict) -> dict:
    return resolve_ref(spec, schema["$ref"]) if "$ref" in schema else schema


def data_key(schema: dict) -> str | None:
    """The single non-meta property an envelope wraps (array OR object), e.g.
    `song` in SongsResponse or `folder` in FolderBrowseResponse. None for a bare
    object (many data props) or a free-form schema."""
    data = [k for k in schema.get("properties", {}) if k not in _ENVELOPE_META]
    return data[0] if len(data) == 1 else None


def schema_keys(schema: dict) -> tuple[set[str], set[str], bool]:
    """(required, all documented properties, additionalProperties-allowed)."""
    props = set(schema.get("properties", {}))
    required = set(schema.get("required", []))
    extra_ok = schema.get("additionalProperties", True) is not False
    return required, props, extra_ok


def action_to_ref(spec: dict) -> dict[str, tuple[str, str]]:
    """RPC action -> (openapi path, $ref) for GET ops wired to a schema."""
    out: dict[str, tuple[str, str]] = {}
    for path, rpc in spec.get("x-rpc-mappings", {}).items():
        m = _ACTION_RE.search(rpc)
        op = spec.get("paths", {}).get(path, {}).get("get")
        if not m or not op:
            continue
        ref = (
            op.get("responses", {}).get("200", {}).get("content", {})
            .get("application/json", {}).get("schema", {}).get("$ref", "")
        )
        if ref.startswith("#/components/schemas/") and m.group(1) not in out:
            out[m.group(1)] = (path, ref)
    return out


def rpc_params(spec: dict, path: str) -> dict[str, str]:
    """The non-action query params template from x-rpc-mappings (with {holes})."""
    rpc = spec["x-rpc-mappings"][path]
    params = dict(urllib.parse.parse_qsl(rpc, keep_blank_values=True))
    params.pop("action", None)
    return params


# ---------------------------------------------------------------------------
# id discovery
# ---------------------------------------------------------------------------


class Discoverer:
    def __init__(self, client: Client, spec: dict):
        self.client = client
        self.spec = spec
        self.cache: dict[str, str | None] = {}

    def id_for(self, obj_type: str) -> str | None:
        if obj_type in self.cache:
            return self.cache[obj_type]
        source = TYPE_DISCOVERY.get(obj_type)
        val: str | None = None
        if source:
            action, key = source
            try:
                data = json.loads(self.client.call(action, {"limit": "1"}))
                items = data.get(key) if isinstance(data, dict) else None
                if isinstance(items, list) and items and isinstance(items[0], dict):
                    val = str(items[0].get("id"))
            except Exception:
                val = None
        self.cache[obj_type] = val
        return val

    def fill(self, params: dict[str, str]) -> dict[str, str] | None:
        """Replace {placeholders} with discovered ids; None if any can't be filled."""
        out: dict[str, str] = {}
        for k, v in params.items():
            hole = re.fullmatch(r"\{(\w+)\}", v)
            if hole:
                if hole.group(1) in PARAM_LITERAL:
                    out[k] = PARAM_LITERAL[hole.group(1)]
                    continue
                obj_type = PARAM_TYPE.get(hole.group(1))
                if obj_type is None:
                    return None
                sample = self.id_for(obj_type)
                if sample is None:
                    return None
                out[k] = sample
            else:
                out[k] = v
        return out


# ---------------------------------------------------------------------------
# comparison
# ---------------------------------------------------------------------------


def compare_json(spec: dict, ref: str, payload: dict) -> list[str]:
    schema = resolve_ref(spec, ref)
    findings: list[str] = []

    def check(obj: dict, sch: dict, where: str) -> None:
        if not isinstance(obj, dict) or not sch.get("properties"):
            return
        required, props, extra_ok = schema_keys(sch)
        got = set(obj)
        missing = required - got
        extra = got - props
        if missing:
            findings.append(f"{where}: schema requires but server omitted: {sorted(missing)}")
        if extra and not extra_ok:
            findings.append(f"{where}: server returned undocumented keys: {sorted(extra)}")

    key = data_key(schema)
    if key:
        check(payload, schema, "envelope")
        val = payload.get(key)
        if isinstance(val, list):
            if val:
                item_schema = deref(spec, schema["properties"][key].get("items", {}))
                check(val[0], item_schema, f"{key}[0]")
            else:
                findings.append(f"{key}: server returned no items (cannot verify item fields)")
        elif isinstance(val, dict):
            check(val, deref(spec, schema["properties"][key]), key)
    else:
        check(payload, schema, "object")
    return findings


def xml_field_set(text: str, target: str | None) -> set[str] | None:
    """Field set (attributes + child element tags) of the data object element.
    For an envelope, `target` is the wrapped element tag (e.g. `song`/`folder`);
    for a bare object it is None and we take the first non-meta child of <root>."""
    try:
        root = ET.fromstring(text)
    except ET.ParseError:
        return None
    if target is not None:
        container = root.find(target)
    else:
        body = [c for c in root if c.tag not in _ENVELOPE_META]
        # A response whose only fields are named like envelope meta (playlist_hash returns
        # just `md5`) filters down to nothing; fall back to every child.
        if not body:
            body = list(root)
        # Flat responses (ping, handshake, playlist_hash) hang their fields straight off
        # <root> with no wrapper element, so the "first child" is a field, not the data
        # object. A leaf first child means flat: a real data object carries fields or an id.
        if body and not len(body[0]) and not body[0].attrib:
            return {c.tag for c in body}
        container = body[0] if body else None
    if container is None:
        return set()
    fields = set(container.attrib)
    for child in container:
        fields.add(child.tag)
    return fields


# ---------------------------------------------------------------------------
# main
# ---------------------------------------------------------------------------


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--env", type=Path, default=SCRIPT_DIR / "verify.env")
    ap.add_argument("--format", choices=("json", "xml", "both"), default="both")
    ap.add_argument("--only", action="append", default=[])
    ap.add_argument("--limit", type=int, default=0)
    ap.add_argument("--strict", action="store_true")
    ap.add_argument("--out", type=Path)
    args = ap.parse_args()

    spec = json.loads(OPENAPI.read_text(encoding="utf-8"))
    cfg = load_env(args.env)
    client = Client(cfg)
    client.handshake()
    disc = Discoverer(client, spec)

    print(f"# Connected to {client.host} (API version {client.effective_version or '?'})")
    if client.effective_version and client.effective_version != "8":
        print(f"! server negotiated API v{client.effective_version}; schemas describe v8 - "
              "differences may be version-related, not doc bugs.")

    targets = action_to_ref(spec)
    if args.only:
        targets = {a: v for a, v in targets.items() if a in args.only}

    report: list[str] = []
    checked = skipped = clean = flagged = 0
    for action, (path, ref) in sorted(targets.items()):
        if args.limit and checked >= args.limit:
            break
        params = {**rpc_params(spec, path), **DEFAULT_PARAMS.get(action, {})}
        filled = disc.fill(params)
        if filled is None:
            skipped += 1
            report.append(f"- SKIP `{action}` ({path}) - no sample id available")
            continue
        checked += 1
        lines: list[str] = []
        payload: dict | None = None
        # JSON (fetched once, reused for the XML field-set comparison).
        if args.format in ("json", "both"):
            try:
                payload = json.loads(client.call(action, filled, "json"))
                if isinstance(payload, dict) and "error" in payload:
                    lines.append(f"  - JSON error: {payload['error']}")
                    payload = None
                elif isinstance(payload, dict):
                    lines.extend(f"  - JSON {f}" for f in compare_json(spec, ref, payload))
            except Exception as exc:
                lines.append(f"  - JSON request failed: {exc}")
        # XML vs JSON field-set at the data-object level (skip free-form open maps).
        schema = resolve_ref(spec, ref)
        dk = data_key(schema)
        freeform = not schema.get("properties") and dk is None
        if args.format in ("xml", "both") and not freeform:
            try:
                xml_fields = xml_field_set(client.call(action, filled, "xml"), dk)
                json_fields: set[str] | None = None
                if payload is not None:
                    if dk is None:
                        json_fields = set(payload)
                    else:
                        val = payload.get(dk)
                        if isinstance(val, list):
                            json_fields = set(val[0]) if val else None
                        elif isinstance(val, dict):
                            json_fields = set(val)
                if xml_fields is not None and json_fields is not None:
                    only_xml = xml_fields - json_fields - _ENVELOPE_META
                    only_json = json_fields - xml_fields
                    if only_xml:
                        lines.append(f"  - XML-only elements/attrs: {sorted(only_xml)}")
                    if only_json:
                        lines.append(f"  - JSON-only keys (not XML elements): {sorted(only_json)}")
            except Exception as exc:
                lines.append(f"  - XML request failed: {exc}")

        if lines:
            flagged += 1
            report.append(f"- `{action}` ({path}) -> `{ref.rsplit('/', 1)[-1]}`")
            report.extend(lines)
        else:
            clean += 1

    summary = (f"\n## Summary\nchecked={checked} clean={clean} flagged={flagged} skipped={skipped}")
    print("\n".join(report))
    print(summary)
    if args.out:
        args.out.write_text("# API shape verification\n\n" + "\n".join(report) + "\n" + summary + "\n",
                            encoding="utf-8", newline="\n")
        print(f"\nwrote {args.out}")

    return 1 if (args.strict and flagged) else 0


if __name__ == "__main__":
    sys.exit(main())
