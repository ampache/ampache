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

namespace Ampache\Module\Wanted;

use Ampache\Config\ConfigContainerInterface;
use MusicBrainz\MusicBrainz;

final class MissingArtistLookup implements MissingArtistLookupInterface
{
    private ConfigContainerInterface $configContainer;

    private MusicBrainz $musicBrainz;

    public function __construct(
        ConfigContainerInterface $configContainer,
        MusicBrainz $musicBrainz
    ) {
        $this->configContainer     = $configContainer;
        $this->musicBrainz         = $musicBrainz;
    }

    /**
     * Get missing artist data.
     */
    public function lookup(string $musicbrainzId): array
    {
        $wartist = [];

        $wartist['mbid'] = $musicbrainzId;
        $wartist['name'] = T_('Unknown Artist');

        set_time_limit(600);

        try {
            $martist = $this->musicBrainz->lookup('artist', $musicbrainzId);
        } catch (\Exception $error) {
            return $wartist;
        }

        $wartist['name'] = $martist->name;
        $wartist['link'] = sprintf(
            '<a href="%s/artists.php?action=show_missing&mbid=%s" title="%s">%s</a>',
            $this->configContainer->getWebPath(),
            $wartist['mbid'],
            $wartist['name'],
            $wartist['name']
        );

        return $wartist;
    }
}
