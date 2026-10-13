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

namespace Ampache\Config\Init;

use Ampache\Config\AmpConfig;
use Ampache\Config\Init\Exception\EnvironmentNotSuitableException;
use Ampache\MockeryTestCase;
use Ampache\Module\System\Update\UpdaterInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * `hasPendingUpdates()` needs a reachable database, which this unit suite has none of, so only the bypass and
 * an unreachable database are covered here; the pending-update branch has to be proven against a real instance.
 */
class InitializationHandlerDatabaseUpdateTest extends MockeryTestCase
{
    private string $originalHostname;
    private MockInterface|UpdaterInterface|null $updater;

    public function testAnUnreachableDatabaseIsReportedAsEnvironmentNotSuitable(): void
    {
        // an empty hostname makes Dba::_connect() return null without attempting any connection, so this
        // is deterministic regardless of what database (if any) the running environment can actually reach
        AmpConfig::set('database_hostname', '', true);

        $this->updater->shouldNotReceive('hasPendingUpdates');

        $this->expectException(EnvironmentNotSuitableException::class);

        new InitializationHandlerDatabaseUpdate($this->updater)->init();
    }

    #[RunInSeparateProcess]
    public function testOutdatedDatabaseOkSkipsTheCheckEntirely(): void
    {
        define('OUTDATED_DATABASE_OK', 1);

        $this->updater->shouldNotReceive('hasPendingUpdates');

        new InitializationHandlerDatabaseUpdate($this->updater)->init();
    }

    #[Override]
    protected function setUp(): void
    {
        $this->updater = $this->mock(UpdaterInterface::class);

        $this->originalHostname = (string) AmpConfig::get('database_hostname', '');
    }

    #[Override]
    protected function tearDown(): void
    {
        AmpConfig::set('database_hostname', $this->originalHostname, true);

        parent::tearDown();
    }
}
