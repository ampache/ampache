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
use Ampache\Repository\Model\library_item;
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

    private ShoutCreator $subject;

    protected function setUp(): void
    {
        $this->userActivityPoster = $this->createMock(UserActivityPosterInterface::class);
        $this->configContainer    = $this->createMock(ConfigContainerInterface::class);
        $this->shoutRepository    = $this->createMock(ShoutRepositoryInterface::class);

        $this->subject = new ShoutCreator(
            $this->userActivityPoster,
            $this->configContainer,
            $this->shoutRepository
        );
    }

    public function testCreateFailsToCreateItem(): void
    {
        $user    = $this->createMock(User::class);
        $libItem = $this->createMock(library_item::class);

        $objectType = 'some-type';
        $text       = '<div>some-text</div>';
        $isSticky   = false;
        $offset     = 666;

        $this->shoutRepository->expects(static::once())
            ->method('create')
            ->with(
                $user,
                self::callback(fn (DateTimeInterface $value): bool => true),
                strip_tags($text),
                $isSticky,
                $libItem,
                $objectType,
                $offset
            )
            ->willReturn(0);

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
