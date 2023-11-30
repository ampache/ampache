<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
 */

namespace Ampache\Module\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoutCreatorTest extends TestCase
{
    private UserActivityPosterInterface&MockObject $userActivityPoster;

    private ConfigContainerInterface&MockObject $configContainer;

    private ShoutRepositoryInterface&MockObject $shoutRepository;

    private UtilityFactoryInterface&MockObject $utilityFactory;

    private ModelFactoryInterface&MockObject $modelFactory;

    private ShoutCreator $subject;

    protected function setUp(): void
    {
        $this->userActivityPoster = $this->createMock(UserActivityPosterInterface::class);
        $this->configContainer    = $this->createMock(ConfigContainerInterface::class);
        $this->shoutRepository    = $this->createMock(ShoutRepositoryInterface::class);
        $this->utilityFactory     = $this->createMock(UtilityFactoryInterface::class);
        $this->modelFactory       = $this->createMock(ModelFactoryInterface::class);

        $this->subject = new ShoutCreator(
            $this->userActivityPoster,
            $this->configContainer,
            $this->shoutRepository,
            $this->utilityFactory,
            $this->modelFactory
        );
    }

    public function testCreateFailsToCreateItem(): void
    {
        $user    = $this->createMock(User::class);
        $libItem = $this->createMock(library_item::class);
        $shout   = $this->createMock(Shoutbox::class);

        $objectType = 'some-type';
        $text       = '<div>some-text</div>';
        $isSticky   = false;
        $offset     = 666;
        $objectId   = 42;
        $userId     = 33;

        $libItem->expects(static::once())
            ->method('getId')
            ->willReturn($objectId);

        $this->shoutRepository->expects(static::once())
            ->method('prototype')
            ->willReturn($shout);

        $shout->expects(static::once())
            ->method('setDate')
            ->with(self::callback(fn (DateTimeInterface $value): bool => true))
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('setUser')
            ->with($user)
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('setText')
            ->with($text)
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('setSticky')
            ->with($isSticky)
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('setObjectId')
            ->with($objectId)
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('setObjectType')
            ->with($objectType)
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('setOffset')
            ->with($offset)
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('save');

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->userActivityPoster->expects(static::once())
            ->method('post')
            ->with(
                $userId,
                'shout',
                $objectType,
                $objectId,
                self::callback(function (int $value): bool {
                    return $value <= time();
                })
            );

        $this->subject->create(
            $user,
            $libItem,
            $objectType,
            $text,
            $isSticky,
            $offset
        );
    }
}
