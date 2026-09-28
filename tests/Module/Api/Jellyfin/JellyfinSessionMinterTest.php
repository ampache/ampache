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
use ReflectionClassConstant;

class JellyfinSessionMinterTest extends TestCase
{
    private const int ONE_YEAR = 365 * 24 * 60 * 60;

    /**
     * A perpetual session carries expiry zero on purpose, and that is what keeps it revocable.
     */
    public function testAPerpetualSessionIsLeftAlone(): void
    {
        self::assertStringContainsString(
            '`expire` != 0',
            (string) file_get_contents(__DIR__ . '/../../../../src/Module/Api/Jellyfin/JellyfinSessionMinter.php'),
            'Minting overwrites a perpetual session, which takes it out of reach of the admin tool that clears them'
        );
    }

    /**
     * Nothing collects a session dated years out: the expiry sweep looks for one already past, and the admin's
     * "Clear Perpetual API Sessions" looks for expiry zero, so such a row answers to neither.
     */
    public function testASessionDoesNotOutliveEveryToolThatCouldClearIt(): void
    {
        $ttl = (new ReflectionClassConstant(JellyfinSessionMinter::class, 'SESSION_TTL_SECONDS'))->getValue();

        self::assertLessThan(self::ONE_YEAR, $ttl);
    }
}
