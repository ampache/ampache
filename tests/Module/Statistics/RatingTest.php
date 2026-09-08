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

namespace Ampache\Module\Statistics;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Database\database_object;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Repository\RatingRepositoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RatingTest extends MockeryTestCase
{
    private UserActivityPosterInterface&MockInterface $activityPoster;
    private RatingRepositoryInterface&MockInterface $ratingRepository;

    public function testSetRatingClampsAnOutOfRangeValueToFive(): void
    {
        // the one door every writer passes through bounds the star value, so 127 lands as 5
        $this->primeRating('song', 42, 7, 0);

        $this->activityPoster->shouldReceive('post')->once();
        $this->ratingRepository->shouldReceive('adjustWeight')
            ->with('song', 42, 1)
            ->once();
        $this->ratingRepository->shouldReceive('setRating')
            ->with(42, 'song', 5, 7, \Mockery::type('int'))
            ->once();

        $rating = new Rating(42, 'song');

        self::assertTrue($rating->set_rating(127, 7, false));
    }

    public function testSetRatingIsANoOpWhenSettingZeroOnAnUnratedObject(): void
    {
        // an unrated object reads as 0, so setting 0 must not run through the weight decrement again and again
        $this->primeRating('song', 42, 7, 0);

        $this->ratingRepository->shouldNotReceive('adjustWeight');
        $this->ratingRepository->shouldNotReceive('deleteRating');
        $this->ratingRepository->shouldNotReceive('setRating');

        $rating = new Rating(42, 'song');

        self::assertTrue($rating->set_rating(0, 7, false));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->ratingRepository = $this->mock(RatingRepositoryInterface::class);
        $this->activityPoster   = $this->mock(UserActivityPosterInterface::class);

        $logger = new NullLogger();

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')
            ->andReturnUsing(fn(string $id) => match ($id) {
                RatingRepositoryInterface::class => $this->ratingRepository,
                UserActivityPosterInterface::class => $this->activityPoster,
                LoggerInterface::class => $logger,
                default => $logger,
            });

        $GLOBALS['dic'] = $dic;
    }

    private function primeRating(string $type, int $objectId, int $userId, int $value): void
    {
        // a cached 0 means "no rating", which is exactly the unrated case the guard must treat as 0
        AmpConfig::set('memory_cache', true, true);
        (new \ReflectionProperty(database_object::class, '_enabled'))->setValue(null, null);
        Rating::add_to_cache('rating_' . $type . '_user' . $userId, $objectId, [$value]);
    }
}
