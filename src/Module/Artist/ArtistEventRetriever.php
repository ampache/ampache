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

declare(strict_types=0);

namespace Ampache\Module\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\Artist;
use Ampache\Module\LastFm\LastFmQueryInterface;

final class ArtistEventRetriever implements ArtistEventRetrieverInterface
{
    private ConfigContainerInterface $configContainer;
    
    private LastFmQueryInterface $lastFmQuery;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LastFmQueryInterface $lastFmQuery
    ) {
        $this->configContainer = $configContainer;
        $this->lastFmQuery     = $lastFmQuery;
    }

    public function getUpcomingEvents(Artist $artist): array
    {
        return $this->getEvents(
            $artist,
            'artist.getevents',
            (int) $this->configContainer->get('concerts_limit_future')
        );
    }

    public function getPastEvents(Artist $artist): array
    {
        return $this->getEvents(
            $artist,
            'artist.getpastevents',
            (int) $this->configContainer->get('concerts_limit_past')
        );
    }
    
    private function getEvents(
        Artist $artist,
        string $api_method,
        int $limit
    ): array {
        $concerts = [];
        
        if (isset($artist->mbid)) {
            $query = 'mbid=' . rawurlencode($artist->mbid);
        } else {
            $query = 'artist=' . rawurlencode($artist->name);
        }

        if ($limit > 0) {
            $query .= '&limit=' . $limit;
        }

        $xml = $this->lastFmQuery->getLastFmResults($api_method, $query);

        if ($xml->events) {
            foreach ($xml->events->children() as $item) {
                if ($item->getName() == 'event') {
                    $concerts[] = $item;
                }
            }
        }

        return $concerts;
    }
}
