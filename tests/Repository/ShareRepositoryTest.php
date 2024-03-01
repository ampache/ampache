<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use DateTime;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShareRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private ConfigContainerInterface&MockObject $configContainer;

    private ShareRepository $subject;

    protected function setUp(): void
    {
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new ShareRepository(
            $this->connection,
            $this->configContainer
        );
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-type';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `share` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [$newObjectId, $objectType, $oldObjectId]
            );

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }

    public function testCollectGarbageCleansUp(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `share` WHERE (`expire_days` > 0 AND (`creation_date` + (`expire_days` * 86400)) < UNIX_TIMESTAMP()) OR (`max_counter` > 0 AND `counter` >= `max_counter`)',
            );

        $this->subject->collectGarbage();
    }

    public function testGetIdsByUserReturnsItemIdsForUser(): void
    {
        $user   = $this->createMock(User::class);
        $result = $this->createMock(PDOStatement::class);

        $userId  = 666;
        $shareId = 42;

        $user->expects(static::once())
            ->method('has_access')
            ->with(AccessLevelEnum::LEVEL_MANAGER)
            ->willReturn(false);
        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CATALOG_FILTER)
            ->willReturn(false);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `share` WHERE `user` = ?',
                [$userId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $shareId, false);

        static::assertSame(
            [$shareId],
            $this->subject->getIdsByUser($user)
        );
    }

    public function testGetIdsByUserReturnsItemIdsForManagingUser(): void
    {
        $user   = $this->createMock(User::class);
        $result = $this->createMock(PDOStatement::class);

        $userId  = 666;
        $shareId = 42;

        $user->expects(static::once())
            ->method('has_access')
            ->with(AccessLevelEnum::LEVEL_MANAGER)
            ->willReturn(true);
        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CATALOG_FILTER)
            ->willReturn(true);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `share` WHERE 1=1 AND  `share`.`object_id` IN (SELECT `share`.`object_id` FROM `share` LEFT JOIN `catalog_map` ON `share`.`object_type` = `catalog_map`.`object_type` AND `share`.`object_id` = `catalog_map`.`object_id` LEFT JOIN `catalog` ON `catalog_map`.`catalog_id` = `catalog`.`id` WHERE `catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ' . $userId . ' AND `catalog_filter_group_map`.`enabled`=1) GROUP BY `share`.`object_id`, `share`.`object_type`) ',
                []
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $shareId, false);

        static::assertSame(
            [$shareId],
            $this->subject->getIdsByUser($user)
        );
    }

    public function testDeleteDeletesItem(): void
    {
        $share = $this->createMock(Share::class);

        $shareId = 666;

        $share->expects(static::once())
            ->method('getId')
            ->willReturn($shareId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `share` WHERE `id` = ?',
                [$shareId]
            );

        $this->subject->delete($share);
    }

    public function testRegisterAccessRegisters(): void
    {
        $share = $this->createMock(Share::class);

        $date    = new DateTime();
        $shareId = 666;

        $share->expects(static::once())
            ->method('getId')
            ->willReturn($shareId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `share` SET `counter` = (`counter` + 1), lastvisit_date = ? WHERE `id` = ?',
                [$date->getTimestamp(), $shareId]
            );

        $this->subject->registerAccess($share, $date);
    }
}
