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

namespace Ampache\Module\Channel;

use Ampache\MockeryTestCase;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Repository\ChannelRepositoryInterface;
use Ampache\Repository\Model\ChannelInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ChannelFactoryTest extends MockeryTestCase
{
    private MockInterface $logger;

    private MockInterface $catalogLoader;

    private MockInterface $channelRepository;

    private ChannelFactory $subject;

    public function setUp(): void
    {
        $this->logger            = $this->mock(LoggerInterface::class);
        $this->catalogLoader     = $this->mock(CatalogLoaderInterface::class);
        $this->channelRepository = $this->mock(ChannelRepositoryInterface::class);

        $this->subject = new ChannelFactory(
            $this->logger,
            $this->catalogLoader,
            $this->channelRepository
        );
    }

    public function testCreateChannelStreamerReturnsInstance(): void
    {
        $this->assertInstanceOf(
            ChannelStreamerInterface::class,
            $this->subject->createChannelStreamer(
                $this->mock(ChannelInterface::class)
            )
        );
    }

    public function testCreateChannelManagerReturnsInstance(): void
    {
        $this->assertInstanceOf(
            ChannelOperatorInterface::class,
            $this->subject->createChannelOperator(
                $this->mock(ChannelInterface::class)
            )
        );
    }
}
