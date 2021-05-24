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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Channel;

use Ampache\MockeryTestCase;
use Ampache\Repository\ChannelRepositoryInterface;
use Mockery\MockInterface;

class ChannelCreatorTest extends MockeryTestCase
{
    private MockInterface $channelRepository;

    private ChannelCreator $subject;

    public function setUp(): void
    {
        $this->channelRepository = $this->mock(ChannelRepositoryInterface::class);

        $this->subject = new ChannelCreator(
            $this->channelRepository
        );
    }

    public function testCreateFailsIfNameIsEmpty(): void
    {
        $name          = '';
        $description   = 'some-description';
        $url           = 'some-url';
        $objectType    = 'some-type';
        $objectId      = 66;
        $interface     = 'some-interface';
        $port          = 42;
        $adminPassword = 'some-password';
        $isPrivate     = 1;
        $maxListeners  = 21;
        $random        = 1;
        $loop          = 1;
        $streamType    = 'some-stream-type';
        $bitrate       = 33;

        $this->assertFalse(
            $this->subject->create(
                $name,
                $description,
                $url,
                $objectType,
                $objectId,
                $interface,
                $port,
                $adminPassword,
                $isPrivate,
                $maxListeners,
                $random,
                $loop,
                $streamType,
                $bitrate
            )
        );
    }

    public function testCreateCreates(): void
    {
        $name          = 'some-name';
        $description   = 'some-description';
        $url           = 'some-url';
        $objectType    = 'some-type';
        $objectId      = 66;
        $interface     = 'some-interface';
        $port          = 42;
        $adminPassword = 'some-password';
        $isPrivate     = 1;
        $maxListeners  = 21;
        $random        = 1;
        $loop          = 1;
        $streamType    = 'some-stream-type';
        $bitrate       = 33;

        $this->channelRepository->shouldReceive('create')
            ->with(
                $name,
                $description,
                $url,
                $objectType,
                $objectId,
                $interface,
                $port,
                $adminPassword,
                $isPrivate,
                $maxListeners,
                $random,
                $loop,
                $streamType,
                $bitrate
            )
            ->once();

        $this->assertTrue(
            $this->subject->create(
                $name,
                $description,
                $url,
                $objectType,
                $objectId,
                $interface,
                $port,
                $adminPassword,
                $isPrivate,
                $maxListeners,
                $random,
                $loop,
                $streamType,
                $bitrate
            )
        );
    }
}
