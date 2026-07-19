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

use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Validates the committed Subsonic/OpenSubsonic response fixtures against the published specifications.
 *
 * The pure Subsonic (legacy) XML corpus is validated against the official 1.16.1 XSD, and the OpenSubsonic
 * JSON corpus against the OpenSubsonic OpenAPI schemas. The other two combinations have no authoritative
 * machine-readable schema: OpenSubsonic deliberately extends the envelope beyond what the 1.16.1 XSD allows,
 * and Subsonic never published a JSON schema.
 *
 * Fixtures are regenerated with resources/scripts/api-docs/capture_subsonic_fixtures.php.
 */
class SubsonicSpecConformanceTest extends TestCase
{
    /**
     * Endpoints whose fixtures cannot currently validate, with the reason. These are asserted to still fail so
     * the list cannot silently rot; remove an entry once the underlying issue is resolved.
     *
     * @var array<string, string>
     */
    private const array KNOWN_DEVIATIONS = [
        // The OpenSubsonic `User` schema omits `email`, which the Subsonic XSD does allow.
        'json:getUser' => 'OpenSubsonic User schema does not document `email`',
        'json:getUsers' => 'OpenSubsonic User schema does not document `email`',
        // The spec marks `playQueue` required, but `PlayQueue` itself requires `username`/`changed`/`changedBy`,
        // which have no meaningful value with nothing queued, so the key is omitted instead.
        'json:getPlayQueue' => 'no play queue is saved, and an empty PlayQueue cannot satisfy its own required fields',
    ];

    private static string $fixtureRoot = __DIR__ . '/../../Fixtures/Api';

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function openSubsonicJsonProvider(): array
    {
        return self::collect('opensubsonic', 'json');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function subsonicXmlProvider(): array
    {
        return self::collect('subsonic', 'xml');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private static function collect(string $corpus, string $extension): array
    {
        $files = glob(sprintf('%s/%s/*.%s', self::$fixtureRoot, $corpus, $extension)) ?: [];

        $cases = [];
        foreach ($files as $file) {
            $action         = basename($file, '.' . $extension);
            $cases[$action] = [$action, $file];
        }

        return $cases;
    }

    private static function specPath(string $file): string
    {
        return __DIR__ . '/../../../docs/' . $file;
    }

    /**
     * Every OpenSubsonic JSON response must satisfy the schema the spec declares for that endpoint.
     */
    #[DataProvider('openSubsonicJsonProvider')]
    public function testOpenSubsonicJsonMatchesOpenApiSchema(string $action, string $path): void
    {
        $spec = json_decode((string) file_get_contents(self::specPath('openapi-opensubsonic.json')), true);
        $body = (string) file_get_contents($path);

        self::assertIsArray(json_decode($body, true), sprintf('%s is not valid JSON', basename($path)));

        $schemaName = ucfirst($action) . 'Response';
        if (!isset($spec['components']['schemas'][$schemaName])) {
            self::markTestSkipped(sprintf('the OpenSubsonic spec declares no schema for %s', $action));
        }

        $errors = (new OpenApiResponseValidator($spec))->validate($body, $schemaName);
        $this->assertConformance('json:' . $action, $errors);
    }

    /**
     * Every pure Subsonic XML response must validate against the official 1.16.1 XSD.
     */
    #[DataProvider('subsonicXmlProvider')]
    public function testSubsonicXmlValidatesAgainstSchema(string $action, string $path): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument();
        self::assertTrue($document->loadXML((string) file_get_contents($path)), sprintf('%s is not well-formed XML', basename($path)));

        $errors = [];
        if (!$document->schemaValidate(self::specPath('subsonic-rest-api-1.16.1.xsd.xml'))) {
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message);
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertConformance('xml:' . $action, array_values(array_unique($errors)));
    }

    /**
     * Assert a fixture conforms, honouring the documented deviation list in both directions.
     *
     * @param string[] $errors
     */
    private function assertConformance(string $key, array $errors): void
    {
        $reason = self::KNOWN_DEVIATIONS[$key] ?? null;
        if ($reason !== null) {
            self::assertNotSame(
                [],
                $errors,
                sprintf('%s is listed as a known deviation (%s) but now conforms - remove it from KNOWN_DEVIATIONS', $key, $reason)
            );

            return;
        }

        self::assertSame([], $errors, sprintf("%s does not conform to the specification:\n  - %s", $key, implode("\n  - ", $errors)));
    }
}
