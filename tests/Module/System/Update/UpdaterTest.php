<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
 */

namespace Ampache\Module\System\Update;

use Ampache\Module\System\Update\Exception\VersionNotUpdatableException;
use Ampache\Module\System\Update\Migration\MigrationInterface;
use Ampache\Module\System\Update\Migration\V6\Migration600049;
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class UpdaterTest extends TestCase
{
    private UpdateHelperInterface&MockObject $updateHelper;

    private UpdateInfoRepositoryInterface&MockObject $updateInfoRepository;

    private ContainerInterface&MockObject $dic;

    private UpdateRunnerInterface&MockObject $updateRunner;

    private Updater $subject;

    protected function setUp(): void
    {
        $this->updateHelper         = $this->createMock(UpdateHelperInterface::class);
        $this->updateInfoRepository = $this->createMock(UpdateInfoRepositoryInterface::class);
        $this->dic                  = $this->createMock(ContainerInterface::class);
        $this->updateRunner         = $this->createMock(UpdateRunnerInterface::class);

        $this->subject = new Updater(
            $this->updateHelper,
            $this->updateInfoRepository,
            $this->dic,
            $this->updateRunner
        );
    }

    public function testGetPendingUpdatesYieldPendingOnes(): void
    {
        $version          = 600049;
        $formattedVersion = 'some-formatted-version';

        $migration = $this->createMock(MigrationInterface::class);

        $this->updateInfoRepository->expects(static::once())
            ->method('getValueByKey')
            ->with(UpdateInfoEnum::DB_VERSION)
            ->willReturn('600048');

        $this->dic->expects(static::atLeastOnce())
            ->method('get')
            ->with(Migration600049::class)
            ->willReturn($migration);

        $this->updateHelper->expects(static::once())
            ->method('formatVersion')
            ->with('600049')
            ->willReturn($formattedVersion);

        $result = $this->subject->getPendingUpdates()->current();

        static::assertSame(
            [
                'versionFormatted' => $formattedVersion,
                'version' => $version,
                'migration' => $migration,
            ],
            $result
        );
    }

    public function testHasPendingUpdatesReturnsTrueIfSo(): void
    {
        $this->updateInfoRepository->expects(static::once())
            ->method('getValueByKey')
            ->with(UpdateInfoEnum::DB_VERSION)
            ->willReturn('600048');

        static::assertTrue(
            $this->subject->hasPendingUpdates()
        );
    }

    public function testUpdateThrowsIfCurrentVersionIsLowerThenTheMinimum(): void
    {
        $this->updateInfoRepository->expects(static::once())
            ->method('getValueByKey')
            ->with(UpdateInfoEnum::DB_VERSION)
            ->willReturn('350000');

        static::expectException(VersionNotUpdatableException::class);

        $this->subject->update();
    }

    public function testUpdatePerformsTheActualUpdate(): void
    {
        $this->updateInfoRepository->expects(static::once())
            ->method('getValueByKey')
            ->with(UpdateInfoEnum::DB_VERSION)
            ->willReturn('600000');

        $this->updateRunner->expects(static::once())
            ->method('run')
            ->with(
                static::isType('iterable'),
                null
            );

        $this->subject->update();
    }

    public function testCheckTablesYieldMissingTables(): void
    {
        $table = 'snafu';

        $this->updateRunner->expects(static::once())
            ->method('runTableCheck')
            ->with(
                static::isType('iterable'),
                true,
                600000
            )
            ->willReturn(new ArrayIterator([$table]));

        static::assertSame(
            $table,
            $this->subject->checkTables(true, 600000)->current()
        );
    }
}
