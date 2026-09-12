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

namespace Ampache\Module\Database;

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Module\System\Dba;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A failing query used to throw a `QueryFailedException` with no message, so a stack trace named the throw
 * site and nothing about what actually failed. The statement and the driver error now travel with it.
 */
class DbaDatabaseConnectionTest extends TestCase
{
    private string $database;
    private string $hostname;

    public function testAFailedQueryCarriesTheDriverErrorAndTheStatement(): void
    {
        // no reachable database, so `Dba::query()` returns null without touching the recorded error, and the
        // fake driver message set below is what the exception must surface
        AmpConfig::set('database_hostname', '', true);
        AmpConfig::set('database_name', 'zz_no_such_db', true);

        $error = new ReflectionProperty(Dba::class, '_error');
        $error->setValue(null, "SQLSTATE[42S22]: Unknown column 'user_preference.name'");

        try {
            (new DbaDatabaseConnection())->query('DELETE FROM `user_preference` WHERE `name` = ?', ['x']);
            self::fail('a query with no database should have thrown');
        } catch (QueryFailedException $error) {
            self::assertStringContainsString("Unknown column 'user_preference.name'", $error->getMessage());
            self::assertStringContainsString('DELETE FROM `user_preference`', $error->getMessage());
        }
    }

    protected function setUp(): void
    {
        $this->hostname = (string) AmpConfig::get('database_hostname', '');
        $this->database = (string) AmpConfig::get('database_name', '');
    }

    protected function tearDown(): void
    {
        AmpConfig::set('database_hostname', $this->hostname, true);
        AmpConfig::set('database_name', $this->database, true);
    }
}
