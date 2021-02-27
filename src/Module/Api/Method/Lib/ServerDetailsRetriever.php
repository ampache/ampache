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

namespace Ampache\Module\Api\Method\Lib;

use Ampache\Module\Api\Api;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;

/**
 * Provides access to vital server informations
 */
final class ServerDetailsRetriever implements ServerDetailsRetrieverInterface
{
    private CatalogRepositoryInterface $catalogRepository;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    public function __construct(
        CatalogRepositoryInterface $catalogRepository,
        UpdateInfoRepositoryInterface $updateInfoRepository
    ) {
        $this->catalogRepository    = $catalogRepository;
        $this->updateInfoRepository = $updateInfoRepository;
    }

    /**
     * get the server counts for pings and handshakes
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $token = ''): array
    {
        // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
        $details = $this->catalogRepository->getLastActionDates();

        // Now we need to quickly get the totals
        $counts = $this->updateInfoRepository->countServer(true);

        $authResult = $token !== '' ? ['auth' => $token] : [];

        // send the totals
        $result = [
            'api' => Api::$version,
            'update' => date('c', (int) $details['update']),
            'add' => date('c', (int) $details['add']),
            'clean' => date('c', (int) $details['clean']),
            'songs' => $counts['song'],
            'albums' => $counts['album'],
            'artists' => $counts['artist'],
            'genres' => $counts['tag'],
            'playlists' => ($counts['playlist'] + $counts['search']),
            'users' => $counts['user'],
            'catalogs' => $counts['catalog'],
            'videos' => $counts['video'],
            'podcasts' => $counts['podcast'],
            'podcast_episodes' => $counts['podcast_episode'],
            'shares' => $counts['share'],
            'licenses' => $counts['license'],
            'live_streams' => $counts['live_stream'],
            'labels' => $counts['label']
        ];

        return array_merge($authResult, $result);
    }
}
