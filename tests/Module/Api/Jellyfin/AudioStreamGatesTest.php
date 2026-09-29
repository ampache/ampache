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

namespace Ampache\Module\Api\Jellyfin;

use PHPUnit\Framework\TestCase;

/**
 * The handler writes bytes and calls static gates, so it cannot be driven from a unit test; these assert
 * the gates are still in the source, which is what the equivalent native path applies before serving.
 */
class AudioStreamGatesTest extends TestCase
{
    private const string SUBJECT = __DIR__ . '/../../../../src/Module/Api/Jellyfin/Method/Playback/AudioStreamMethod.php';

    public function testLegalizeModeIsHonouredBeforeServing(): void
    {
        self::assertStringContainsString(
            'Stream::check_lock_media(',
            (string) file_get_contents(self::SUBJECT),
            'Jellyfin serves a media that is already playing, which lock_songs exists to refuse'
        );
    }

    public function testTheStreamControllerIsAskedBeforeServing(): void
    {
        self::assertStringContainsString(
            'User::stream_control(',
            (string) file_get_contents(self::SUBJECT),
            'Jellyfin streams without consulting the quota plugins, so a capped user is not capped here'
        );
    }
}
