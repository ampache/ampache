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

namespace Ampache\Module\Application\Rss;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\Rss\RssFeedTypeFactoryInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\UserRepositoryInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowActionTest extends TestCase
{
    public static function offSettings(): Generator
    {
        yield 'use_rss' => [false, false];
        yield 'demo_mode' => [true, true];
    }

    #[DataProvider('offSettings')]
    public function testASilentlyEmptyFeedSaysSoInTheLog(bool $useRss, bool $demoMode): void
    {
        $configContainer = $this->createMock(ConfigContainerInterface::class);
        $configContainer->method('isFeatureEnabled')
            ->willReturnMap([
                [ConfigurationKeyEnum::USE_RSS, $useRss],
                [ConfigurationKeyEnum::DEMO_MODE, $demoMode],
            ]);

        $messages = [];
        $logger   = $this->createMock(LoggerInterface::class);
        $logger->method('notice')
            ->willReturnCallback(static function (string $message) use (&$messages): void {
                $messages[] = $message;
            });

        $subject = new ShowAction(
            $configContainer,
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(RssFeedTypeFactoryInterface::class),
            $this->createMock(LibraryItemLoaderInterface::class),
            $logger,
        );

        self::assertNull(
            $subject->run(
                $this->createMock(ServerRequestInterface::class),
                $this->createMock(GuiGatekeeperInterface::class)
            )
        );
        self::assertSame(['RSS feeds are off, check use_rss and demo_mode'], $messages);
    }
}
