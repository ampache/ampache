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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;

class JellyfinSessionMinterTest extends TestCase
{
    private const int ONE_YEAR = 365 * 24 * 60 * 60;

    private DatabaseConnectionInterface&MockObject $databaseConnection;
    private JellyfinServerId $serverId;

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

    /**
     * `extend()` is the rolling keep-alive called on every authenticated request, so it must reuse the
     * same guarded UPDATE that mint() itself relies on, never a bare overwrite.
     */
    public function testExtendGuardsAgainstOverwritingAPerpetualOrLongerExpiry(): void
    {
        $this->databaseConnection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `session` SET `expire` = ? WHERE `id` = ? AND `expire` != 0 AND `expire` < ?',
                static::callback(static function (array $params): bool {
                    self::assertSame('some-token', $params[1]);
                    self::assertGreaterThan(time(), $params[0]);
                    self::assertSame($params[0], $params[2]);

                    return true;
                })
            );

        $this->subject()->extend('some-token');
    }

    protected function setUp(): void
    {
        $this->databaseConnection = $this->createMock(DatabaseConnectionInterface::class);
        $this->serverId           = new JellyfinServerId($this->createMock(UpdateInfoRepositoryInterface::class));
    }

    private function subject(): JellyfinSessionMinter
    {
        return new JellyfinSessionMinter($this->databaseConnection, $this->serverId);
    }
}
