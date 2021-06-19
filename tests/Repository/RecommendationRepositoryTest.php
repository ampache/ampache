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
use Doctrine\DBAL\Connection;
use Mockery\MockInterface;

class RecommendationRepositoryTest extends MockeryTestCase
{
    private MockInterface $connection;

    private RecommendationRepository $subject;

    public function setUp(): void
    {
        $this->connection = $this->mock(Connection::class);

        $this->subject = new RecommendationRepository(
            $this->connection
        );
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-type';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE IGNORE `recommendation` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [$newObjectId, $objectType, $oldObjectId]
            )
            ->once();

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }

    public function testMigratePerformsSpecialMigrationForArtists(): void
    {
        $objectType  = 'artist';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE IGNORE `recommendation_item` SET `recommendation_id` = ? WHERE `recommendation_id` = ?',
                [$newObjectId, $oldObjectId]
            )
            ->once();
        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE IGNORE `recommendation` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [$newObjectId, $objectType, $oldObjectId]
            )
            ->once();

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }

    public function testCollectGarbageDeletes(): void
    {
        $this->connection->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `recommendation` WHERE `last_update` < ?',
                [(time() - 604800)]
            )
            ->once();

        $this->subject->collectGarbage();
    }
}
