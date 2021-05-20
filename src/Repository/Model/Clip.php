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

namespace Ampache\Repository\Model;

use Ampache\Module\Artist\ArtistFinderInterface;
use Ampache\Module\System\Dba;
use Ampache\Repository\ClipRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final class Clip extends Video
{
    /** @var array<string, mixed>|null  */
    private ?array $dbData = null;

    private ClipRepositoryInterface $clipRepository;

    private ModelFactoryInterface $modelFactory;

    private SongRepositoryInterface $songRepository;

    /**
     * This pulls the clip information from the database and returns
     * a constructed object
     */
    public function __construct(
        ClipRepositoryInterface $clipRepository,
        ModelFactoryInterface $modelFactory,
        SongRepositoryInterface $songRepository,
        int $id
    ) {
        parent::__construct($id);

        $this->clipRepository = $clipRepository;
        $this->id             = $id;
        $this->modelFactory   = $modelFactory;
        $this->songRepository = $songRepository;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSongId(): int
    {
        return (int) ($this->getDbData()['song'] ?? 0);
    }

    public function getArtistId(): int
    {
        return (int) ($this->getDbData()['artist'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->clipRepository->getDataById($this->id);
        }

        return $this->dbData;
    }

    /**
     * _get_artist_id
     * Look-up an artist id from artist tag data... creates one if it doesn't exist already
     * @param array $data
     * @return integer|null
     */
    private static function _get_artist_id($data)
    {
        if (isset($data['artist_id']) && !empty($data['artist_id'])) {
            return $data['artist_id'];
        }
        if (!isset($data['artist']) || empty($data['artist'])) {
            return null;
        }
        $artist_mbid = isset($data['mbid_artistid']) ? $data['mbid_artistid'] : null;
        if ($artist_mbid) {
            $artist_mbid = Catalog::trim_slashed_list($artist_mbid);
        }

        return static::getArtistFinder()->find($data['artist'], $artist_mbid);
    } // _get_artist_id

    /**
     * create
     * This takes a key'd array of data as input and inserts a new clip entry, it returns the record id
     * @param array $data
     * @param array $gtypes
     * @param array $options
     * @return mixed
     */
    public static function insert(array $data, $gtypes = array(), $options = array())
    {
        debug_event(self::class, 'insert ' . print_r($data,true) , 5);
        $artist_id = self::_get_artist_id($data);
        $song_id   = static::getSongRepository()->findBy($data);
        if (empty($song_id)) {
            $song_id = null;
        }
        if ($artist_id || $song_id) {
            debug_event(__CLASS__, 'insert ' . print_r(['artist_id' => $artist_id, 'song_id' => $song_id], true), 5);
            $sql = "INSERT INTO `clip` (`id`, `artist`, `song`) " . "VALUES (?, ?, ?)";

            Dba::write($sql, array($data['id'], $artist_id, $song_id));
        }

        return $data['id'];
    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a clip entry
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        debug_event(self::class, 'update ' . print_r($data,true) , 5);
        $artist_id = self::_get_artist_id($data);
        $song_id   = $this->songRepository->findBy($data);
        debug_event(self::class, 'update ' . print_r(['artist_id' => $artist_id,'song_id' => $song_id],true) , 5);

        $sql = "UPDATE `clip` SET `artist` = ?, `song` = ? WHERE `id` = ?";
        Dba::write($sql, array($artist_id, $song_id, $this->id));

        return $this->id;
    } // update

    /**
     * format
     * this function takes the object and formats some values
     * @param boolean $details
     */
    public function format($details = true)
    {
        parent::format($details);
    }

    public function getSongLinkFormatted(): string
    {
        if ($this->getSongId()) {
            $song = new Song($this->getSongId());
            $song->format();

            return $song->f_link;
        }

        return '';
    }

    public function getArtistLinkFormatted(): string
    {
        $artist = $this->getArtist();
        if ($artist !== null) {
            return $artist->f_link;
        }

        return '';
    }

    private function getArtist(): ?Artist
    {
        if ($this->getArtist()) {
            $artist = $this->modelFactory->createArtist($this->getArtistId());
            $artist->format();

            return $artist;
        }

        return null;
    }

    public function getFullTitle(): string
    {
        $artist = $this->getArtist();
        if ($artist !== null) {
            return '[' . scrub_out($artist->f_name) . '] ' . $this->getFullTitle();
        }

        return '';
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords = parent::get_keywords();
        if ($this->getArtistId()) {
            $keywords['artist'] = array(
                'important' => true,
                'label' => T_('Artist'),
                'value' => $this->getArtistLinkFormatted()
            );
        }

        return $keywords;
    }

    /**
     * @return array{object_type: string, object_id: int}|null
     */
    public function get_parent(): ?array
    {
        if ($this->getArtistId()) {
            return ['object_type' => 'artist', 'object_id' => $this->getArtistId()];
        }

        return null;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getArtistFinder(): ArtistFinderInterface
    {
        global $dic;

        return $dic->get(ArtistFinderInterface::class);
    }
}
