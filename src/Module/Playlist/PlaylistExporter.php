<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Playlist;

use Ahc\Cli\IO\Interactor;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Playlist;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;

final class PlaylistExporter implements PlaylistExporterInterface
{
    public const VALID_FILE_EXTENSIONS = ['m3u', 'xspf', 'pls'];

    public function export(
        Interactor $interactor,
        string $dirname,
        string $type,
        string $ext,
        string $playlistId,
        int $userId,
        string $urltype
    ): void {
        // Make sure the output dir is valid and writeable
        if (!is_writeable($dirname)) {
            $interactor->error(
                sprintf(T_('There was a problem creating this directory: %s'), $dirname),
                true
            );
        }

        // Switch on the type of playlist dump we want to do here
        switch ($type) {
            case 'albums':
                $ids   = Catalog::get_albums();
                $items = array();
                foreach ($ids as $albumid) {
                    $items[] = new Album($albumid);
                }
                break;
            case 'artists':
                $items = Catalog::get_artists();
                break;
            case 'smartlists':
                if ((int)$playlistId < 1) {
                    $ids = Playlist::get_smartlists($userId);
                } else {
                    $ids = array($playlistId);
                }
                $items = array();
                foreach ($ids as $playlist_id) {
                    $searchId   = (int)str_replace('smart_', '', $playlist_id);
                    $searchUser = new User($userId);
                    $playlist   = ($searchUser->id)
                        ? new Search($searchId, 'song', $searchUser)
                        : new Search($searchId);
                    if ($playlist->id) {
                        $items[] = $playlist;
                    }
                }
                break;
            case 'playlists':
            default:
                if ((int)$playlistId < 1) {
                    $ids = Playlist::get_playlists($userId);
                } else {
                    $ids = array($playlistId);
                }
                $items = array();
                foreach ($ids as $playlist_id) {
                    $playlist = new Playlist($playlist_id);
                    if ($playlist->id) {
                        $items[] = $playlist;
                    }
                }
                break;
        }
        $dirname = rtrim($dirname, "/");

        foreach ($items as $item) {
            $item->format();
            $name = $item->get_fullname();
            // We don't know about file system encoding / specificity
            // For now, we only keep simple characters to be sure it will work everywhere
            $name      = preg_replace('/[:]/', '.', $name);
            $name      = preg_replace('/[^a-zA-Z0-9. -]/', '', $name);
            $filename  = $dirname . DIRECTORY_SEPARATOR . $item->id . '. ' . $name . '.' . $ext;
            $medias    = $item->get_medias();
            $pl        = new Stream_Playlist($userId);
            $pl->title = $item->get_fullname();
            foreach ($medias as $media) {
                $pl->urls[] = Stream_Playlist::media_to_url($media, $dirname, $urltype);
            }

            $plstr = $pl->{'get_' . $ext . '_string'}();
            if (file_put_contents($filename, $plstr) === false) {
                $interactor->error(
                    sprintf(T_('There was a problem creating the playlist file "%s"'), $filename),
                    true
                );
            } else {
                $interactor->ok(
                    sprintf(T_('Playlist created "%s"'), $filename),
                    true
                );
            }
        }
    }
}
