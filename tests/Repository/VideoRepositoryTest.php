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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Video;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class VideoRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private MockInterface $configContainer;

    private VideoRepository $subject;

    public function setUp(): void
    {
        $this->database        = $this->mock(Connection::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new VideoRepository(
            $this->database,
            $this->configContainer
        );
    }

    public function testGetRandomReturnsData(): void
    {
        $limit   = 666;
        $videoId = 42;

        $result = $this->mock(Result::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->once()
            ->andReturnTrue();

        $this->database->shouldReceive('executeQuery')
            ->with(
                sprintf(
                    'SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` WHERE `video`.`enabled` = \'1\' AND `catalog`.`enabled` = \'1\' ORDER BY RAND() LIMIT %d',
                    $limit
                )
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $videoId, false);

        $this->assertSame(
            [$videoId],
            $this->subject->getRandom($limit)
        );
    }

    public function testGetItemCountReturnsValue(): void
    {
        $type   = Video::class;
        $result = 666;

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT COUNT(*) as count from `video`'
            )
            ->once()
            ->andReturn((string) $result);

        $this->assertSame(
            $result,
            $this->subject->getItemCount($type)
        );
    }
}
