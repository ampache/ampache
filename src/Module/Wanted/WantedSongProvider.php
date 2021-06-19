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

namespace Ampache\Module\Wanted;

use Ampache\Module\System\Core;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\WantedInterface;
use Exception;
use MusicBrainz\MusicBrainz;

final class WantedSongProvider implements WantedSongProviderInterface
{
    private MusicBrainz $musicBrainz;

    private ModelFactoryInterface $modelFactory;

    private MissingArtistLookupInterface $missingArtistLookup;

    public function __construct(
        MusicBrainz $musicBrainz,
        ModelFactoryInterface $modelFactory,
        MissingArtistLookupInterface $missingArtistLookup
    ) {
        $this->musicBrainz         = $musicBrainz;
        $this->modelFactory        = $modelFactory;
        $this->missingArtistLookup = $missingArtistLookup;
    }

    /**
     * Load wanted release data.
     *
     * @return array<Song_Preview>
     */
    public function provide(
        WantedInterface $wanted
    ): array {
        $name = $wanted->getName();

        try {
            $group = $this->musicBrainz->lookup('release-group', $wanted->getMusicBrainzId(), array('releases'));
            // Set fresh data
            $name = $group->title;

            // Load from database if already cached
            $result = Song_Preview::get_song_previews($wanted->getMusicBrainzId());
            if (count($group->releases) > 0) {
                if ($result === []) {
                    // Use the first release as reference for track content
                    $release = $this->musicBrainz->lookup('release', $group->releases[0]->id, array('recordings'));
                    foreach ($release->media as $media) {
                        foreach ($media->tracks as $track) {
                            $song          = array();
                            $song['disk']  = Album::sanitize_disk($media->position);
                            $song['track'] = $track->number;
                            $song['title'] = $track->title;
                            $song['mbid']  = $track->id;
                            if ($wanted->getArtistId()) {
                                $song['artist'] = $wanted->getArtistId();
                            }
                            $song['artist_mbid'] = $wanted->getArtistMusicBrainzId();
                            $song['session']     = session_id();
                            $song['album_mbid']  = $wanted->getMusicBrainzId();

                            if ($wanted->getArtistId()) {
                                $artist      = $this->modelFactory->createArtist($wanted->getArtistId());
                                $artist_name = $artist->name;
                            } else {
                                $wartist     = $this->missingArtistLookup->lookup($wanted->getArtistMusicBrainzId());
                                $artist_name = $wartist['name'];
                            }

                            $song['file'] = null;
                            foreach (Plugin::get_plugins('get_song_preview') as $plugin_name) {
                                $plugin = new Plugin($plugin_name);
                                if ($plugin->load(Core::get_global('user'))) {
                                    $song['file'] = $plugin->_plugin->get_song_preview($track->id, $artist_name,
                                        $track->title);
                                    if ($song['file'] != null) {
                                        break;
                                    }
                                }
                            }

                            if ($song != null) {
                                $result[] = new Song_Preview(Song_Preview::insert($song));
                            }
                        }
                    }
                }
            }
        } catch (Exception $error) {
            $result = [];
        }

        foreach ($result as $song) {
            $song->f_album = $name;
            $song->format();
        }

        return $result;
    }
}
