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

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ChannelInterface;
use Doctrine\DBAL\Connection;
use Mockery\MockInterface;

class ChannelRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private ChannelRepository $subject;

    public function setUp(): void
    {
        $this->database = $this->mock(Connection::class);

        $this->subject = new ChannelRepository(
            $this->database
        );
    }

    public function testGetDataByIdReturnsEmptyArray(): void
    {
        $channelId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `channel` WHERE `id` = ?',
                [$channelId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDataById($channelId)
        );
    }

    public function testGetDataByIdReturnsData(): void
    {
        $channelId = 666;
        $result    = ['some' => 'result'];

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `channel` WHERE `id` = ?',
                [$channelId]
            )
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->getDataById($channelId)
        );
    }

    public function testGetNextPortReturnsDefault(): void
    {
        $defaultPort = 666;

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT MAX(`port`) AS `max_port` FROM `channel`',
            )
            ->once()
            ->andReturnNull();

        $this->assertSame(
            $defaultPort,
            $this->subject->getNextPort($defaultPort)
        );
    }

    public function testGetNextPortReturnsValue(): void
    {
        $port = 666;

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT MAX(`port`) AS `max_port` FROM `channel`',
            )
            ->once()
            ->andReturn($port);

        $this->assertSame(
            $port + 1,
            $this->subject->getNextPort(42)
        );
    }

    public function testDeleteDeletes(): void
    {
        $channel = $this->mock(ChannelInterface::class);

        $channelId = 666;

        $channel->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($channelId);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `channel` WHERE `id` = ?',
                [$channelId]
            )
            ->once();

        $this->subject->delete($channel);
    }

    public function testUpdateListenersUpdates(): void
    {
        $channelId     = 666;
        $listeners     = 42;
        $peakListeners = 33;
        $connections   = 21;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `channel` SET `listeners` = ?, `peak_listeners` = ?, `connections` = ? WHERE `id` = ?',
                [$listeners, $peakListeners, $connections, $channelId]
            )
            ->once();

        $this->subject->updateListeners(
            $channelId,
            $listeners,
            $peakListeners,
            $connections
        );
    }

    public function testUpdateStartUpdates(): void
    {
        $channelId = 666;
        $startDate = 42;
        $address   = 'some-address';
        $port      = 33;
        $pid       = 21;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `channel` SET `start_date` = ?, `interface` = ?, `port` = ?, `pid` = ?, `listeners` = 0 WHERE `id` = ?',
                [$startDate, $address, $port, $pid, $channelId]
            )
            ->once();

        $this->subject->updateStart(
            $channelId,
            $startDate,
            $address,
            $port,
            $pid
        );
    }

    public function testStopStops(): void
    {
        $channelId = 666;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `channel` SET `start_date` = 0, `listeners` = 0, `pid` = 0 WHERE `id` = ?',
                [$channelId]
            )
            ->once();

        $this->subject->stop($channelId);
    }

    public function testCreateCreates(): void
    {
        $name          = 'some-name';
        $description   = 'some-description';
        $url           = 'some-url';
        $objectType    = 'some-object-type';
        $objectId      = 666;
        $interface     = 'some-interface';
        $port          = 42;
        $adminPassword = 'some-admin-password';
        $isPrivate     = 1;
        $maxListeners  = 21;
        $random        = 1;
        $loop          = 1;
        $streamType    = 'some-stream-type';
        $bitrate       = 33;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `channel` (`name`, `description`, `url`, `object_type`, `object_id`, `interface`, `port`, `fixed_endpoint`, `admin_password`, `is_private`, `max_listeners`, `random`, `loop`, `stream_type`, `bitrate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $name,
                    $description,
                    $url,
                    $objectType,
                    $objectId,
                    $interface,
                    $port,
                    $interface !== '' && $port !== 0,
                    $adminPassword,
                    $isPrivate,
                    $maxListeners,
                    $random,
                    $loop,
                    $streamType,
                    $bitrate
                ]
            )
            ->once();

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
        );
    }

    public function testUpdateUpdates(): void
    {
        $channelId     = 1234;
        $name          = 'some-name';
        $description   = 'some-description';
        $url           = 'some-url';
        $objectId      = 666;
        $interface     = 'some-interface';
        $port          = 42;
        $adminPassword = 'some-admin-password';
        $isPrivate     = 1;
        $maxListeners  = 21;
        $random        = 1;
        $loop          = 1;
        $streamType    = 'some-stream-type';
        $bitrate       = 33;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `channel` SET `name` = ?, `description` = ?, `url` = ?, `interface` = ?, `port` = ?, `fixed_endpoint` = ?, `admin_password` = ?, `is_private` = ?, `max_listeners` = ?, `random` = ?, `loop` = ?, `stream_type` = ?, `bitrate` = ?, `object_id` = ? ' . "WHERE `id` = ?",
                [
                    $name,
                    $description,
                    $url,
                    $interface,
                    $port,
                    $interface !== '' && $port !== 0,
                    $adminPassword,
                    $isPrivate,
                    $maxListeners,
                    $random,
                    $loop,
                    $streamType,
                    $bitrate,
                    $objectId,
                    $channelId
                ]
            )
            ->once();

        $this->subject->update(
            $channelId,
            $name,
            $description,
            $url,
            $interface,
            $port,
            $adminPassword,
            $isPrivate,
            $maxListeners,
            $random,
            $loop,
            $streamType,
            $bitrate,
            $objectId
        );
    }
}
