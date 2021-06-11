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

namespace Ampache\Module\TvShow\Deletion;

use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\TvShowInterface;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\RatingRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

final class TvShowDeleter implements TvShowDeleterInterface
{
    private RatingRepositoryInterface $ratingRepository;

    private ShoutRepositoryInterface $shoutRepository;

    private UserActivityRepositoryInterface $userActivityRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        RatingRepositoryInterface $ratingRepository,
        ShoutRepositoryInterface $shoutRepository,
        UserActivityRepositoryInterface $userActivityRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->ratingRepository       = $ratingRepository;
        $this->shoutRepository        = $shoutRepository;
        $this->userActivityRepository = $userActivityRepository;
        $this->modelFactory           = $modelFactory;
    }

    public function delete(TvShowInterface $tvShow): bool
    {
        $deleted    = true;
        $season_ids = $tvShow->get_seasons();
        $tvShowId   = $tvShow->getId();

        foreach ($season_ids as $season_object) {
            $season  = $this->modelFactory->createTvShowSeason($season_object);
            $deleted = $season->remove();
            if (!$deleted) {
                debug_event(self::class, 'Error when deleting the season `' . (string) $season_object . '`.', 1);
                break;
            }
        }

        if ($deleted) {
            $sql     = "DELETE FROM `tvshow` WHERE `id` = ?";
            $deleted = Dba::write($sql, [$tvShowId]);
            if ($deleted) {
                Art::garbage_collection('tvshow', $tvShowId);
                Userflag::garbage_collection('tvshow', $tvShowId);
                $this->ratingRepository->collectGarbage('tvshow', $tvShowId);
                $this->shoutRepository->collectGarbage('tvshow', $tvShowId);
                $this->userActivityRepository->collectGarbage('tvshow', $tvShowId);
            }
        }

        return $deleted;
    }
}
