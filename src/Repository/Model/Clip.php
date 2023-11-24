<?php

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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\System\Dba;
use PDOStatement;

class Clip extends Video
{
    protected const DB_TABLENAME = 'clip';

    public ?int $artist;
    public ?int $song;

    public $video;
    public $f_artist;
    public $f_song;

    /**
     * Constructor
     * This pulls the clip information from the database and returns
     * a constructed object
     * @param $clip_id
     */
    public function __construct($clip_id)
    {
        parent::__construct($clip_id);

        $info = $this->get_info($clip_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // Constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * garbage_collection
     *
     * This cleans out unused clips
     */
    public static function garbage_collection(): void
    {
        $sql = "DELETE FROM `clip` USING `clip` LEFT JOIN `video` ON `video`.`id` = `clip`.`id` WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * _get_artist_id
     * Look-up an artist id from artist tag data... creates one if it doesn't exist already
     * @param array $data
     * @return int|null
     */
    private static function _get_artist_id($data)
    {
        if (array_key_exists('artist_id', $data) && !empty($data['artist_id'])) {
            return $data['artist_id'];
        }
        if (!array_key_exists('artist_id', $data) || empty($data['artist'])) {
            return null;
        }
        $artist_mbid = $data['mbid_artistid'] ?? null;
        if ($artist_mbid) {
            $artist_mbid = Catalog::trim_slashed_list($artist_mbid);
        }

        return Artist::check($data['artist'], $artist_mbid);
    } // _get_artist_id

    /**
     * create
     * This takes a key'd array of data as input and inserts a new clip entry, it returns the record id
     * @param array $data
     * @param array $gtypes
     * @param array $options
     */
    public static function insert(array $data, $gtypes = array(), $options = array()): int
    {
        debug_event(self::class, 'insert ' . print_r($data,true), 5);
        $artist_id = self::_get_artist_id($data);
        $song_id   = Song::find($data);
        if (empty($song_id)) {
            $song_id = null;
        }
        if ($artist_id || $song_id) {
            debug_event(__CLASS__, 'insert ' . print_r(['artist_id' => $artist_id, 'song_id' => $song_id], true), 5);
            $sql = "INSERT INTO `clip` (`id`, `artist`, `song`) VALUES (?, ?, ?)";

            Dba::write($sql, array($data['id'], $artist_id, $song_id));
        }

        return (int)$data['id'];
    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a clip entry
     * @param array $data
     */
    public function update(array $data): int
    {
        debug_event(self::class, 'update ' . print_r($data,true), 5);
        $artist_id = self::_get_artist_id($data);
        $song_id   = Song::find($data);
        debug_event(self::class, 'update ' . print_r(['artist_id' => $artist_id,'song_id' => $song_id],true), 5);

        $sql = "UPDATE `clip` SET `artist` = ?, `song` = ? WHERE `id` = ?";
        Dba::write($sql, array($artist_id, $song_id, $this->id));

        return $this->id;
    } // update

    /**
     * format
     * this function takes the object and formats some values
     *
     * @param bool $details
     */

    public function format($details = true): void
    {
        parent::format($details);

        if ($details) {
            if ($this->artist) {
                $artist = new Artist($this->artist);
                $artist->format();
                $this->f_artist     = $artist->get_f_link();
                $this->f_full_title = '[' . scrub_out($artist->get_fullname()) . '] ' . $this->f_full_title;
            }

            if ($this->song) {
                $song         = new Song($this->song);
                $this->f_song = $song->get_f_link();
            }
        }
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords = parent::get_keywords();
        if ($this->artist) {
            $keywords['artist'] = array(
                'important' => true,
                'label' => T_('Artist'),
                'value' => $this->f_artist
            );
        }

        return $keywords;
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        if ($this->artist) {
            return array(
                'object_type' => 'artist',
                'object_id' => $this->artist
            );
        }

        return null;
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @return PDOStatement|bool
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        if ($object_type == 'artist') {
            $sql    = "UPDATE `clip` SET `artist` = ? WHERE `artist` = ?";
            $params = array($new_object_id, $old_object_id);

            return Dba::write($sql, $params);
        }

        return false;
    }
}
