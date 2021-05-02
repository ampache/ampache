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

namespace Ampache\Module\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Cache\DatabaseObjectCacheInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final class ArtistCacheBuilder implements ArtistCacheBuilderInterface
{
    private DatabaseObjectCacheInterface $databaseObjectCache;

    private ArtistRepositoryInterface $artistRepository;

    private SongRepositoryInterface $songRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        DatabaseObjectCacheInterface $databaseObjectCache,
        ArtistRepositoryInterface $artistRepository,
        SongRepositoryInterface $songRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->databaseObjectCache = $databaseObjectCache;
        $this->artistRepository    = $artistRepository;
        $this->songRepository      = $songRepository;
        $this->configContainer     = $configContainer;
    }

    /**
     * this attempts to build a cache of the data from the passed albums all in one query
     *
     * @param int[] $ids
     */
    public function build(array $ids, bool $extra = false, int $limitThreshold = 0): void
    {
        if ($ids === []) {
            return;
        }

        foreach ($this->artistRepository->getByIdList($ids) as $row) {
            $this->databaseObjectCache->add('artist', $row['id'], $row);
        }

        // If we need to also pull the extra information, this is normally only used when we are doing the human display
        if ($extra && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_PLAYED_TIMES)) {
            foreach ($this->songRepository->getByIdList($ids) as $row) {
                $row['object_cnt'] = Stats::get_object_count('artist', $row['artist'], $limitThreshold);
                $this->databaseObjectCache->add('artist_extra', $row['artist'], $row);
            }
        }
    }
}
