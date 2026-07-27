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
 * Pins the OpenSubsonic surface to the spec snapshot it was built against.
 *
 * `docs/openapi-opensubsonic.json` is upstream's build artefact, copied in verbatim and never hand-edited. Upstream
 * ships changes to it continuously, so this records what the committed copy contained on the compliance date in
 * `docs/API-subsonic.md`: if a refreshed copy adds an endpoint or drops one, these counts move and the failure is
 * the prompt to re-audit rather than a silent drift.
 *
 * Refresh procedure is documented in resources/scripts/api-docs/README.md.
 */
class OpenSubsonicSpecVersionTest extends TestCase
{
    /**
     * The advertised extensions the spec document actually names, so a refreshed build that renames or drops one
     * fails here. The other four we claim — `indexBasedQueue`, `sonicSimilarity`, `transcodeOffset` and the
     * endpoint-less `formPost` — are real extensions with their own documentation pages but are never named in the
     * schema text, which is one of the inconsistencies upstream flags as work in progress. They were confirmed
     * against their individual pages under /docs/extensions/ instead and cannot be checked from the spec file.
     *
     * @var string[]
     */
    private const array SPEC_NAMED_EXTENSIONS = [
        'apiKeyAuthentication',
        'getPodcastEpisode',
        'playbackReport',
        'songLyrics',
        'topSongsByArtistId',
        'transcoding',
    ];

    private const int SPEC_PATH_COUNT = 87;
    /**
     * The upstream build the implementation was last audited against — keep in step with the compliance date
     * recorded in docs/API-subsonic.md.
     */
    private const string SPEC_SHA256 = 'cb54c03c33835d132c555863e9771e30dfaa2930312853ca27dfece2ed46bfb6';

    /**
     * @return array<string, mixed>
     */
    private static function spec(): array
    {
        return (array) json_decode((string) file_get_contents(self::specPath()), true);
    }

    private static function specPath(): string
    {
        return __DIR__ . '/../../../docs/openapi-opensubsonic.json';
    }

    /**
     * An advertised extension that no longer exists upstream is a lie to every client that checks.
     */
    public function testAdvertisedExtensionsAreNamedInTheSpec(): void
    {
        $spec = (string) file_get_contents(self::specPath());

        foreach (self::SPEC_NAMED_EXTENSIONS as $extension) {
            // str_contains rather than assertStringContainsString: a failure would otherwise dump 450KB of spec.
            self::assertTrue(
                str_contains($spec, $extension),
                sprintf('extension %s is advertised but no longer named in the spec', $extension)
            );
        }
    }

    public function testCommittedSpecIsTheAuditedBuild(): void
    {
        self::assertSame(
            self::SPEC_SHA256,
            hash_file('sha256', self::specPath()),
            'docs/openapi-opensubsonic.json changed. Re-audit the implementation against the new build, then update '
            . 'SPEC_SHA256 here and the compliance date in docs/API-subsonic.md.'
        );
    }

    /**
     * Every endpoint the spec documents must resolve to a handler, so a refreshed spec that adds one fails here
     * instead of quietly going unserved.
     */
    public function testEveryDocumentedEndpointHasAHandler(): void
    {
        $handlers = array_map('strtolower', get_class_methods(OpenSubsonic_Api::class));

        $missing = [];
        foreach (array_keys(self::spec()['paths']) as $path) {
            $action = strtolower(str_replace(['/rest/', '.view'], '', (string) $path));
            // hls.m3u8 is served by hls(); the suffix only exists so clients see a playlist file name.
            $action = ($action === 'hls.m3u8') ? 'hls' : $action;

            if (!in_array($action, $handlers, true)) {
                $missing[] = $action;
            }
        }

        self::assertSame([], $missing, 'OpenSubsonic endpoints with no handler: ' . implode(', ', $missing));
    }

    public function testSpecStillDescribesTheSameEndpointCount(): void
    {
        self::assertCount(self::SPEC_PATH_COUNT, self::spec()['paths']);
    }
}
