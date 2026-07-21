#!/usr/bin/env python3
"""Check that the inline ``examples`` in docs/openapi.json agree with the schema
their operation is wired to.

``generate_openapi_schemas.py`` points each 200 response at a generated schema but
deliberately leaves the hand-written ``examples`` alone. That is how an example can
drift: a DELETE endpoint keeps the object example it was copied from, a create
endpoint shows the whole list envelope, or a data endpoint still shows a
``{"success": ...}`` placeholder. None of that is caught by the schema generator or
by the live-shape verifier (which drives the server, not the docs).

For every 200 response wired to a ``$ref``, this compares each example's top-level
keys against that schema: keys the schema requires but the example omits, and keys
the example carries that the schema does not document (only when the schema sets
``additionalProperties: false``). Schemas without fixed properties (``oneOf``,
free-form maps) are skipped - there is nothing to compare against.

Usage:
    python resources/scripts/api-docs/check_openapi_examples.py [--strict]

    --strict   exit 1 when any mismatch is found (for CI)
"""
from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[3]
OPENAPI = REPO_ROOT / "docs" / "openapi.json"

HTTP_METHODS = ("get", "post", "put", "patch", "delete")


def resolve_ref(spec: dict, ref: str) -> dict:
    node: dict = spec
    for part in ref.lstrip("#/").split("/"):
        node = node[part.replace("~1", "/").replace("~0", "~")]
    return node


def branches(spec: dict, schema: dict) -> list[dict]:
    """The alternative shapes an example may match: a `oneOf` offers several, anything
    else just itself. Branches without fixed properties can't be compared, so a schema
    that has any such branch accepts everything and is dropped."""
    members = schema.get("oneOf", [schema])
    resolved = [resolve_ref(spec, m["$ref"]) if "$ref" in m else m for m in members]
    if any(not m.get("properties") for m in resolved):
        return []
    return resolved


def check(spec: dict) -> list[tuple[str, str, str, str, list[str], list[str]]]:
    findings = []
    for path, item in spec.get("paths", {}).items():
        for method, operation in item.items():
            if method not in HTTP_METHODS or not isinstance(operation, dict):
                continue
            content = (
                operation.get("responses", {})
                .get("200", {})
                .get("content", {})
                .get("application/json", {})
            )
            ref = content.get("schema", {}).get("$ref")
            if not ref:
                continue
            options = branches(spec, resolve_ref(spec, ref))
            if not options:
                continue
            for name, example in (content.get("examples") or {}).items():
                value = example.get("value")
                if not isinstance(value, dict):
                    continue
                keys = set(value)
                best: tuple[list[str], list[str]] | None = None
                for option in options:
                    documented = set(option["properties"])
                    required = set(option.get("required", []))
                    closed = option.get("additionalProperties", True) is False
                    missing = sorted(required - keys)
                    extra = sorted(keys - documented) if closed else []
                    if not missing and not extra:
                        best = None
                        break
                    # report the closest branch (fewest complaints)
                    if best is None or len(missing) + len(extra) < len(best[0]) + len(best[1]):
                        best = (missing, extra)
                if best is not None:
                    findings.append(
                        (method.upper(), path, ref.rsplit("/", 1)[-1], name, best[0], best[1])
                    )
    return findings


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--strict", action="store_true", help="exit 1 if any example mismatches")
    args = parser.parse_args()

    spec = json.loads(OPENAPI.read_text(encoding="utf-8"))
    findings = check(spec)

    for method, path, schema, name, missing, extra in findings:
        print(f"{method:6s} {path}  -> {schema}  (example: {name})")
        if missing:
            print(f"         schema requires but example omits: {missing}")
        if extra:
            print(f"         example carries undocumented keys: {extra}")

    print(f"\n{len(findings)} example(s) disagree with their schema")
    return 1 if (findings and args.strict) else 0


if __name__ == "__main__":
    sys.exit(main())
