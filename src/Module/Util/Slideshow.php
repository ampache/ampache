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

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use Ampache\Module\Art\Art;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Plugin\PluginRetrieverInterface;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Plugin\Ampacheflickr;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use SimpleXMLElement;

final readonly class Slideshow implements SlideshowInterface
{
    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private PluginRetrieverInterface $pluginRetriever,
        private AlbumRepositoryInterface $albumRepository,
    ) {}

    /**
     * @return array<int, array{title: SimpleXMLElement|string, url: string}>
     */
    public function getCurrentSlideshow(User $user): array
    {
        $songs  = Stats::get_recently_played($user->getId(), 'stream', 'song');
        $images = [];
        if ($songs !== []) {
            $last_song = $this->modelFactory->createSong((int) $songs[0]['object_id']);
            $images    = $this->getImages($last_song, $user);
        }

        return $images;
    }

    /**
     * @return array<int, array{title: SimpleXMLElement|string, url: string}>
     */
    private function getImages(Song $song, User $user): array
    {
        $images = [];

        foreach ($this->pluginRetriever->retrieveByType(PluginTypeEnum::SLIDESHOW, $user) as $plugin) {
            if ($plugin->_plugin instanceof Ampacheflickr) {
                $images += $plugin->_plugin->get_photos($song->get_parent_fullname());
            }
        }

        // no plugin configured anything usable (e.g. no Flickr key); fall back to the artist's own art already in the catalog
        if ($images === []) {
            return $this->getLocalArt($song);
        }

        return $images;
    }

    /**
     * The song's own art, the artist's photo, and their albums' covers, for when no slideshow plugin has anything to show.
     *
     * @return array<int, array{title: string, url: string}>
     */
    private function getLocalArt(Song $song): array
    {
        $images = [];

        if (AmpConfig::get_bool('show_song_art') && Art::has_db($song->id, 'song')) {
            $url = Art::url($song->id, 'song');
            if ($url !== null) {
                $images[] = ['title' => $song->get_parent_fullname(), 'url' => $url];
            }
        }

        $artist_id = $song->artist;
        if ($artist_id === null) {
            return $images;
        }

        if (Art::has_db($artist_id, 'artist')) {
            $url = Art::url($artist_id, 'artist');
            if ($url !== null) {
                $images[] = ['title' => $song->get_parent_fullname(), 'url' => $url];
            }
        }

        foreach ($this->albumRepository->getAlbumByArtist($artist_id) as $album_id) {
            if (Art::has_db($album_id, 'album')) {
                $url = Art::url($album_id, 'album');
                if ($url !== null) {
                    $images[] = ['title' => $song->get_parent_fullname(), 'url' => $url];
                }
            }
        }

        return $images;
    }
}
