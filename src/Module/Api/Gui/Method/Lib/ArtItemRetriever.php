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

namespace Ampache\Module\Api\Gui\Method\Lib;

use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

/**
 * Retrieves art item for api calls by special rules
 */
final class ArtItemRetriever implements ArtItemRetrieverInterface
{
    public function retrieve(
        User $user,
        string $type,
        int $objectId
    ): ?Art {
        $art = null;
        if ($type == 'artist') {
            $art = new Art($objectId, 'artist');
        } elseif ($type == 'album') {
            $art = new Art($objectId, 'album');
        } elseif ($type == 'song') {
            $art = new Art($objectId, 'song');
            if ($art != null && $art->id == null) {
                // in most cases the song doesn't have a picture, but the album where it belongs to has
                // if this is the case, we take the album art
                $song = new Song($objectId);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($type == 'podcast') {
            $art = new Art($objectId, 'podcast');
        } elseif ($type == 'search') {
            $smartlist = new Search($objectId, 'song', $user);
            $listitems = $smartlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $art       = new Art($item['object_id'], $item['object_type']);
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($type == 'playlist') {
            $playlist  = new Playlist($objectId);
            $listitems = $playlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $art       = new Art($item['object_id'], $item['object_type']);
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, 'album');
            }
        }

        return $art;
    }
}
