#!/usr/bin/env python3
"""Structural lint for docs/openapi.json (the invariants the cleanup established).

The schema/response/parameter generators produce a correct spec; this guards against
hand edits or future generator changes silently breaking the structure the audit
fixed. It asserts, across the whole document:

  1. every operation carries an operationId (codegen names methods from it)
  2. no schema uses additionalProperties: false (a new response field must not break
     validating clients)
  3. every operation documents the universal error set 400/401/403/500
  4. the shared parameters live in components.parameters and are $ref'd, never
     re-declared inline (offset/limit/cond/sort/client/the rule grammar/path ids)
  5. every $ref resolves
  6. no two named schemas are byte-for-byte identical, except the generated *Request
     bodies (create/edit with the same fields) and a small allowlist of intentional
     twins

This is the Python stand-in for the Spectral/Redocly lint the plan named; the repo's
other doc checks are all Python under `composer api:docs:check`, so it lives there too.

Usage:
    python resources/scripts/api-docs/lint_openapi.py
"""
from __future__ import annotations

import json
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[3]
OPENAPI = REPO_ROOT / "docs" / "openapi.json"

HTTP_METHODS = ("get", "post", "put", "patch", "delete")
REQUIRED_ERROR_CODES = ("400", "401", "403", "500")

# Named schemas that are intentionally identical in shape. Generated *Request bodies
# are excluded wholesale (create/edit endpoints legitimately share a field set).
IDENTICAL_SCHEMA_ALLOWLIST = {
    # list-item and browse-item stubs; identical today but kept distinct so either can
    # gain a field without disturbing the other (S3 decision in the cleanup plan).
    frozenset({"ListObject", "BrowseObject"}),
}


def operations(spec: dict):
    for path, item in spec.get("paths", {}).items():
        for method, operation in item.items():
            if method in HTTP_METHODS:
                yield f"{method.upper()} {path}", operation


def each_ref(node: object):
    if isinstance(node, list):
        for item in node:
            yield from each_ref(item)
    elif isinstance(node, dict):
        ref = node.get("$ref")
        if isinstance(ref, str):
            yield ref
        for value in node.values():
            yield from each_ref(value)


def resolves(spec: dict, ref: str) -> bool:
    node: object = spec
    for part in ref.lstrip("#/").split("/"):
        part = part.replace("~1", "/").replace("~0", "~")
        if not isinstance(node, dict) or part not in node:
            return False
        node = node[part]
    return True


def check_operation_ids(spec: dict) -> list[str]:
    return [f"{label}: missing operationId" for label, op in operations(spec) if "operationId" not in op]


def check_no_additional_false(spec: dict) -> list[str]:
    out = []
    for name, schema in spec.get("components", {}).get("schemas", {}).items():
        for node in _walk_objects(schema):
            if node.get("additionalProperties") is False:
                out.append(f"schema {name}: additionalProperties: false (breaks clients on a new field)")
                break
    return out


def _walk_objects(node: object):
    if isinstance(node, dict):
        yield node
        for value in node.values():
            yield from _walk_objects(value)
    elif isinstance(node, list):
        for item in node:
            yield from _walk_objects(item)


def check_error_set(spec: dict) -> list[str]:
    out = []
    for label, op in operations(spec):
        responses = op.get("responses", {})
        missing = [code for code in REQUIRED_ERROR_CODES if code not in responses]
        if missing:
            out.append(f"{label}: missing error responses {missing}")
    return out


def check_shared_params_not_inline(spec: dict) -> list[str]:
    shared = {
        (param["name"], param["in"])
        for param in spec.get("components", {}).get("parameters", {}).values()
    }
    out = []
    for label, op in operations(spec):
        for param in op.get("parameters", []):
            if "$ref" in param:
                continue
            if (param.get("name"), param.get("in")) in shared:
                out.append(f"{label}: parameter '{param.get('name')}' declared inline; use the shared $ref")
    return out


def check_refs_resolve(spec: dict) -> list[str]:
    return [f"dangling $ref: {ref}" for ref in sorted(set(each_ref(spec))) if not resolves(spec, ref)]


def check_no_identical_schemas(spec: dict) -> list[str]:
    schemas = spec.get("components", {}).get("schemas", {})
    by_shape: dict[str, list[str]] = {}
    for name, schema in schemas.items():
        if name.endswith("Request"):  # generated bodies; create/edit share a field set
            continue
        by_shape.setdefault(json.dumps(schema, sort_keys=True), []).append(name)
    out = []
    for names in by_shape.values():
        if len(names) > 1 and frozenset(names) not in IDENTICAL_SCHEMA_ALLOWLIST:
            out.append(f"structurally identical schemas: {', '.join(sorted(names))} (extract or allowlist)")
    return out


CHECKS = (
    ("operationId present", check_operation_ids),
    ("no additionalProperties: false", check_no_additional_false),
    ("universal error set", check_error_set),
    ("shared parameters $ref'd", check_shared_params_not_inline),
    ("$refs resolve", check_refs_resolve),
    ("no duplicate schemas", check_no_identical_schemas),
)


def main() -> int:
    spec = json.loads(OPENAPI.read_text(encoding="utf-8"))
    failures = 0
    for title, check in CHECKS:
        violations = check(spec)
        if violations:
            failures += len(violations)
            print(f"FAIL {title} ({len(violations)}):")
            for line in violations:
                print(f"  {line}")
        else:
            print(f"ok   {title}")
    if failures:
        print(f"\n{failures} structural violation(s) in {OPENAPI.relative_to(REPO_ROOT)}")
        return 1
    print("\nopenapi.json structure is clean")
    return 0


if __name__ == "__main__":
    sys.exit(main())
