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

use Ampache\Repository\CatalogFilterRepositoryInterface;
use Ampache\Repository\PreferenceRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * `rebuild_all_preferences` repairs preferences assuming the final schema — `user_preference`.`name` first of
 * all, which a migration adds late. Run mid-upgrade, before that column exists, it used to crash the whole
 * `admin:updateDatabase`. It must stand down until the schema is complete, and it must not stand down otherwise.
 */
class PreferenceRebuildTest extends TestCase
{
    private CatalogFilterRepositoryInterface&MockObject $filterRepository;
    private PreferenceRepositoryInterface&MockObject $preferenceRepository;

    public function testRebuildDoesNothingWhileTheNameColumnIsMissing(): void
    {
        $this->preferenceRepository->method('hasUserPreferenceName')->willReturn(false);

        // none of the repairs, which all read or write `user_preference`.`name`, may be attempted
        $this->preferenceRepository->expects(self::never())->method('repairLanguagePreferences');
        $this->preferenceRepository->expects(self::never())->method('collectPreferenceGarbage');
        $this->preferenceRepository->expects(self::never())->method('getIdsMissingPreferences');
        $this->filterRepository->expects(self::never())->method('repairDefaultGroup');

        Preference::rebuild_all_preferences();
    }

    public function testRebuildRunsTheRepairsOnceTheSchemaIsComplete(): void
    {
        $this->preferenceRepository->method('hasUserPreferenceName')->willReturn(true);
        // let the per-user pass over nothing so the test stays about the guard
        $this->preferenceRepository->method('getIdsMissingPreferences')->willReturn([]);
        $this->preferenceRepository->method('getStoredPreferences')->willReturn([]);
        $this->preferenceRepository->method('getAllPreferences')->willReturn([]);
        $this->preferenceRepository->method('getSystemDefaultPreferences')->willReturn([]);

        // the guard let the maintenance through
        $this->preferenceRepository->expects(self::once())->method('repairLanguagePreferences');
        $this->preferenceRepository->expects(self::once())->method('collectPreferenceGarbage');

        Preference::rebuild_all_preferences();
    }

    protected function setUp(): void
    {
        $this->preferenceRepository = $this->createMock(PreferenceRepositoryInterface::class);
        $this->filterRepository     = $this->createMock(CatalogFilterRepositoryInterface::class);

        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            PreferenceRepositoryInterface::class => $this->preferenceRepository,
            CatalogFilterRepositoryInterface::class => $this->filterRepository,
        });

        // the model reaches its repositories through the `global $dic` bridge; phpunit.xml backs globals up
        $GLOBALS['dic'] = $dic;
    }
}
