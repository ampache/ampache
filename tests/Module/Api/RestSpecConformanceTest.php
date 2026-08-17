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
 * Validates the committed Ampache REST response fixtures against docs/openapi.json.
 *
 * Fixtures are regenerated with resources/scripts/api-docs/capture_rest_fixtures.php. The fixture file name
 * encodes the documented path, e.g. `albums.album_id.songs.json` maps to `/albums/{album_id}/songs`.
 */
class RestSpecConformanceTest extends TestCase
{
    /**
     * Paths whose fixtures cannot currently validate, with the reason. These are asserted to still fail so the
     * list cannot silently rot; remove an entry once the underlying issue is resolved.
     *
     * @var array<string, string>
     */
    private const array KNOWN_DEVIATIONS = [];

    private static string $fixtureRoot = __DIR__ . '/../../Fixtures/Api/rest';

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function restFixtureProvider(): array
    {
        $files = glob(self::$fixtureRoot . '/*.json') ?: [];

        $cases = [];
        foreach ($files as $file) {
            $path         = self::toSpecPath(basename($file, '.json'));
            $cases[$path] = [$path, $file];
        }

        return $cases;
    }

    /**
     * Turn a fixture basename back into the documented path, e.g. `albums.album_id.songs` -> `/albums/{album_id}/songs`.
     */
    private static function toSpecPath(string $name): string
    {
        $segments = array_map(
            static fn(string $segment): string => str_ends_with($segment, '_id') ? '{' . $segment . '}' : $segment,
            explode('.', $name)
        );

        return '/' . implode('/', $segments);
    }

    /**
     * Every documented REST response must satisfy the schema declared for its path.
     */
    #[DataProvider('restFixtureProvider')]
    public function testRestResponseMatchesOpenApiSchema(string $path, string $file): void
    {
        $spec = json_decode((string) file_get_contents(__DIR__ . '/../../../docs/openapi.json'), true);
        $body = (string) file_get_contents($file);

        self::assertIsArray(json_decode($body, true), sprintf('%s is not valid JSON', basename($file)));

        $schema = $spec['paths'][$path]['get']['responses'][200]['content']['application/json']['schema'] ?? null;
        self::assertIsArray($schema, sprintf('docs/openapi.json declares no 200 JSON schema for GET %s', $path));

        $reference = $schema['$ref'] ?? null;
        if (!is_string($reference)) {
            self::markTestSkipped(sprintf('GET %s uses an inline schema rather than a named component', $path));
        }

        $schemaName = basename($reference);
        $errors     = new OpenApiResponseValidator($spec)->validate($body, $schemaName);

        $reason = self::KNOWN_DEVIATIONS[$path] ?? null;
        if ($reason !== null) {
            self::assertNotSame(
                [],
                $errors,
                sprintf('%s is listed as a known deviation (%s) but now conforms - remove it from KNOWN_DEVIATIONS', $path, $reason)
            );

            return;
        }

        self::assertSame([], $errors, sprintf("GET %s does not conform to docs/openapi.json:\n  - %s", $path, implode("\n  - ", $errors)));
    }
}
