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

namespace Ampache\Module\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\Ui;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\TagRepositoryInterface;

final class SingleItemUpdater implements SingleItemUpdaterInterface
{
    private SongRepositoryInterface $songRepository;

    private AlbumRepositoryInterface $albumRepository;

    private TagRepositoryInterface $tagRepository;

    private ConfigContainerInterface $configContainer;

    private ArtistRepositoryInterface $artistRepository;

    public function __construct(
        SongRepositoryInterface $songRepository,
        AlbumRepositoryInterface $albumRepository,
        TagRepositoryInterface $tagRepository,
        ConfigContainerInterface $configContainer,
        ArtistRepositoryInterface $artistRepository
    ) {
        $this->songRepository   = $songRepository;
        $this->albumRepository  = $albumRepository;
        $this->tagRepository    = $tagRepository;
        $this->configContainer  = $configContainer;
        $this->artistRepository = $artistRepository;
    }

    /**
     * updates a single album,artist,song from the tag data
     * this can be done by 75+
     *
     * @param string $type
     * @param int $objectId
     * @param bool $api
     *
     * @return int
     */
    public function update(
        string $type,
        int $objectId,
        bool $api = false
    ): int {
        // Because single items are large numbers of things too
        set_time_limit(0);

        $songs   = array();
        $result  = $objectId;
        $libitem = 0;

        switch ($type) {
            case 'album':
                $libitem = new Album($objectId);
                $songs   = $this->songRepository->getByAlbum($libitem->getId());
                break;
            case 'artist':
                $libitem = new Artist($objectId);
                $songs   = $this->songRepository->getByArtist($libitem);
                break;
            case 'song':
                $songs[] = $objectId;
                break;
        } // end switch type

        if (!$api) {
            echo '<table class="tabledata">' . "\n";
            echo '<thead><tr class="th-top">' . "\n";
            echo "<th>" . T_("Song") . "</th><th>" . T_("Status") . "</th>\n";
            echo "<tbody>\n";
        }
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            $info = Catalog::update_media_from_tags($song);
            // don't echo useless info when using api
            if (($info['change']) && (!$api)) {
                if ($info['element'][$type]) {
                    $change = explode(' --> ', (string)$info['element'][$type]);
                    $result = (int)$change[1];
                }
                $file = scrub_out($song->file);
                echo '<tr class="' . Ui::flip_class() . '">' . "\n";
                echo "<td>$file</td><td>" . T_('Updated') . "</td>\n";
                echo $info['text'];
                echo "</td>\n</tr>\n";
                flush();
            } else {
                if (!$api) {
                    echo '<tr class="' . Ui::flip_class() . '"><td>' . scrub_out($song->file) . "</td><td>" . T_('No Update Needed') . "</td></tr>\n";
                }
                flush();
            }
        } // foreach songs
        if (!$api) {
            echo "</tbody></table>\n";
        }
        // Update the tags for
        switch ($type) {
            case 'album':
                /** @var Album $libitem*/
                $tags = $this->tagRepository->getSongTags('album', $libitem->id);
                Tag::update_tag_list(implode(',', $tags), 'album', $libitem->id, false);
                $this->albumRepository->updateTime($libitem);
                break;
            case 'artist':
                /** @var Artist $libitem */
                foreach ($this->albumRepository->getDistinctIdsByArtist($libitem) as $album_id) {
                    $album_tags = $this->tagRepository->getSongTags('album', $album_id);
                    Tag::update_tag_list(implode(',', $album_tags), 'album', $album_id, false);
                }
                $tags = $this->tagRepository->getSongTags('artist', $libitem->id);
                Tag::update_tag_list(implode(',', $tags), 'artist', $libitem->id, false);
                $libitem->update_album_count();
                break;
        } // end switch type

        // Cleanup old objects that are no longer needed
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CRON_CACHE)) {
            $this->albumRepository->collectGarbage();
            $this->artistRepository->collectGarbage();
        }

        return $result;
    }
}
