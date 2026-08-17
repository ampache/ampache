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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Locks the API6 contract described by docs/openapi-6.json.
 *
 * API6 is served by both Ampache7 and Ampache8, so its surface must not drift as Ampache8 evolves.
 * docs/openapi-6.json is a static, hand-maintained document; these tests assert the running code still
 * matches it. A failure means either the spec needs updating or an API6 change slipped in that would
 * break clients pointed at an Ampache7 server.
 *
 * Deliberately independent of any generator: the spec is read from disk and compared with
 * `Api6::METHOD_LIST` and the `Json6_Data` `@return` docblocks.
 */
class Api6SpecConformanceTest extends TestCase
{
    /**
     * Paths API8 serves that API6 must not document. `random` was briefly registered on API6 as well,
     * but Ampache7 never served it there, so it was made API8-only to keep the two servers agreeing.
     *
     * @var list<string>
     */
    private const array API8_ONLY_PATHS = [
        '/folder',
        '/folders',
        '/playlists/{playlist_id}/remove',
        '/random',
    ];

    /**
     * API3-6 always answer HTTP 200 and carry the error in the body; only API8 maps errors onto
     * status codes. Documenting any of these would make the spec untestable against a real server.
     *
     * @var list<string>
     */
    private const array FORBIDDEN_STATUS_CODES = ['400', '403', '404', '410', '500'];

    /**
     * Response schema -> the Json6_Data builder whose @return shape defines it.
     *
     * @var array<string, string>
     */
    private const array SCHEMA_BUILDERS = [
        'AlbumObject' => 'albums_array',
        'ArtistObject' => 'artists_array',
        'GenreObject' => 'genres_array',
        'LabelObject' => 'labels_array',
        'LiveStreamObject' => 'live_streams_array',
        'PlaylistObject' => 'playlists_array',
        'PodcastEpisodeObject' => 'podcast_episodes_array',
        'PodcastObject' => 'podcasts_array',
        'SongObject' => 'songs_array',
        'UserSummaryObject' => 'users_array',
        'VideoObject' => 'videos_array',
    ];

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function schemaBuilderProvider(): array
    {
        $cases = [];
        foreach (self::SCHEMA_BUILDERS as $schema => $builder) {
            $cases[$schema] = [$schema, $builder];
        }

        return $cases;
    }

    /**
     * The spec must not describe endpoints an Ampache7 API6 server does not serve.
     */
    public function testApi8OnlyPathsAreNotDocumented(): void
    {
        $spec = $this->spec();

        foreach (self::API8_ONLY_PATHS as $path) {
            self::assertArrayNotHasKey(
                $path,
                $spec['paths'],
                sprintf('%s is not part of the API6 contract shared by Ampache7 and Ampache8', $path)
            );
        }
    }

    /**
     * Every documented path must resolve to an action API6 actually serves.
     */
    public function testDocumentedPathsAreServedByApi6(): void
    {
        $spec     = $this->spec();
        $actions  = array_keys(Api6::METHOD_LIST);
        $unserved = [];

        foreach ($spec['x-rpc-mappings'] as $path => $mapping) {
            if (!array_key_exists((string) $path, $spec['paths'])) {
                continue;
            }

            if (preg_match('/action=([a-z_0-9]+)/', (string) $mapping, $matches) !== 1) {
                continue;
            }

            if (!in_array($matches[1], $actions, true)) {
                $unserved[] = sprintf('%s (action=%s)', $path, $matches[1]);
            }
        }

        self::assertSame([], $unserved, 'docs/openapi-6.json documents actions missing from Api6::METHOD_LIST');
    }

    /**
     * Every $ref must resolve, otherwise the spec cannot be used to validate a response.
     */
    public function testEverySchemaReferenceResolves(): void
    {
        $spec     = $this->spec();
        $declared = array_keys($spec['components']['schemas']);
        $dangling = [];

        array_walk_recursive(
            $spec,
            static function ($value, $key) use ($declared, &$dangling): void {
                if ($key === '$ref' && is_string($value) && str_starts_with($value, '#/components/schemas/')) {
                    $name = basename($value);
                    if (!in_array($name, $declared, true)) {
                        $dangling[$name] = $name;
                    }
                }
            }
        );

        self::assertSame([], array_values($dangling), 'docs/openapi-6.json references undefined schemas');
    }

    /**
     * API6 never maps an error onto an HTTP status code, so none may be documented.
     */
    public function testNoErrorStatusCodesAreDocumented(): void
    {
        $spec  = $this->spec();
        $found = [];

        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (!is_array($operation)) {
                    continue;
                }

                foreach (array_keys($operation['responses'] ?? []) as $code) {
                    if (in_array((string) $code, self::FORBIDDEN_STATUS_CODES, true)) {
                        $found[] = sprintf('%s %s -> %s', strtoupper((string) $method), $path, $code);
                    }
                }
            }
        }

        self::assertSame([], $found, 'API6 returns HTTP 200 with the error in the body; no error codes may be documented');
    }

    /**
     * The documented fields of each object must match what Json6_Data actually builds. This is the
     * guard against an API6 response quietly gaining or losing a field in Ampache8.
     */
    #[DataProvider('schemaBuilderProvider')]
    public function testSchemaFieldsMatchJson6DataDocblock(string $schema, string $builder): void
    {
        $spec = $this->spec();

        self::assertArrayHasKey($schema, $spec['components']['schemas'], sprintf('%s is not declared', $schema));

        $documented = array_keys($spec['components']['schemas'][$schema]['properties']);
        $actual     = $this->builderFields($builder);

        sort($documented);
        sort($actual);

        self::assertSame(
            $actual,
            $documented,
            sprintf('%s does not match the @return shape of Json6_Data::%s()', $schema, $builder)
        );
    }

    /**
     * The spec must stay pinned to a single API version so a generated client cannot pick another.
     */
    public function testServerIsPinnedToApiVersionSix(): void
    {
        $spec = $this->spec();

        self::assertSame(['6'], $spec['servers'][0]['variables']['apiVersion']['enum']);
        self::assertSame('6', $spec['servers'][0]['variables']['apiVersion']['default']);
    }

    /**
     * Top-level field names from a builder's `@return array<int, array{...}>` docblock.
     *
     * Depth-aware rather than indentation-aware, because the shapes are written both across many
     * lines and (for the smaller ones) on a single line.
     *
     * @return list<string>
     */
    private function builderFields(string $builder): array
    {
        $source = (string) file_get_contents(__DIR__ . '/../../../src/Module/Api/Json6_Data.php');

        $pattern = sprintf('#(/\*\*(?:(?!\*/).)*?\*/)\s*public (?:static )?function %s\(#s', preg_quote($builder, '#'));
        self::assertSame(1, preg_match($pattern, $source, $matches), sprintf('no docblock for %s()', $builder));

        // flatten the docblock to a single line, then isolate the item shape
        $text  = (string) preg_replace('#\s*\n\s*\*\s?#', ' ', $matches[1]);
        $start = strpos($text, 'array{');
        self::assertIsInt($start, sprintf('%s() has no @return array-shape', $builder));

        $depth  = 0;
        $fields = [];
        $token  = '';
        for ($i = $start + strlen('array{'), $length = strlen($text); $i < $length; $i++) {
            $char = $text[$i];
            if ($char === '{' || $char === '<') {
                $depth++;
            } elseif ($char === '}' || $char === '>') {
                if ($depth === 0) {
                    break; // closed the item shape
                }

                $depth--;
            } elseif ($depth === 0 && $char === ':' && $token !== '') {
                $fields[] = trim($token, " \t\"'?");
                $token    = '';
                continue;
            } elseif ($depth === 0 && $char === ',') {
                $token = '';
                continue;
            }

            if ($depth === 0) {
                $token .= $char;
            }
        }

        return array_values(array_unique(array_filter($fields)));
    }

    /**
     * @return array<string, mixed>
     */
    private function spec(): array
    {
        $spec = json_decode(
            (string) file_get_contents(__DIR__ . '/../../../docs/openapi-6.json'),
            true
        );

        self::assertIsArray($spec, 'docs/openapi-6.json is not valid JSON');

        return $spec;
    }
}
