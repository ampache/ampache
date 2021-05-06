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

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class LiveStreamRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private MockInterface $configContainer;

    private LiveStreamRepository $subject;

    public function setUp(): void
    {
        $this->database        = $this->mock(Connection::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new LiveStreamRepository(
            $this->database,
            $this->configContainer
        );
    }

    public function testGetAllReturnsListOfIds(): void
    {
        $result = $this->mock(Result::class);

        $liveStreamId = 666;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->once()
            ->andReturnTrue();

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `live_stream`.`id` FROM `live_stream` JOIN `catalog` ON `catalog`.`id` = `live_stream`.`catalog` WHERE `catalog`.`enabled` = \'1\' ',
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $liveStreamId, false);

        $this->assertSame(
            [$liveStreamId],
            $this->subject->getAll()
        );
    }

    public function testDeleteDeletes(): void
    {
        $liveStreamId = 666;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `live_stream` WHERE `id` = ?',
                [$liveStreamId]
            )
            ->once();

        $this->subject->delete($liveStreamId);
    }

    public function testGetDataByIdReturnsEmptyArrayIfNothingWasFound(): void
    {
        $liveStreamId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `live_stream` WHERE `id` = ?',
                [$liveStreamId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDataById($liveStreamId)
        );
    }

    public function testGetDataByIdReturnsData(): void
    {
        $liveStreamId = 666;
        $data         = ['some-result'];

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `live_stream` WHERE `id` = ?',
                [$liveStreamId]
            )
            ->once()
            ->andReturn($data);

        $this->assertSame(
            $data,
            $this->subject->getDataById($liveStreamId)
        );
    }

    public function testCreateReturnsIdOfTheCreatedDataset(): void
    {
        $name      = 'some-name';
        $siteUrl   = 'some-site-url';
        $url       = 'some-url';
        $catalogId = 666;
        $codec     = 'some-codec';
        $insertId  = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `live_stream` (`name`, `site_url`, `url`, `catalog`, `codec`) VALUES (?, ?, ?, ?, ?)',
                [$name, $siteUrl, $url, $catalogId, $codec]
            )
            ->once();
        $this->database->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $insertId);

        $this->assertSame(
            $insertId,
            $this->subject->create($name, $siteUrl, $url, $catalogId, $codec)
        );
    }

    public function testUpdateUpdates(): void
    {
        $name          = 'some-name';
        $siteUrl       = 'some-site-url';
        $url           = 'some-url';
        $codec         = 'some-codec';
        $liveStreamId  = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `live_stream` SET `name` = ?,`site_url` = ?,`url` = ?, codec = ? WHERE `id` = ?',
                [$name, $siteUrl, $url, $codec, $liveStreamId]
            )
            ->once();

        $this->subject->update(
            $name,
            $siteUrl,
            $url,
            $codec,
            $liveStreamId
        );
    }
}
