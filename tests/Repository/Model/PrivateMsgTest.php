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
 */

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Repository\ImageRepositoryInterface;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PrivateMsgTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private ImageRepositoryInterface&MockObject $imageRepository;
    private PrivateMessageRepositoryInterface&MockObject $privateMessageRepository;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testBuildCacheSkipsAnEmptyList(): void
    {
        $this->privateMessageRepository->expects(static::never())
            ->method('getRowsByIds');

        self::assertFalse(PrivateMsg::build_cache([]));
    }

    public function testBuildCacheWarmsTheMessagesAndTheirSenderRecipientUsers(): void
    {
        AmpConfig::set('memory_cache', true, true);

        $this->privateMessageRepository->expects(static::once())
            ->method('getRowsByIds')
            ->with([666])
            ->willReturn([['id' => 666, 'from_user' => 1, 'to_user' => 2]]);

        $this->userRepository->expects(static::once())
            ->method('getRowsByIds')
            ->with([1, 2])
            ->willReturn([]);

        $this->imageRepository->method('getRowsByObjectIds')
            ->willReturn([]);

        self::assertTrue(PrivateMsg::build_cache([666]));
    }

    protected function setUp(): void
    {
        $this->privateMessageRepository = $this->createMock(PrivateMessageRepositoryInterface::class);
        $this->userRepository           = $this->createMock(UserRepositoryInterface::class);
        $this->imageRepository          = $this->createMock(ImageRepositoryInterface::class);
        $this->dic                      = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->willReturnMap([
                [PrivateMessageRepositoryInterface::class, $this->privateMessageRepository],
                [UserRepositoryInterface::class, $this->userRepository],
                [ImageRepositoryInterface::class, $this->imageRepository],
            ]);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
