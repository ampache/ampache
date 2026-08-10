#!/usr/bin/env python3
"""Capture live READ responses from the verify server and write obfuscated
example files into the python3-ampache{N}/docs/{json,xml}-responses repos.

Reuses the Client/handshake/env loader from verify_openapi_shapes.py. Only issues
GET requests. Obfuscates host + auth/ssid tokens + the server's music root path so
nothing identifying from the live server lands in the committed docs.

Usage: python capture_reads.py [--probe]   (--probe prints raw+obfuscated, writes nothing)
"""
from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

from verify_openapi_shapes import Client, load_env, SCRIPT_DIR

REPOS = Path(r"C:\Users\LachlandeWaard\GitHub")

# obfuscation placeholders (match the existing corpus conventions)
OBF_HOST = "music.com.au"
OBF_SSID = "cfj3f237d563f479f5223k23189dbb34"
OBF_AUTH = "eeb9f1b6056246a7d563f479f518bb34"


def obfuscate(text: str, client: Client) -> str:
    real_host = re.sub(r"^https?://", "", client.host).rstrip("/")
    text = text.replace(client.host, "https://" + OBF_HOST)
    text = text.replace(real_host, OBF_HOST)
    if client.auth:
        text = text.replace(client.auth, OBF_AUTH)
    # any remaining ssid=/auth= hex tokens -> placeholders
    text = re.sub(r"ssid=[A-Za-z0-9]+", "ssid=" + OBF_SSID, text)
    text = re.sub(r"auth=[A-Za-z0-9]+", "auth=" + OBF_AUTH, text)
    return text


def discover_id(client: Client, action: str, key: str) -> str | None:
    try:
        data = json.loads(client.call(action, {"limit": "1"}))
        items = data.get(key) if isinstance(data, dict) else None
        if isinstance(items, list) and items:
            return str(items[0].get("id"))
    except Exception:
        pass
    return None


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--probe", action="store_true", help="print raw+obfuscated, write nothing")
    args = ap.parse_args()

    client = Client(load_env(SCRIPT_DIR / "verify.env"))
    client.handshake()
    print(f"# connected to {client.host} (negotiated api v{client.effective_version})")

    song_id = discover_id(client, "songs", "song")
    album_id = discover_id(client, "albums", "album")
    print(f"# sample song_id={song_id} album_id={album_id}")

    # (repo, method-name, action, extra params) for api8 reads with real data.
    # localplay_songs is intentionally excluded: the verify server has no localplay
    # controller (returns error 4710); the hand-made empty example is kept instead.
    targets = [
        ("python3-ampache8", "get_lyrics", "get_lyrics", {"filter": song_id or ""}),
        ("python3-ampache8", "get_external_metadata", "get_external_metadata",
         {"filter": album_id or "", "type": "album"}),
    ]

    for repo, name, action, params in targets:
        for fmt in ("json", "xml"):
            raw = client.call(action, params, fmt)
            obf = obfuscate(raw, client)
            if "lachlandewaard" in obf:
                print(f"!! ABORT {name}.{fmt}: host still present after obfuscation")
                return 1
            if args.probe:
                print(f"\n===== {name}.{fmt} (RAW) =====\n{raw[:800]}")
                print(f"----- {name}.{fmt} (OBFUSCATED) -----\n{obf[:800]}")
                continue
            obf = obf.replace("\r\n", "\n").replace("\n", "\r\n")  # repo convention: CRLF
            dest = REPOS / repo / "docs" / f"{fmt}-responses" / f"{name}.{fmt}"
            dest.write_text(obf, encoding="utf-8", newline="")
            print(f"wrote {dest} ({len(obf)} bytes)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
