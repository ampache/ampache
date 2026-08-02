# API documentation tooling

`docs/openapi.json` and the two method-reference documents are **generated**. They are not just
published documentation — `tests/Module/Api/RestSpecConformanceTest.php` validates captured response
fixtures against the spec, `tests/Module/Api/Api8SpecCoverageTest.php` asserts it still covers every
API8 method, and `verify_openapi_shapes.py` validates a live server against it. A wrong spec means a
test that silently passes.

## Running them

Order matters: the schemas land in `docs/openapi.json` first, then the markdown tables are built from
that file.

```sh
python resources/scripts/api-docs/generate_openapi_schemas.py     # -> docs/openapi.json
python resources/scripts/api-docs/generate_api_methods_md.py      # -> docs/API-{JSON,XML}-methods.md
python resources/scripts/api-docs/check_openapi_examples.py --strict
```

Or all three in check-only mode, which is what CI runs:

```sh
composer api:docs:check
```

**`check_openapi_examples.py` only fails with `--strict`.** Without that flag it prints its findings
and still exits `0`. The two generators exit `1` from `--check` on their own. Do not pipe any of them
through `tail`/`grep` when you care about the exit status — the pipe reports the exit status of the
last command, which will mask a failure.

## Where each file comes from

| File | Owner |
|---|---|
| `docs/openapi.json` | `generate_openapi_schemas.py` |
| `docs/API-JSON-methods.md`, `docs/API-XML-methods.md` | `generate_api_methods_md.py` (response tables only) |
| `docs/openapi-6.json` | **hand-maintained**, guarded by `Api6SpecConformanceTest` |
| `docs/openapi-opensubsonic.json` | **upstream's artefact**, copied verbatim — never hand-edit (see below) |
| `docs/REST-to-RPC.md` | hand-maintained; its "Alternative action" column is the source of truth for aliases |

In the markdown documents only the block between `<!-- GENERATED:RESPONSE:BEGIN -->` and
`<!-- GENERATED:RESPONSE:END -->` is generated. The prose, the input-parameter tables, the `* throws`
blocks and the `[Example]` links are hand-written and are left alone.

## Refreshing the OpenSubsonic spec

`docs/openapi-opensubsonic.json` is built by the OpenSubsonic project, not by us. It is committed verbatim so the
conformance tests can run offline, and it is **never hand-edited** — an edit would make Ampache validate against a
spec nobody else has.

Upstream rebuilds it continuously, so it is pinned rather than tracked: `tests/Module/Api/OpenSubsonicSpecVersionTest.php`
records the checksum and endpoint count of the build the implementation was last audited against, and
`docs/API-subsonic.md` records the date of that audit.

To take a newer build:

```shell
curl -o docs/openapi-opensubsonic.json https://opensubsonic.netlify.app/docs/openapi/openapi.json
composer qa
```

`OpenSubsonicSpecVersionTest` will fail, which is the point — it means the surface moved. Re-audit the
implementation against the new build, then update `SPEC_SHA256` (and `SPEC_PATH_COUNT` if endpoints were added or
removed) in that test and the compliance date in `docs/API-subsonic.md`. `testEveryDocumentedEndpointHasAHandler`
names any endpoint the new build documents that Ampache does not serve.

Response fixtures are captured separately with `capture_subsonic_fixtures.php`; see its header for what it covers
and why the mutating and binary endpoints are excluded.

## Response schemas come from docblocks

Every schema is derived from the PHPStan `@return array{...}` shape on a builder method. **To change
a documented field, change the docblock** — editing `docs/openapi.json` directly is reverted the next
time the generator runs.

The default source is `src/Module/Api/Json8_Data.php` (the `*_array()` builders). `SOURCES` adds a few
payloads assembled elsewhere:

| Source | Used for |
|---|---|
| `Method/PreferenceItemBuilder.php` | preference endpoints |
| `Api.php` (`server_details()`) | `handshake`, and `ping` derived from it |
| `Module/Database/Query/Search.php` (`get_rule_types()`) | `search_rules` |
| `Playback/Localplay/LocalPlay.php` (`get()`) | `localplay_songs` |

## The escape hatches

Reach for these only when a shape genuinely cannot be expressed in a docblock. Each one is a place
where the generator stops deriving and starts asserting, so each is a small maintenance debt.

| Table | Purpose |
|---|---|
| `TYPES` | object/list schema names + envelope for a builder |
| `WIRING` | RPC action -> schema, **GET only** |
| `WIRING_BY_METHOD` | `(verb, action)` -> schema, for everything that is not a GET |
| `WIRING_BY_PARAM` | `(action, param, value)` -> schema, where the shape depends on a request parameter baked into the path (e.g. `/albums/search` -> `type=album`) |
| `SUCCESS_ACTIONS` | the mutation/command actions that answer `{"success": "..."}`, folded into the two tables above |
| `BINARY_RESPONSES` | `stream`/`download`/`get_art`, documented as redirects or image bodies |
| `BINARY_REQUEST_BODIES` | actions that carry a file rather than parameters (`upload`). `apply_request_bodies()` would otherwise replace the binary body with one mirroring the query parameters, so the body is declared here and that step skips the operation |
| `MANUAL_SCHEMAS` | schemas with no single docblock behind them (`IndexResponse`, the democratic and localplay variants, ...) |
| `REF_REUSE` | replace an inlined shape with a `$ref` to an existing schema (`AlbumObject.tracks` -> `SongObject`) |
| `PROPERTY_DESCRIPTIONS` | prose for a property whose meaning the type alone does not convey |
| `ERROR_RESPONSE_REFS` / `ALWAYS_ERRORS` / `DEPRECATED_ACTIONS` | the error responses applied to every operation, derived from `Api::getHttpCode()` |
| `SHARED_PARAMETERS` | parameters repeated across many operations (`offset`, `limit`, `cond`, `sort`, the search-rule grammar, every path id), lifted into `components.parameters` and `$ref`'d. Only names with one consistent meaning are here; `type`, `filter`, `include`, `exact` and `random` stay inline because their schema varies per operation |

Beyond the response schemas, the generator also derives, in `main()` order: the 200 `$ref` wiring
(`wire_responses`), binary responses (`document_binary_responses`), the error set on every operation
(`apply_error_responses`), a `requestBody` mirroring each write operation's query parameters
(`apply_request_bodies`), a stable `operationId` per operation (`apply_operation_ids`), and the shared
parameter extraction (`apply_parameter_components`). Anything these produce is regenerated every run —
edit the tables above, not `docs/openapi.json`.

The readers (`apply_request_bodies`, `apply_error_responses`) call `resolved_parameters()` so they see
through a `$ref` into `components.parameters`; without that the parameter extraction would make the
generator non-idempotent. Do not read `operation["parameters"]` directly in a new generator step —
resolve it.

### Hand-editing generated regions gets reverted

`PlaylistObject.items` was once refined by hand directly in `docs/openapi.json`: a precise
`{id, playlisttrack}` shape plus a paragraph explaining why a playlist reports only songs. Because
nothing enforced the generator, the edit survived in the committed file while
`generate_openapi_schemas.py --check` reported `CHANGED` on a clean checkout — and the next person to
run the generator silently reverted it.

The fix was to move both halves to where the generator could reproduce them: the shape went into the
`Json8_Data::playlists_array()` docblock, and the prose into `PROPERTY_DESCRIPTIONS`. Do the same with
any hand-edit you are tempted to make.

## Error responses

`Api::getHttpCode()` in `src/Module/Api/Api.php` is the authority on which HTTP status an API error
code produces. `apply_error_responses()` mirrors it: every operation documents `400`, `401`, `403` and
`500`; `404` is added where the operation addresses one object; `410` where the action is one that
`ApiHandler::$deprecated8` rejects. Codes outside that managed set — the `302` redirects and the `416`
range errors — are preserved as-is.

Note this applies to API8 only. API versions 3-6 always answer HTTP 200 with the error in the body,
which is why `docs/openapi-6.json` documents no error statuses at all and
`Api6SpecConformanceTest::testNoErrorStatusCodesAreDocumented()` asserts that.

## Live verification

`verify_openapi_shapes.py` drives a real server and reports where the response keys diverge from the
schema. It stays manual: it needs credentials and a populated library.

Configuration comes from `verify.env`, which is **not** tracked because it holds credentials — copy
`verify.env.example` and fill it in. Point it only at a server you own; it authenticates as your user,
though it only issues GET requests.

`capture_rest_fixtures.php` refreshes the fixtures under `tests/Fixtures/Api/rest/` that
`RestSpecConformanceTest` validates. Fixture coverage is partial, so a documented path without a
fixture is not response-validated by the test suite.

## Structural lint

`lint_openapi.py` (run by `composer api:docs:check`) asserts the invariants the spec cleanup
established, so a hand edit or a future generator change cannot silently undo them: every operation has
an `operationId` and the universal `400/401/403/500` error set, no schema uses
`additionalProperties: false`, the shared parameters are always `$ref`'d (never re-declared inline),
every `$ref` resolves, and no two named schemas are byte-identical (bar the generated `*Request` bodies
and an explicit allowlist). `generate_api_methods_md.py` also fails fast if it resolves zero response
schemas — the signature of the bug where a change to the `x-rpc-mappings` key format froze the response
tables.

## Design notes (decisions deliberately taken, so they are not re-adopted)

These were considered during the cleanup and rejected on purpose. Do not "fix" them without new
information.

- **XML is not documented byte-for-byte.** The spec describes the JSON serialisation; `info.description`
  states XML mirrors it structurally (attributes/element nesting differ). `Xml8_Data` builds XML by
  string concatenation with no array intermediate to derive a schema from, so a second XML spec would be
  hand-maintained and drift. One spec, JSON, with an XML structure note.
- **`object_type` stays an open string, never an enum.** Pinning today's media types would make every new
  type a breaking spec change; the field carries a description listing current values instead.
- **`now_playing` / `shout` keep an inline `{id, username}` user stub** rather than `$ref`-ing
  `UserSummaryObject`. Their builders return a non-null `username` (`getUsername()` / `?? ''`), so the
  nullable `UserSummaryObject` would misdocument them. Only `playlist` and `activity` (nullable username)
  were wired to it.
- **`ListObject` and `BrowseObject` are kept as separate names** though identical today, so either can
  gain a field without disturbing the other. The lint allowlists the pair.
- **No `allOf` base extraction** (list envelope, server-status fields, media attributes, deleted-media
  base, plugin-payload). `openapi.json` is generated and its *source* is already DRY (one
  `build_list_response()`, one `build_deleted_schemas()`, ping built from handshake), so `allOf` would
  remove no real duplication while adding composition many 3.0.3 codegen/validator tools handle poorly.
  Only `$ref` extraction of genuinely inline stubs (`NamedReference`, `GenreReference`) was done, which
  gives codegen named types with no downside.
