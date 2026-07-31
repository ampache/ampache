#!/usr/bin/env python3
"""Pretty-format (lint) GitHub-Flavored-Markdown pipe tables in a Markdown file.

Aligns every well-formed table so columns are padded to a consistent width and
delimiter rows carry the right alignment colons, matching the hand-written table
style already used in docs/API-*-methods.md:

    | Input     | Type   | Optional |
    |-----------|--------|---------:|
    | 'filter'  | string |       NO |

Content cells are wrapped in one space of padding; delimiter cells are filled
with dashes to the full column width (no surrounding spaces), preserving each
column's left/right/center alignment. Non-table lines are left untouched. The
pass is idempotent.

Usable as a library (``format_tables(text) -> str``) or a CLI:

    python resources/scripts/api-docs/format_md_tables.py FILE [FILE ...] [--check]
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

# Split a table row on unescaped pipes (so a literal "\|" inside a cell survives).
_PIPE_SPLIT = re.compile(r"(?<!\\)\|")
_DELIM_CELL = re.compile(r"^\s*:?-+:?\s*$")


def _is_row(line: str) -> bool:
    s = line.strip()
    return s.startswith("|") and s.count("|") >= 2


def _is_delimiter(line: str) -> bool:
    s = line.strip()
    if not s.startswith("|"):
        return False
    cells = _split_cells(line)
    return bool(cells) and all(_DELIM_CELL.match(c) for c in cells)


def _split_cells(line: str) -> list[str]:
    parts = _PIPE_SPLIT.split(line.strip())
    # Drop the empty strings produced by the leading and trailing pipe.
    if parts and parts[0].strip() == "":
        parts = parts[1:]
    if parts and parts[-1].strip() == "":
        parts = parts[:-1]
    return [p.strip() for p in parts]


def _alignment(delim_cell: str) -> str:
    c = delim_cell.strip()
    left = c.startswith(":")
    right = c.endswith(":")
    if left and right:
        return "center"
    if right:
        return "right"
    if left:
        return "left"
    return "none"


def _pad(content: str, width: int, align: str) -> str:
    gap = width - len(content)
    if gap <= 0:
        return content
    if align == "right":
        return " " * gap + content
    if align == "center":
        left = gap // 2
        return " " * left + content + " " * (gap - left)
    return content + " " * gap


def _delim(width: int, align: str) -> str:
    if align == "center":
        return ":" + "-" * (width - 2) + ":"
    if align == "right":
        return "-" * (width - 1) + ":"
    if align == "left":
        return ":" + "-" * (width - 1)
    return "-" * width


def _format_block(rows: list[list[str]], aligns: list[str], delim_index: int) -> list[str]:
    cols = max(len(r) for r in rows)
    rows = [r + [""] * (cols - len(r)) for r in rows]
    aligns = (aligns + ["none"] * cols)[:cols]
    # Column width = widest content + the two padding spaces (matches existing style).
    widths = [
        max((len(rows[i][c]) for i in range(len(rows)) if i != delim_index), default=1) + 2
        for c in range(cols)
    ]
    out: list[str] = []
    for idx, row in enumerate(rows):
        if idx == delim_index:
            cells = [_delim(widths[c], aligns[c]) for c in range(cols)]
            out.append("|" + "|".join(cells) + "|")
        else:
            cells = [" " + _pad(row[c], widths[c] - 2, aligns[c]) + " " for c in range(cols)]
            out.append("|" + "|".join(cells) + "|")
    return out


def format_tables(text: str) -> str:
    lines = text.split("\n")
    out: list[str] = []
    i = 0
    n = len(lines)
    while i < n:
        if _is_row(lines[i]) and i + 1 < n and _is_delimiter(lines[i + 1]):
            start = i
            block = [lines[i], lines[i + 1]]
            j = i + 2
            while j < n and _is_row(lines[j]) and not _is_delimiter(lines[j]):
                block.append(lines[j])
                j += 1
            rows = [_split_cells(l) for l in block]
            aligns = [_alignment(c) for c in _split_cells(block[1])]
            out.extend(_format_block(rows, aligns, delim_index=1))
            i = j
        else:
            out.append(lines[i])
            i += 1
    return "\n".join(out)


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("files", nargs="+", type=Path)
    ap.add_argument("--check", action="store_true", help="exit 1 if a file would change")
    args = ap.parse_args()

    changed = False
    for path in args.files:
        original = path.read_text(encoding="utf-8")
        updated = format_tables(original)
        if updated != original:
            changed = True
            print(f"{path}: {'would reformat' if args.check else 'reformatted'}")
            if not args.check:
                path.write_text(updated, encoding="utf-8", newline="\n")
        else:
            print(f"{path}: ok")
    return 1 if (changed and args.check) else 0


if __name__ == "__main__":
    sys.exit(main())
