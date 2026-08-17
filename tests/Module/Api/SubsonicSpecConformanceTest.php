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

    /**
     * Endpoints the OpenSubsonic spec declares no response schema for, so there is nothing to validate against and
     * no value in generating a case. Drop an entry once the spec documents that endpoint.
     *
     * `ping` answers with the shared `EmptySubsonicResponse`, which every mutating endpoint also returns, so the
     * envelope is still covered by the rest of the corpus. `getTranscodeDecision` declares its schema inline rather
     * than as a `$ref`, which self::resolveSchemaName() cannot name for the validator.
     *
     * @var string[]
     */
    private const array UNSCHEMAED_JSON_ACTIONS = [
        'ping',
        'getTranscodeDecision',
    ];

    private static string $fixtureRoot = __DIR__ . '/../../Fixtures/Api';

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function openSubsonicJsonProvider(): array
    {
        return array_diff_key(
            self::collect('opensubsonic', 'json'),
            array_flip(self::UNSCHEMAED_JSON_ACTIONS)
        );
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

    /**
     * Every OpenSubsonic JSON response must satisfy the schema the spec declares for that endpoint.
     */
    #[DataProvider('openSubsonicJsonProvider')]
    public function testOpenSubsonicJsonMatchesOpenApiSchema(string $action, string $path): void
    {
        $spec = json_decode((string) file_get_contents($this->specPath('openapi-opensubsonic.json')), true);
        $body = (string) file_get_contents($path);

        self::assertIsArray(json_decode($body, true), sprintf('%s is not valid JSON', basename($path)));

        $schemaName = $this->resolveSchemaName($spec, $action);
        if ($schemaName === null) {
            self::markTestSkipped(sprintf('the OpenSubsonic spec declares no schema for %s', $action));
        }

        $errors = new OpenApiResponseValidator($spec)->validate($body, $schemaName);
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
        if (!$document->schemaValidate($this->specPath('subsonic-rest-api-1.16.1.xsd.xml'))) {
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

    /**
     * Find the response schema the spec actually declares for an endpoint.
     *
     * The name cannot be derived from the action: 38 of the 87 documented endpoints have no `<Action>Response`
     * schema at all — `tokenInfo` is `GetTokenInfoResponse`, `getAlbumInfo2` reuses the `getAlbumInfo` schema, and
     * every void or binary endpoint `$ref`s a shared base response. Reading the `$ref` off the path is what lets
     * those endpoints be covered instead of silently skipped.
     *
     * @param array<string, mixed> $spec
     */
    private function resolveSchemaName(array $spec, string $action): ?string
    {
        foreach ($spec['paths'] ?? [] as $path => $operations) {
            if (strcasecmp(str_replace(['/rest/', '.view'], '', (string) $path), $action) !== 0) {
                continue;
            }

            foreach (['get', 'post'] as $verb) {
                $response = $operations[$verb]['responses']['200'] ?? null;
                if (!is_array($response)) {
                    continue;
                }

                // Void and binary endpoints ref components/responses, so follow one hop before looking for the body.
                if (is_string($response['$ref'] ?? null)) {
                    $shared   = substr($response['$ref'], (int) strrpos($response['$ref'], '/') + 1);
                    $response = $spec['components']['responses'][$shared] ?? [];
                }

                $ref = $response['content']['application/json']['schema']['$ref'] ?? null;
                if (is_string($ref)) {
                    $name = substr($ref, (int) strrpos($ref, '/') + 1);

                    return isset($spec['components']['schemas'][$name]) ? $name : null;
                }
            }
        }

        // Fall back to the naming convention for anything the paths section does not pin down.
        $name = ucfirst($action) . 'Response';

        return isset($spec['components']['schemas'][$name]) ? $name : null;
    }

    private function specPath(string $file): string
    {
        return __DIR__ . '/../../../docs/' . $file;
    }
}
