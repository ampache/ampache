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

namespace Ampache\Module\Catalog;

use Ampache\MockeryTestCase;
use Ampache\Module\Art\ArtDuplicatorInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\RecommendationRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\StatsRepositoryInterface;
use Ampache\Repository\TagRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserflagRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class DataMigratorTest extends MockeryTestCase
{
    /** @var UserActivityRepositoryInterface|MockInterface */
    private MockInterface $userActivityRepository;

    /** @var LoggerInterface|MockInterface */
    private MockInterface $logger;

    /** @var ShoutRepositoryInterface|MockInterface */
    private MockInterface $shoutRepository;

    /** @var TagRepositoryInterface|MockInterface */
    private MockInterface $tagRepository;

    /** @var ShareRepositoryInterface|MockInterface */
    private MockInterface $shareRepository;

    /** @var RecommendationRepositoryInterface|MockInterface */
    private MockInterface $recommendationRepository;

    /** @var StatsRepositoryInterface|MockInterface */
    private MockInterface $statsRepository;

    /** @var UserflagRepositoryInterface|MockInterface */
    private MockInterface $userflagRepository;

    /** @var RatingRepositoryInterface|MockInterface */
    private MockInterface $ratingRepository;

    private MockInterface $catalogRepository;

    private MockInterface $artDuplicator;

    private MockInterface $labelRepository;

    private MockInterface $playlistRepository;

    private MockInterface $wantedRepository;

    private MockInterface $metadataRepository;

    private DataMigrator $subject;

    public function setUp(): void
    {
        $this->userActivityRepository   = $this->mock(UserActivityRepositoryInterface::class);
        $this->logger                   = $this->mock(LoggerInterface::class);
        $this->shoutRepository          = $this->mock(ShoutRepositoryInterface::class);
        $this->tagRepository            = $this->mock(TagRepositoryInterface::class);
        $this->shareRepository          = $this->mock(ShareRepositoryInterface::class);
        $this->recommendationRepository = $this->mock(RecommendationRepositoryInterface::class);
        $this->statsRepository          = $this->mock(StatsRepositoryInterface::class);
        $this->userflagRepository       = $this->mock(UserflagRepositoryInterface::class);
        $this->ratingRepository         = $this->mock(RatingRepositoryInterface::class);
        $this->artDuplicator            = $this->mock(ArtDuplicatorInterface::class);
        $this->catalogRepository        = $this->mock(CatalogRepositoryInterface::class);
        $this->labelRepository          = $this->mock(LabelRepositoryInterface::class);
        $this->playlistRepository       = $this->mock(PlaylistRepositoryInterface::class);
        $this->wantedRepository         = $this->mock(WantedRepositoryInterface::class);
        $this->metadataRepository       = $this->mock(MetadataRepositoryInterface::class);

        $this->subject = new DataMigrator(
            $this->userActivityRepository,
            $this->logger,
            $this->shoutRepository,
            $this->tagRepository,
            $this->shareRepository,
            $this->recommendationRepository,
            $this->statsRepository,
            $this->userflagRepository,
            $this->ratingRepository,
            $this->catalogRepository,
            $this->artDuplicator,
            $this->labelRepository,
            $this->playlistRepository,
            $this->wantedRepository,
            $this->metadataRepository
        );
    }

    public function testMigrateReturnsFalseIfIdsMatch(): void
    {
        $this->assertFalse(
            $this->subject->migrate('foobar', 666, 666)
        );
    }

    public function testMigrateMigrates(): void
    {
        $oldObjectId = 666;
        $newObjectId = 42;
        $objectType  = 'artist';

        $this->logger->shouldReceive('warning')
            ->with(
                sprintf(
                    'migrate %s from %d to %d',
                    $objectType,
                    $oldObjectId,
                    $newObjectId
                ),
                [LegacyLogger::CONTEXT_TYPE => DataMigrator::class]
            )
            ->once();

        $this->statsRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->userActivityRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->recommendationRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->shareRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->shoutRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->tagRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->userflagRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->ratingRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->artDuplicator->shouldReceive('duplicate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->catalogRepository->shouldReceive('migrateMap')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->labelRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->wantedRepository->shouldReceive('migrate')
            ->with($oldObjectId, $newObjectId)
            ->once();
        $this->playlistRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();
        $this->metadataRepository->shouldReceive('migrate')
            ->with($objectType, $oldObjectId, $newObjectId)
            ->once();

        $this->assertTrue(
            $this->subject->migrate($objectType, $oldObjectId, $newObjectId)
        );
    }
}
