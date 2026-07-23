<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Api;

use PHPUnit\Framework\TestCase;

/**
 * Checks that docs/openapi.json still covers everything API8 serves.
 *
 * The spec is consumed by more than the published documentation: `RestSpecConformanceTest` validates
 * captured fixtures against it and `resources/scripts/api-docs/verify_openapi_shapes.py` validates a
 * live server against it. A method missing from the spec is therefore silently untested, which is how
 * `/random` ended up as the only path with no `x-rpc-mappings` entry and `localplay_songs` with no
 * path at all.
 *
 * Two traps this encodes, both of which produced wrong answers when audited by hand:
 *   - `x-rpc-mappings` keys may carry an http verb (`PUT /catalogs`, `GET /songs/{song_id}/bookmark`)
 *   - an action may legitimately have no path of its own because it is an alias, in which case it is
 *     recorded in the "Alternative action" column of docs/REST-to-RPC.md (e.g. `catalog_add` is the
 *     alias of `PUT /catalogs`). That table stays the single source of truth for aliases.
 */
class Api8SpecCoverageTest extends TestCase
{
    private const string REST_TO_RPC = __DIR__ . '/../../../docs/REST-to-RPC.md';
    private const string SPEC        = __DIR__ . '/../../../docs/openapi.json';

    /**
     * The actions recorded in the "Alternative action" column of docs/REST-to-RPC.md
     *
     * @return list<string>
     */
    private static function documentedAliases(): array
    {
        $rows = (string) file_get_contents(self::REST_TO_RPC);

        // | HTTP | REST | RPC action | Alternative action |
        preg_match_all('/^\|[^|]*\|[^|]*\|[^|]*\|\s*`([a-z_0-9]+)`\s*\|$/m', $rows, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Every `action=` named by a mapping, whether or not the key carries a verb.
     *
     * @param array<string, mixed> $spec
     *
     * @return list<string>
     */
    private static function mappedActions(array $spec): array
    {
        $actions = [];
        foreach ($spec['x-rpc-mappings'] as $mapping) {
            if (preg_match('/action=([a-z_0-9]+)/', (string) $mapping, $matches) === 1) {
                $actions[] = $matches[1];
            }
        }

        return array_values(array_unique($actions));
    }

    /**
     * @return array<string, mixed>
     */
    private static function spec(): array
    {
        $spec = json_decode((string) file_get_contents(self::SPEC), true);

        self::assertIsArray($spec, 'docs/openapi.json is not valid JSON');

        return $spec;
    }

    /**
     * A documented action that API8 does not serve is a dead path.
     */
    public function testDocumentedActionsAreServedByApi8(): void
    {
        $spec     = self::spec();
        $served   = array_keys(Api::METHOD_LIST);
        $unserved = [];

        foreach (self::mappedActions($spec) as $action) {
            if (!in_array($action, $served, true)) {
                $unserved[] = $action;
            }
        }

        sort($unserved);

        self::assertSame([], $unserved, 'docs/openapi.json documents actions missing from Api::METHOD_LIST');
    }

    /**
     * Every handler must be reachable from a documented path, or recorded as an alias.
     */
    public function testEveryHandlerIsDocumented(): void
    {
        $spec     = self::spec();
        $mapped   = self::mappedActions($spec);
        $aliases  = self::documentedAliases();
        $reached  = [];
        $handlers = [];

        foreach (Api::METHOD_LIST as $action => $handler) {
            $handlers[$handler][] = $action;
            if (in_array($action, $mapped, true) || in_array($action, $aliases, true)) {
                $reached[$handler] = true;
            }
        }

        $undocumented = [];
        foreach ($handlers as $handler => $actions) {
            if (!array_key_exists($handler, $reached)) {
                $undocumented[] = sprintf('%s (%s)', $handler, implode(', ', $actions));
            }
        }

        sort($undocumented);

        self::assertSame(
            [],
            $undocumented,
            'Api::METHOD_LIST handlers with no documented REST path and no alias in docs/REST-to-RPC.md'
        );
    }

    /**
     * A mapping to a path that does not exist reads as a documented endpoint while documenting
     * nothing callable, so both directions have to be checked.
     */
    public function testEveryMappingHasAPath(): void
    {
        $spec    = self::spec();
        $paths   = $spec['paths'];
        $dangled = [];

        foreach (array_keys($spec['x-rpc-mappings']) as $key) {
            $path = (preg_match('#^(?:GET|POST|PUT|PATCH|DELETE)\s+(/\S*)$#', (string) $key, $matches) === 1)
                ? $matches[1]
                : (string) $key;

            if (!array_key_exists($path, $paths)) {
                $dangled[] = (string) $key;
            }
        }

        sort($dangled);

        self::assertSame([], $dangled, 'docs/openapi.json maps paths that are not declared in `paths`');
    }

    /**
     * A bare `/path` mapping key is ambiguous once a path has more than one method, so every key
     * carries its verb.
     */
    public function testEveryMappingKeyIsVerbPrefixed(): void
    {
        $spec       = self::spec();
        $unprefixed = [];

        foreach (array_keys($spec['x-rpc-mappings']) as $key) {
            if (preg_match('#^(?:GET|POST|PUT|PATCH|DELETE)\s+/#', (string) $key) !== 1) {
                $unprefixed[] = (string) $key;
            }
        }

        self::assertSame([], $unprefixed, 'docs/openapi.json has x-rpc-mappings keys without a METHOD prefix');
    }

    /**
     * A path with no mapping cannot be resolved back to an RPC action, so nothing can verify it.
     */
    public function testEveryPathHasAnRpcMapping(): void
    {
        $spec     = self::spec();
        $mappings = $spec['x-rpc-mappings'];
        $unmapped = [];

        foreach (array_keys($spec['paths']) as $path) {
            if (array_key_exists($path, $mappings)) {
                continue;
            }

            // a path may instead be covered entirely by verb-prefixed keys
            $verbed = false;
            foreach (array_keys($mappings) as $key) {
                if (preg_match('/^(?:GET|POST|PUT|PATCH|DELETE)\s+' . preg_quote((string) $path, '/') . '$/', (string) $key) === 1) {
                    $verbed = true;
                    break;
                }
            }

            if (!$verbed) {
                $unmapped[] = $path;
            }
        }

        self::assertSame([], $unmapped, 'docs/openapi.json documents paths with no x-rpc-mappings entry');
    }

    /**
     * Every $ref must resolve, otherwise the spec cannot be used to validate a response.
     */
    public function testEverySchemaReferenceResolves(): void
    {
        $spec       = self::spec();
        $schemas    = array_keys($spec['components']['schemas']);
        $responses  = array_keys($spec['components']['responses']);
        $parameters = array_keys($spec['components']['parameters'] ?? []);
        $dangling   = [];

        array_walk_recursive(
            $spec,
            static function ($value, $key) use ($schemas, $responses, $parameters, &$dangling): void {
                if ($key !== '$ref' || !is_string($value)) {
                    return;
                }

                if (str_starts_with($value, '#/components/schemas/') && !in_array(basename($value), $schemas, true)) {
                    $dangling[$value] = $value;
                }

                // response refs may point at a whole response or into its examples
                if (str_starts_with($value, '#/components/responses/')) {
                    $name = explode('/', substr($value, strlen('#/components/responses/')))[0];
                    if (!in_array($name, $responses, true)) {
                        $dangling[$value] = $value;
                    }
                }

                if (str_starts_with($value, '#/components/parameters/') && !in_array(basename($value), $parameters, true)) {
                    $dangling[$value] = $value;
                }
            }
        );

        self::assertSame([], array_values($dangling), 'docs/openapi.json contains dangling $ref values');
    }

    /**
     * A placeholder schema documents nothing and silently passes response validation.
     */
    public function testNoOperationKeepsAPlaceholderSchema(): void
    {
        $spec         = self::spec();
        $placeholders = [];

        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $schema = $operation['responses']['200']['content']['application/json']['schema'] ?? null;
                if ($schema === ['type' => 'object']) {
                    $placeholders[] = sprintf('%s %s', strtoupper((string) $method), $path);
                }
            }
        }

        self::assertSame([], $placeholders, 'docs/openapi.json has operations left on the placeholder {"type": "object"} schema');
    }
}
