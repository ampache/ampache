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
| `docs/REST-to-RPC.md` | hand-maintained; its "Alternative action" column is the source of truth for aliases |

In the markdown documents only the block between `<!-- GENERATED:RESPONSE:BEGIN -->` and
`<!-- GENERATED:RESPONSE:END -->` is generated. The prose, the input-parameter tables, the `* throws`
blocks and the `[Example]` links are hand-written and are left alone.

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
| `Repository/Model/Search.php` (`get_rule_types()`) | `search_rules` |
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
