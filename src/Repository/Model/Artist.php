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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Artist\ArtistFinderInterface;
use Ampache\Module\Artist\Tag\ArtistTagUpdaterInterface;
use Ampache\Module\Catalog\DataMigratorInterface;
use Ampache\Module\Label\LabelListUpdaterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

class Artist extends database_object implements library_item
{
    protected const DB_TABLENAME = 'artist';

    /* Variables from DB */

    /**
     * @var integer $id
     */
    public $id;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $summary
     */
    public $summary;

    /**
     * @var string $placeformed
     */
    public $placeformed;

    /**
     * @var integer $yearformed
     */
    public $yearformed;

    /**
     * @var integer $last_update
     */
    public $last_update;

    /**
     * @var integer $songs
     */
    public $songs;

    /**
     * @var integer $albums
     */
    public $albums;

    /**
     * @var string $prefix
     */
    public $prefix;

    /**
     * @var string $mbid
     */
    public $mbid; // MusicBrainz ID

    /**
     * @var integer $catalog_id
     */
    public $catalog_id;

    /**
     * @var integer $time
     */
    public $time;

    /**
     * @var integer $user
     */
    public $user;

    /**
     * @var boolean $manual_update
     */
    public $manual_update;

    /**
     * @var array $tags
     */
    public $tags;

    /**
     * @var string $f_tags
     */
    public $f_tags;

    /**
     * @var array $labels
     */
    public $labels;

    /**
     * @var string $f_labels
     */
    public $f_labels;

    /**
     * @var integer $object_cnt
     */
    public $object_cnt;

    /**
     * @var integer $total_count
     */
    private $total_count;

    /**
     * @var string $f_name // Prefix + Name, generated
     */
    public $f_name;

    /**
     * @var string $link
     */
    public $link;

    /**
     * @var string $f_link
     */
    public $f_link;

    /**
     * @var string $f_time
     */
    public $f_time;

    /**
     * @var integer $album_count
     */
    private $album_count;

    /**
     * @var integer $album_group_count
     */
    private $album_group_count;

    /**
     * @var integer $song_count
     */
    private $song_count;

    /**
     * Artist
     * Artist class, for modifying an artist
     * Takes the ID of the artist and pulls the info from the db
     * @param integer|null $artist_id
     * @param integer $catalog_init
     */
    public function __construct($artist_id = null, $catalog_init = 0)
    {
        /* If they failed to pass in an id, just run for it */
        if ($artist_id === null) {
            return false;
        }

        $this->catalog_id = $catalog_init;
        /* Get the information from the db */
        $info = $this->get_info($artist_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // foreach info

        // set the full name
        $this->f_name = trim(trim((string) $info['prefix']) . ' ' . trim((string) $info['name']));
        // make sure the int values are cast to integers
        $this->object_cnt        = (int)$this->total_count;
        $this->time              = (int)$this->time;
        $this->album_count       = (int)$this->album_count;
        $this->album_group_count = (int)$this->album_group_count;
        $this->song_count        = (int)$this->song_count;

        return true;
    } // constructor

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * _get_extra info
     * This returns the extra information for the artist, this means totals etc
     * @param integer $catalog
     * @param string $limit_threshold
     * @return array
     */
    private function _get_extra_info($catalog = 0, $limit_threshold = '')
    {
        $params = array($this->id);
        // Get associated information from first song only
        $sql  = "SELECT `song`.`artist`, `song`.`catalog` as `catalog_id`, `artist`.`total_count` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` ";
        $sqlw = "WHERE `song`.`artist` = ? ";
        if ($catalog) {
            $params[] = $catalog;
            $sqlw .= "AND (`song`.`catalog` = ?) ";
        }
        $sql .= $sqlw . "LIMIT 1";

        $db_results = Dba::read($sql, $params);
        $row        = Dba::fetch_assoc($db_results);

        if (AmpConfig::get('show_played_times')) {
            $row['object_cnt'] = (!empty($limit_threshold))
                ? Stats::get_object_count('artist', $row['artist'], $limit_threshold)
                : $row['total_count'];
        }

        /* Set Object Vars */
        $this->catalog_id = $row['catalog_id'];

        return $row;
    } // _get_extra_info

    /**
     * format
     * this function takes an array of artist
     * information and formats the relevant values
     * so they can be displayed in a table for example
     * it changes the title into a full link.
     * @param boolean $details
     * @param string $limit_threshold
     * @return boolean
     */
    public function format($details = true, $limit_threshold = '')
    {
        // If this is a memory-only object, we're done here
        if (!$this->id) {
            return true;
        }
        if ($this->time == 0) {
            $this->update_time();
        }
        if ($this->album_count == 0 && $this->album_group_count == 0 && $this->song_count == 0) {
            $this->update_album_count();
        }
        $this->songs  = $this->song_count;
        $this->albums = (AmpConfig::get('album_group')) ? $this->album_group_count : $this->album_count;

        if ($this->catalog_id) {
            $this->link   = AmpConfig::get('web_path') . '/artists.php?action=show&catalog=' . $this->catalog_id . '&artist=' . $this->id;
            $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . $this->f_name . "\">" . $this->f_name . "</a>";
        } else {
            $this->link   = AmpConfig::get('web_path') . '/artists.php?action=show&artist=' . $this->id;
            $this->f_link = "<a href=\"" . $this->link . "\" title=\"" . $this->f_name . "\">" . $this->f_name . "</a>";
        }

        if ($details) {
            // Get the counts
            $extra_info = $this->_get_extra_info($this->catalog_id, $limit_threshold);

            // Format the new time thingy that we just got
            $min = sprintf("%02d", (floor($this->time / 60) % 60));

            $sec   = sprintf("%02d", ($this->time % 60));
            $hours = floor($this->time / 3600);

            $this->f_time = ltrim((string)$hours . ':' . $min . ':' . $sec, '0:');

            $this->tags   = Tag::get_top_tags('artist', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'artist');

            if (AmpConfig::get('label')) {
                $this->labels   = $this->getLabelRepository()->getByArtist((int) $this->id);
                $this->f_labels = Ui::renderLabels($this->labels, true);
            }

            $this->object_cnt = $extra_info['object_cnt'];
        }

        return true;
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords                = array();
        $keywords['mb_artistid'] = array(
            'important' => false,
            'label' => T_('Artist MusicBrainzID'),
            'value' => $this->mbid
        );
        $keywords['artist'] = array(
            'important' => true,
            'label' => T_('Artist'),
            'value' => $this->f_name
        );

        return $keywords;
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
    }

    /**
     * Get parent item description.
     *
     * @return array{object_type: string, object_id: int}|null
     */
    public function get_parent(): ?array
    {
        return null;
    }

    /**
     * Get item childrens.
     * @return array
     */
    public function get_childrens()
    {
        $medias = array();
        $albums = $this->getAlbumRepository()->getByArtist($this->id);
        foreach ($albums as $album_id) {
            $medias[] = array(
                'object_type' => 'album',
                'object_id' => $album_id
            );
        }

        return array('album' => $medias);
    }

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        $search                    = array();
        $search['type']            = "album";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $search['rule_1_input']    = $this->name;
        $search['rule_1_operator'] = 4;
        $search['rule_1']          = "artist";
        $albums                    = Search::run($search);

        $childrens = array();
        foreach ($albums as $album_id) {
            $childrens[] = array(
                'object_type' => 'album',
                'object_id' => $album_id
            );
        }

        return $childrens;
    }

    /**
     * Get all childrens and sub-childrens medias.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'song') {
            $songs = $this->getSongRepository()->getByArtist($this->getId());
            foreach ($songs as $song_id) {
                $medias[] = array(
                    'object_type' => 'song',
                    'object_id' => $song_id
                );
            }
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array($this->catalog_id);
    }

    /**
     * Get item's owner.
     * @return integer|null
     */
    public function get_user_owner()
    {
        return $this->user;
    }

    /**
     * Get default art kind for this item.
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * get_description
     * @return string
     */
    public function get_description()
    {
        return $this->summary;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $artist_id = null;
        $type      = null;

        if (Art::has_db($this->id, 'artist') || $force) {
            $artist_id = $this->id;
            $type      = 'artist';
        }

        if ($artist_id !== null && $type !== null) {
            echo Art::display($type, $artist_id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * update
     * This takes a key'd array of data and updates the current artist
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        // Save our current ID
        $name        = isset($data['name']) ? $data['name'] : $this->name;
        $mbid        = isset($data['mbid']) ? $data['mbid'] : $this->mbid;
        $summary     = isset($data['summary']) ? $data['summary'] : $this->summary;
        $placeformed = isset($data['placeformed']) ? $data['placeformed'] : $this->placeformed;
        $yearformed  = isset($data['yearformed']) ? $data['yearformed'] : $this->yearformed;

        $current_id = $this->id;

        // Check if name is different than current name
        if ($this->name != $name) {
            $updated    = false;
            $artist_id  = static::getArtistFinder()->find($name, $mbid, true);

            // If it's changed we need to update
            if ($artist_id !== null && $artist_id !== $this->id) {
                $time  = time();
                $songs = $this->getSongRepository()->getByArtist($this->getId());
                foreach ($songs as $song_id) {
                    Song::update_artist($artist_id, $song_id, $this->id);
                    Song::update_utime($song_id, $time);
                }
                $updated    = true;
                $current_id = $artist_id;

                $this->getDataMigrator()->migrate('artist', $this->id, $artist_id);
            } // end if it changed

            // clear out the old data
            if ($updated) {
                Stats::garbage_collection();
                Rating::garbage_collection();
                Userflag::garbage_collection();
                $this->getUseractivityRepository()->collectGarbage();
                $artist = $this->getModelFactory()->createArtist((int) $current_id);
                $artist->update_album_count();
            } // if updated
        } else {
            if ($this->mbid != $mbid) {
                $sql = 'UPDATE `artist` SET `mbid` = ? WHERE `id` = ?';
                Dba::write($sql, array($mbid, $current_id));
            }
        }

        // Update artist name (if we don't want to use the MusicBrainz name)
        $trimmed = Catalog::trim_prefix(trim((string)$name));
        $name    = $trimmed['string'];
        if ($name != '' && $name != $this->name) {
            $sql = 'UPDATE `artist` SET `name` = ? WHERE `id` = ?';
            Dba::write($sql, array($name, $current_id));
        }

        $this->update_artist_info($summary, $placeformed, $yearformed, true);

        $this->name = $name;
        $this->mbid = $mbid;

        $override_childs = false;
        if ($data['overwrite_childs'] == 'checked') {
            $override_childs = true;
        }

        $add_to_childs = false;
        if ($data['add_to_childs'] == 'checked') {
            $add_to_childs = true;
        }

        if (isset($data['edit_tags'])) {
            $this->getArtistTagUpdater()->updateTags(
                $this,
                $data['edit_tags'],
                $override_childs,
                $add_to_childs,
                true
            );
        }

        if (AmpConfig::get('label') && isset($data['edit_labels'])) {
            $this->getLabelListUpdater()->update(
                $data['edit_labels'],
                (int) $this->id,
                true
            );
        }

        return $current_id;
    } // update

    /**
     * Update artist information.
     * @param string $summary
     * @param string $placeformed
     * @param integer $yearformed
     * @param boolean $manual
     */
    public function update_artist_info($summary, $placeformed, $yearformed, $manual = false)
    {
        // set null values if missing
        $summary     = (empty($summary)) ? null : $summary;
        $placeformed = (empty($placeformed)) ? null : $placeformed;
        $yearformed  = ((int)$yearformed == 0) ? null : Catalog::normalize_year($yearformed);

        $this->getArtistRepository()->updateArtistInfo(
            $this,
            $summary,
            $placeformed,
            $yearformed,
            (bool) $manual
        );

        $this->summary     = $summary;
        $this->placeformed = $placeformed;
        $this->yearformed  = $yearformed;
    }

    /**
     * update_time
     *
     * Get time for an artist and set it.
     */
    private function update_time()
    {
        $artistRepository = $this->getArtistRepository();

        $time = $artistRepository->getDuration($this);
        if ($time > 0 && $time !== $this->time && $this->id) {
            $artistRepository->updateTime($this, $time);

            $this->time = $time;
            $artistRepository->updateLastUpdate($this->getId());
        }
    }

    /**
     * update_album_count
     *
     * Get album_count, album_group_count for an artist and set it.
     */
    public function update_album_count()
    {
        $albumRepository  = $this->getAlbumRepository();
        $artistRepository = $this->getArtistRepository();

        $album_count = $albumRepository->getCountByArtist($this);
        if ($album_count > 0 && $album_count !== $this->album_count && $this->id) {
            $artistRepository->updateAlbumCount($this, $album_count);

            $this->album_count = $album_count;

            $artistRepository->updateLastUpdate($this->getId());
        }

        $group_count = $albumRepository->getGroupedCountByArtist($this);
        if ($group_count > 0 && $group_count !== $this->album_group_count && $this->id) {
            $artistRepository->updateAlbumGroupCount($this, $group_count);

            $this->album_group_count = $group_count;

            $artistRepository->updateLastUpdate($this->getId());
        }

        $song_count = $this->getSongRepository()->getCountByArtist($this);
        if ($song_count > 0 && $song_count !== $this->song_count && $this->id) {
            $artistRepository->updateSongCount($this, $song_count);

            $this->song_count = $song_count;

            $artistRepository->updateLastUpdate($this->getId());
        }

        $this->update_time();
    }

    /**
     * @deprecated
     */
    private function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getLabelListUpdater(): LabelListUpdaterInterface
    {
        global $dic;

        return $dic->get(LabelListUpdaterInterface::class);
    }

    /**
     * @deprecated
     */
    private function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getArtistTagUpdater(): ArtistTagUpdaterInterface
    {
        global $dic;

        return $dic->get(ArtistTagUpdaterInterface::class);
    }

    /**
     * @deprecated
     */
    private function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getDataMigrator(): DataMigratorInterface
    {
        global $dic;

        return $dic->get(DataMigratorInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getArtistFinder(): ArtistFinderInterface
    {
        global $dic;

        return $dic->get(ArtistFinderInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getModelFactory(): ModelFactoryInterface
    {
        global $dic;

        return $dic->get(ModelFactoryInterface::class);
    }
}
