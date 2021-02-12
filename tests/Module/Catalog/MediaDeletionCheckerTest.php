<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\library_item;
use Mockery\MockInterface;

class MediaDeletionCheckerTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var PrivilegeCheckerInterface|MockInterface|null */
    private MockInterface $privilegeChecker;

    private ?MediaDeletionChecker $subject;

    public function setUp(): void
    {
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);
        $this->privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);

        $this->subject = new MediaDeletionChecker(
            $this->configContainer,
            $this->privilegeChecker
        );
    }

    public function testMayDeleteReturnsFalseIfNotAllowed(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DELETE_FROM_DISK)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->subject->mayDelete(
                $this->mock(library_item::class),
                666
            )
        );
    }

    public function testMayDeleteReturnsTrueIfAccessLevelIsSufficent(): void
    {
        $libraryItem = $this->mock(library_item::class);

        $userId = 666;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DELETE_FROM_DISK)
            ->once()
            ->andReturnTrue();

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->mayDelete(
                $libraryItem,
                $userId
            )
        );
    }

    public function testMayDeleteReturnsTrueIfUserIsOwnerAndDeletionByOwnerIsEnabled(): void
    {
        $libraryItem = $this->mock(library_item::class);

        $userId = 666;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DELETE_FROM_DISK)
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_REMOVE)
            ->once()
            ->andReturnTrue();

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnFalse();

        $libraryItem->shouldReceive('get_user_owner')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->mayDelete(
                $libraryItem,
                $userId
            )
        );
    }
}
