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
 * The handler resolves its song through static model calls, so it cannot be driven from a unit test; these
 * assert the two properties that keep a per-user route out of everyone else's state.
 */
class PlayedMethodGatesTest extends TestCase
{
    private const string SUBJECT = __DIR__ . '/../../../../src/Module/Api/Jellyfin/Method/UserData/PlayedMethod.php';

    public function testThePlayIsRecordedAgainstTheCaller(): void
    {
        self::assertStringContainsString(
            'set_played(',
            (string) file_get_contents(self::SUBJECT),
            'A play reported here leaves no trace in the caller\'s own history'
        );
    }

    /**
     * `Song::update_played()` writes the shared `song`.`played` column for every user at once, and the
     * delete form of this route used it to clear songs other people had genuinely played.
     */
    public function testTheSharedPlayedColumnIsNeverWrittenDirectly(): void
    {
        self::assertStringNotContainsString(
            'Song::update_played(',
            (string) file_get_contents(self::SUBJECT),
            'A per-user route is writing the shared played flag, which every other user reads'
        );
    }
}
