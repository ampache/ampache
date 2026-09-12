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

use FilesystemIterator;
use PDO;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use SplFileInfo;

/**
 * A server left with `autocommit = 0` puts every connection in one transaction that Ampache never commits, so
 * a cron run holds a read view from its first SELECT to its last write. Nothing fails loudly until MariaDB
 * 11.6.2, where snapshot isolation turns the next concurrent play into `1020 Record has changed since last read`.
 *
 * These are source guards: the state belongs to a live connection, which the suite has none of.
 */
class DbaConnectionTest extends TestCase
{
    /**
     * The clean code is the string '00000', which is truthy, so a handle that has run anything at all would be
     * thrown away as broken. Forcing autocommit at connection time is what makes that reachable.
     */
    public function testACleanErrorCodeIsNotAFailure(): void
    {
        $clean = $this->createMock(PDO::class);
        $clean->method('errorCode')->willReturn(PDO::ERR_NONE);

        $broken = $this->createMock(PDO::class);
        $broken->method('errorCode')->willReturn('HY000');

        $hasError = new ReflectionMethod(Dba::class, '_has_error');

        self::assertFalse($hasError->invoke(null, $clean), 'a connection that ran a statement cleanly is usable');
        self::assertTrue($hasError->invoke(null, $broken));
    }

    public function testEveryConnectionIsPutBackIntoAutocommit(): void
    {
        self::assertStringContainsString(
            "\$dbh->exec('SET SESSION autocommit = 1');",
            (string) file_get_contents(__DIR__ . '/../../../src/Module/System/Dba.php'),
            'Dba no longer forces autocommit on, so a server configured without it breaks the cron'
        );
    }

    public function testNothingElseOpensItsOwnConnection(): void
    {
        $sourcePath = realpath(__DIR__ . '/../../../src');

        self::assertIsString($sourcePath);

        $offenders = [];
        $iterator  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = (string) $file->getRealPath();
            if (
                $file->getExtension() === 'php'
                && $file->getFilename() !== 'Dba.php'
                && str_contains((string) file_get_contents($path), 'new PDO(')
            ) {
                $offenders[] = str_replace($sourcePath, '', $path);
            }
        }

        self::assertSame([], $offenders, 'a connection built outside Dba never gets the autocommit statement');
    }

    public function testTheTwoTransactionsThatDoCommitAreStillTheOnlyOnes(): void
    {
        $sourcePath = realpath(__DIR__ . '/../../../src');

        self::assertIsString($sourcePath);

        $callers  = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = (string) $file->getRealPath();
            if ($file->getExtension() === 'php' && str_contains((string) file_get_contents($path), '->beginTransaction()')) {
                $callers[] = str_replace($sourcePath, '', $path);
            }
        }

        self::assertSame(['/Module/Statistics/Stats.php'], $callers, 'a new transaction must commit, or autocommit is a lie');
    }
}
