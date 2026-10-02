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

namespace Ampache\Module\System;

use PHPUnit\Framework\TestCase;

/**
 * Every connection is put into READ COMMITTED. Under REPEATABLE READ, MariaDB's innodb_snapshot_isolation
 * (on by default since 11.6.2) aborts a write to a row committed since the read view with ER_CHECKREAD (1020);
 * READ COMMITTED never hits it. The state belongs to a live connection, so this is a source guard.
 */
class DbaIsolationTest extends TestCase
{
    public function testEveryConnectionIsPutIntoReadCommitted(): void
    {
        self::assertStringContainsString(
            "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED",
            (string) file_get_contents(__DIR__ . '/../../../src/Module/System/Dba.php'),
            'Dba no longer forces READ COMMITTED, so a MariaDB with snapshot isolation can raise 1020 mid-cron'
        );
    }
}
