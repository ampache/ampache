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

use Ampache\Repository\PreferenceRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PreferenceTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private PreferenceRepositoryInterface&MockObject $preferenceRepository;

    /**
     * `translate_db()` is the only mechanism that renames a preference's description on an install that
     * already has it; a name missing here never gets its description fixed and is never gathered for translation
     */
    public function testEveryDefaultPreferenceHasATranslatedDescription(): void
    {
        $captured = null;

        $this->preferenceRepository->expects(self::once())
            ->method('updateDescriptions')
            ->willReturnCallback(function (array $descriptions) use (&$captured): void {
                $captured = $descriptions;
            });

        Preference::translate_db();

        self::assertSame(
            [],
            array_values(array_diff(array_keys(Preference::DEFAULTS), array_keys((array) $captured))),
            'DEFAULTS entries missing from translate_db()'
        );
    }

    /**
     * Every row is bound to a seven column insert, so a short row means the preference is never written
     */
    public function testEveryDefaultRowCarriesEveryColumn(): void
    {
        foreach (Preference::DEFAULTS as $name => $row) {
            self::assertCount(6, $row, sprintf('%s does not carry all six columns', $name));
            self::assertIsString($row[0], sprintf('%s has a non-string value', $name));
            self::assertIsString($row[1], sprintf('%s has a non-string description', $name));
            self::assertIsInt($row[2], sprintf('%s has a non-integer level', $name));
        }
    }

    /**
     * A name in one list and not the other is the failure `set_defaults()` used to report at runtime as
     * "missing preference insert code", by which point the preference simply does not exist.
     */
    public function testEverySystemPreferenceHasADefaultRow(): void
    {
        self::assertSame(
            [],
            array_values(array_diff(Preference::SYSTEM_LIST, array_keys(Preference::DEFAULTS))),
            'SYSTEM_LIST entries with no row in DEFAULTS'
        );

        self::assertSame(
            [],
            array_values(array_diff(array_keys(Preference::DEFAULTS), Preference::SYSTEM_LIST)),
            'DEFAULTS rows that SYSTEM_LIST never asks for'
        );
    }

    protected function setUp(): void
    {
        $this->preferenceRepository = $this->createMock(PreferenceRepositoryInterface::class);
        $this->dic                  = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(PreferenceRepositoryInterface::class)
            ->willReturn($this->preferenceRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
