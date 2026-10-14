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

namespace Ampache\Module\Api\Subsonic;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * How `scrobble` reads the `submission` parameter, in both the Subsonic and OpenSubsonic APIs.
 *
 * `docs/openapi-opensubsonic.json` gives the parameter `"default": true`, so a client that never sends it is
 * reporting finished streams. Reading a missing parameter as a now playing notification silently stops every
 * one of its plays from reaching Last.fm or ListenBrainz.
 */
class ScrobbleSubmissionTest extends TestCase
{
    /**
     * @return list<array{0: class-string}>
     */
    public static function handlers(): array
    {
        return [
            [\Ampache\Module\Api\OpenSubsonic_Api::class],
            [\Ampache\Module\Api\Subsonic_Api::class],
        ];
    }

    /**
     * @param class-string $handler
     */
    #[DataProvider('handlers')]
    public function testAMissingParameterIsASubmission(string $handler): void
    {
        $this->assertTrue($this->isSubmission($handler, []));
    }

    /**
     * @param class-string $handler
     */
    #[DataProvider('handlers')]
    public function testANowPlayingNotificationSaysSoExplicitly(string $handler): void
    {
        foreach (['false', 'False', '0', ''] as $value) {
            $this->assertFalse(
                $this->isSubmission($handler, ['submission' => $value]),
                sprintf('`%s` is how a client announces a now playing notification', $value)
            );
        }
    }

    /**
     * @param class-string $handler
     */
    #[DataProvider('handlers')]
    public function testBothSpellingsOfTrueAreAccepted(string $handler): void
    {
        foreach (['true', 'True', 'TRUE', '1'] as $value) {
            $this->assertTrue(
                $this->isSubmission($handler, ['submission' => $value]),
                sprintf('`%s` is a submission', $value)
            );
        }
    }

    /**
     * @param class-string $handler
     * @param array<string, mixed> $input
     */
    private function isSubmission(string $handler, array $input): bool
    {
        return new ReflectionMethod($handler, 'isSubmission')->invoke(null, $input);
    }
}
