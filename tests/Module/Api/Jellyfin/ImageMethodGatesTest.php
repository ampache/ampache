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
 * The handler writes bytes straight out, so it cannot be driven from a unit test; these assert the two
 * properties that keep an anonymous route from answering for things its caller may not see.
 */
class ImageMethodGatesTest extends TestCase
{
    private const string SUBJECT = __DIR__ . '/../../../../src/Module/Api/Jellyfin/Method/Image/ImageMethod.php';

    public function testAPlaylistsMembersAreNeverRead(): void
    {
        self::assertStringNotContainsString(
            'get_items()',
            (string) file_get_contents(self::SUBJECT),
            'Reading a playlist here hands its composition to anyone who can guess its id, since the route takes no token'
        );
    }

    public function testTheDeploymentsArtPrivacySettingIsHonoured(): void
    {
        self::assertStringContainsString(
            'Art::isPublic()',
            (string) file_get_contents(self::SUBJECT),
            'An install that turned public_images off still has its art served to anonymous callers here'
        );
    }
}
