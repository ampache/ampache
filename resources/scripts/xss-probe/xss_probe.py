#!/usr/bin/env python3
"""Seed hostile strings into a dev database, crawl the UI, and report anything echoed unescaped.

This is the half a static check cannot do. `tests/Gui/View/TemplateEscapingTest.php` proves a template
routes its values through `e()`/`raw()`; it cannot tell whether the thing behind a legitimate `raw()`
escapes what it builds. That is how the shoutbox renderer shipped an unescaped shout for years.

Point it at a THROWAWAY instance: it writes to the database and restores from its own in-memory copy,
so an interrupted run leaves the payloads in place.

  python3 xss_probe.py --base-url http://localhost:8084 --user admin --password demodemo
"""

from __future__ import annotations

import argparse
import re
import subprocess
import sys
import urllib.parse
from dataclasses import dataclass, field

# Angle brackets and quotes all have to survive for a finding to be real, and the marker makes the
# payload greppable in a response that legitimately contains a lot of markup.
MARKER = "AMPXSSPROBE"
MARKER_HEX = MARKER.encode().hex().upper()
PAYLOAD = f'<img src=x onerror={MARKER}>"\'</title>'
# some columns are as narrow as varchar(32), so a trimmed variant is used where the full one will not fit
SHORT_PAYLOAD = f"<b {MARKER}>"


@dataclass(frozen=True)
class Target:
    """A text column a user can set, and the pages that display it."""

    table: str
    column: str
    where: str = "1=1"
    limit: int = 1


@dataclass
class Result:
    url: str
    contexts: list[str] = field(default_factory=list)


def is_truncated(body: str) -> bool:
    """A page that stops mid-markup means the request threw and ApplicationRunner swallowed it.

    That failure answers 200 with a partial body, so nothing else notices it. It is checked here
    because this script already fetches every page.
    """
    stripped = body.rstrip()

    return stripped != "" and not stripped.endswith(("</html>", "</OpenSearchDescription>", "</root>"))


# Every column here is settable by some user through the UI or the API.
TARGETS = [
    Target("user", "fullname", "id = 1"),
    Target("user", "website", "id = 1"),
    Target("user", "state", "id = 1"),
    Target("user", "city", "id = 1"),
    Target("playlist", "name"),
    Target("search", "name"),
    Target("podcast", "title"),
    Target("podcast", "website"),
    Target("podcast", "description"),
    Target("podcast", "author"),
    Target("podcast_episode", "title"),
    Target("podcast_episode", "description"),
    Target("podcast_episode", "author"),
    Target("podcast_episode", "category"),
    Target("live_stream", "name"),
    Target("live_stream", "site_url"),
    Target("live_stream", "codec"),
    Target("label", "name"),
    Target("label", "summary"),
    Target("label", "address"),
    Target("label", "email"),
    Target("label", "website"),
    Target("tag", "name"),
    Target("share", "description"),
    Target("user_shout", "text"),
    Target("user_pvmsg", "subject"),
    Target("broadcast", "name"),
    Target("bookmark", "comment"),
    Target("catalog", "name"),
    Target("collection", "name"),
    Target("song", "title"),
    Target("album", "name"),
    Target("artist", "name"),
]

# Pages that render the seeded values. Anything requiring a POST is out of scope for the probe.
PAGES = [
    "index.php",
    "browse.php?action=song",
    "browse.php?action=album",
    "browse.php?action=artist",
    "browse.php?action=playlist",
    "browse.php?action=smartplaylist",
    "browse.php?action=live_stream",
    "browse.php?action=tag",
    "browse.php?action=label",
    "browse.php?action=podcast",
    "browse.php?action=podcast_episode",
    "browse.php?action=video",
    "browse.php?action=share",
    "browse.php?action=collection",
    "folders.php?action=show",
    "folders.php?action=show&folder=43",
    "video.php?action=show_video&video_id=1",
    "browse.php?action=broadcast",
    "browse.php?action=pvmsg",
    "stats.php?action=show_user&user_id=1",
    "stats.php?action=newest",
    "stats.php?action=popular",
    "stats.php?action=share",
    "song.php?action=show_song&song_id=1",
    "albums.php?action=show&album=1",
    "artists.php?action=show&artist=1",
    "playlist.php?action=show&playlist_id=1",
    "radio.php?action=show&radio=1",
    "labels.php?action=show&label=1",
    "podcast.php?action=show&podcast=1",
    "podcast_episode.php?action=show&podcast_episode=1",
    "search.php?action=search&type=song&rule_1=title&rule_1_operator=0&rule_1_input=a",
    "admin/catalog.php",
    "admin/users.php",
    "admin/shout.php",
    "admin/license.php",
    "admin/license.php?action=show_hidden",
    "stats.php?action=wanted",
    "preferences.php?action=show_preferences&tab=account",
    "preferences.php?action=show_preferences&tab=modules",
    "admin/modules.php?action=show_plugins",
    "democratic.php?action=manage_playlists",
    "preferences.php?action=show_preferences&tab=interface",
]


_ROOT_PASSWORD: str | None = None


def get_root_password(container: str) -> str:
    """Reads the container's root password once, so no shell ever sees the query text."""
    global _ROOT_PASSWORD
    if _ROOT_PASSWORD is None:
        _ROOT_PASSWORD = subprocess.run(
            ["docker", "exec", container, "printenv", "MARIADB_ROOT_PASSWORD"],
            capture_output=True, text=True, check=True,
        ).stdout.strip()
    return _ROOT_PASSWORD


def run_sql(container: str, database: str, sql: str) -> str:
    """Runs SQL as root inside the database container and returns the raw tab-separated output.

    The statement goes in on stdin rather than through `sh -c`, because backtick-quoted identifiers
    would otherwise be command-substituted by the shell.
    """
    completed = subprocess.run(
        ["docker", "exec", "-i", container,
         "mariadb", "-uroot", f"-p{get_root_password(container)}", database, "--batch", "--raw"],
        input=sql, capture_output=True, text=True, check=False,
    )
    if completed.returncode != 0:
        raise RuntimeError(f"sql failed: {completed.stderr.strip()}\n{sql}")
    return completed.stdout


def sql_quote(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def column_exists(container: str, database: str, table: str, column: str) -> bool:
    """The target list spans schema versions, so a column this install lacks is skipped, not fatal."""
    rows = run_sql(
        container, database,
        "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() "
        f"AND table_name = '{table}' AND column_name = '{column}';",
    ).splitlines()
    return len(rows) > 1 and rows[1].strip() != "0"


def column_length(container: str, database: str, table: str, column: str) -> int:
    """Text columns without a character limit (text, mediumtext) report NULL and take the full payload."""
    rows = run_sql(
        container, database,
        "SELECT IFNULL(character_maximum_length, 65535) FROM information_schema.columns "
        f"WHERE table_schema = DATABASE() AND table_name = '{table}' AND column_name = '{column}';",
    ).splitlines()
    return int(rows[1].strip()) if len(rows) > 1 and rows[1].strip().isdigit() else 0


def seed(
    container: str, database: str, targets: list[Target],
    saved: list[tuple[Target, list[tuple[str, str]]]],
) -> None:
    """Writes the payload into each target, returning what was there so it can be put back.

    Values round-trip as hex, because a description holding a tab or a newline would otherwise break
    the row-per-line output this parses.
    """
    skipped = []
    for target in targets:
        if not column_exists(container, database, target.table, target.column):
            skipped.append(f"{target.table}.{target.column}")
            continue
        rows = run_sql(
            container, database,
            f"SELECT `id`, IFNULL(HEX(`{target.column}`), 'NULL') FROM `{target.table}` "
            f"WHERE {target.where} ORDER BY `id` LIMIT {target.limit};",
        ).splitlines()[1:]
        originals = [(row.split("\t")[0], row.split("\t")[1]) for row in rows if "\t" in row]
        poisoned = [row_id for row_id, hex_value in originals if MARKER_HEX in hex_value]
        if poisoned:
            raise RuntimeError(
                f"{target.table}.{target.column} already holds the payload (ids {', '.join(poisoned)}). "
                "A previous run did not restore; fix those rows before probing again."
            )
        if not originals:
            continue
        ids = ",".join(row_id for row_id, _ in originals)
        payload = PAYLOAD if column_length(container, database, target.table, target.column) >= len(PAYLOAD) else SHORT_PAYLOAD
        run_sql(
            container, database,
            f"UPDATE `{target.table}` SET `{target.column}` = {sql_quote(payload)} "
            f"WHERE `id` IN ({ids});",
        )
        saved.append((target, originals))
    if skipped:
        print(f"  (not in this schema, skipped: {', '.join(skipped)})")


def restore(container: str, database: str, saved: list[tuple[Target, list[tuple[str, str]]]]) -> None:
    for target, originals in saved:
        for row_id, hex_value in originals:
            literal = "NULL" if hex_value == "NULL" else f"UNHEX('{hex_value}')"
            run_sql(
                container, database,
                f"UPDATE `{target.table}` SET `{target.column}` = {literal} WHERE `id` = {row_id};",
            )


def login(base_url: str, jar: str, user: str, password: str) -> None:
    form = subprocess.run(
        ["curl", "-s", "-c", jar, "-b", jar, f"{base_url}/login.php"],
        capture_output=True, text=True, check=True,
    ).stdout
    match = re.search(r'name="ampache_login_form"[^>]*value="([^"]+)"', form)
    token = match.group(1) if match else ""
    subprocess.run(
        ["curl", "-s", "-c", jar, "-b", jar, "-o", "/dev/null", "-X", "POST", f"{base_url}/login.php",
         "-d", urllib.parse.urlencode(
             {"username": user, "password": password, "referrer": "", "ampache_login_form": token})],
        check=True,
    )


def fetch(base_url: str, jar: str, page: str) -> str:
    return subprocess.run(
        ["curl", "-s", "-b", jar, "-c", jar, f"{base_url}/{page}"],
        capture_output=True, text=True, check=False,
    ).stdout


def find_unescaped(body: str) -> list[str]:
    """Returns the surrounding markup for each place the payload survived intact.

    The escaped form contains the marker too, so only the literal payload counts as a finding.
    """
    contexts = []
    for literal in (f"<img src=x onerror={MARKER}>", f"<b {MARKER}>"):
        start = 0
        while (at := body.find(literal, start)) != -1:
            snippet = body[max(0, at - 120):at + len(literal)]
            contexts.append(" ".join(snippet.split())[-150:])
            start = at + len(literal)
    return contexts


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base-url", default="http://localhost:8084")
    parser.add_argument("--db-container", default="ampache-db")
    parser.add_argument("--database", default="ampache")
    parser.add_argument("--user", default="admin")
    parser.add_argument("--password", default="demodemo")
    parser.add_argument("--jar", default="/tmp/ampache-xss-probe.cookies")
    args = parser.parse_args()

    print(f"seeding {len(TARGETS)} columns ...", flush=True)
    saved: list[tuple[Target, list[tuple[str, str]]]] = []
    findings: list[Result] = []
    truncated: list[str] = []
    try:
        # seeding is inside the try so a failure part way through still restores what it wrote
        seed(args.db_container, args.database, TARGETS, saved)
        print(f"seeded: {', '.join(f'{t.table}.{t.column}' for t, _ in saved)}\n", flush=True)

        login(args.base_url, args.jar, args.user, args.password)
        for page in PAGES:
            body = fetch(args.base_url, args.jar, page)
            # the escaped form contains the marker too, so only the literal payload counts
            if is_truncated(body):
                truncated.append(page)
                print(f"  TRUNC {page}", flush=True)

            contexts = find_unescaped(body)
            if contexts:
                findings.append(Result(page, contexts))
                print(f"  HOLE  {page}", flush=True)
            elif MARKER in body:
                print(f"  ok    {page}", flush=True)
    finally:
        restore(args.db_container, args.database, saved)
        print("\nrestored original values")

    if truncated:
        print(f"\n{len(truncated)} page(s) stopped mid-render, so something threw:\n")
        for page in truncated:
            print(f"  {page}")

    if findings:
        print(f"\n{len(findings)} page(s) rendered the payload unescaped:\n")
        for finding in findings:
            print(f"  {finding.url}")
            for context in finding.contexts[:2]:
                print(f"      {context}")

    if findings or truncated:
        return 1

    print("\nno unescaped output found, no truncated pages")
    return 0


if __name__ == "__main__":
    sys.exit(main())
