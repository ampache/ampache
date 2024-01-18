<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Wanted;

use MusicBrainz\Exception;
use MusicBrainz\Filters\ArtistFilter;
use MusicBrainz\MusicBrainz;

final class MissingArtistFinder implements MissingArtistFinderInterface
{
    private MusicBrainz $musicBrainz;

    public function __construct(
        MusicBrainz $musicBrainz
    ) {
        $this->musicBrainz = $musicBrainz;
    }

    /**
     * @return array<array<string, string>>
     *
     * @throws Exception
     */
    public function find(string $artistName): array
    {
        $filter = new ArtistFilter([
            'artist' => $artistName
        ]);

        return array_map(
            static function ($result): array {
                return [
                    'mbid' => $result->id,
                    'name' => $result->name,
                ];
            },
            $this->musicBrainz->search($filter)
        );
    }
}
