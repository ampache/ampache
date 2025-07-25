<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Beets;

use Ahc\Cli\IO\Interactor;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\Ui;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;

/**
 * Catalog parent for local and remote beets catalog
 *
 * @author raziel
 */
abstract class Catalog extends \Ampache\Repository\Model\Catalog
{
    protected string $version;
    protected string $type;
    protected string $description;

    /** Added Songs counter */
    protected int $addedSongs = 0;

    /** Verified Songs counter */
    protected int $verifiedSongs = 0;

    /**
     * Array of all songs
     * @var string[]
     */
    protected $songs = [];

    /** command which provides the list of all songs */
    protected string $listCommand;

    /**
     * Counter used for cleaning actions
     */
    private int $cleanCounter = 0;

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct(?int $catalog_id = null)
    {
        // TODO: Basic constructor should be provided from parent
        if ($catalog_id) {
            $this->id = (int) $catalog_id;
            $info     = $this->get_info($catalog_id, static::DB_TABLENAME);
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * prepare_media
     * @param Podcast_Episode|Song|Video $media
     * @return array{
     *   file_path: string,
     *   file_name: string,
     *   file_size: int,
     *   file_type: string
     * }
     */
    public function prepare_media(Podcast_Episode|Video|Song $media): array
    {
        debug_event(self::class, 'Play: Started remote stream - ' . $media->file, 5);

        return [
            'file_path' => (string) $media->file,
            'file_name' => $media->getFileName(),
            'file_size' => $media->size,
            'file_type' => $media->type,
        ];
    }

    /**
     * @param string $prefix Prefix like add, updated, verify and clean
     * @param int $count song count
     * @param array $song Song array
     * @param bool $ignoreTicker ignoring the ticker for the last update
     */
    protected function updateUi($prefix, $count, $song = null, $ignoreTicker = false): void
    {
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            return;
        }
        if ($ignoreTicker || Ui::check_ticker()) {
            Ui::update_text($prefix . '_count_' . $this->id, $count);
            if (isset($song)) {
                Ui::update_text($prefix . '_dir_' . $this->id, scrub_out($this->getVirtualSongPath($song)));
            }
        }
    }

    /**
     * Get the parser class like CliHandler or JsonHandler
     */
    abstract protected function getParser(): Handler;

    /**
     * Check if a song was added before
     */
    abstract public function checkSong(array $song): bool;

    /**
     * add_to_catalog
     * @param null|array<string, string|bool> $options
     * @param null|Interactor $interactor
     * @return int
     */
    public function add_to_catalog(?array $options = null, ?Interactor $interactor = null): int
    {
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            require Ui::find_template('show_adds_catalog.inc.php');
            flush();
        }
        set_time_limit(0);
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_top(T_('Running Beets Update'));
        }
        /** @var Handler $parser */
        $parser = $this->getParser();
        /** @see self::addSong() */
        $parser->setHandler($this, 'addSong');
        $parser->start($parser->getTimedCommand($this->listCommand, 'added', 0));
        $this->updateUi('add', $this->addedSongs, null, true);
        $this->update_last_add();

        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            Ui::show_box_bottom();
        }

        return $this->addedSongs;
    }

    /**
     * Add $song to ampache if it isn't already
     * @param array $song
     */
    public function addSong($song): void
    {
        $song['catalog'] = $this->id;

        if ($this->checkSong($song)) {
            debug_event(self::class, 'Skipping existing song ' . $song['file'], 5);
        } else {
            $album_artist_id = (isset($song['album_artist']))
                ? Artist::check($song['album_artist'], $song['mb_albumartistid'] ?? null)
                : null;
            $album_id         = Album::check($song['catalog'], $song['album'], $song['year'], $song['mbid'] ?? null, $song['mb_releasegroupid'] ?? null, $album_artist_id, $song['release_type'] ?? null, $song['release_status'] ?? null, $song['original_year'] ?? null, $song['barcode'] ?? null, $song['catalog_number'] ?? null, $song['version'] ?? null);
            $song['album_id'] = $album_id;
            $songId           = $this->insertSong($song);
            if (
                $songId !== false &&
                $this->getMetadataManager()->isCustomMetadataEnabled()
            ) {
                $songObj = new Song($songId);
                $this->addMetadata($songObj, $song);
            }

            $this->updateUi('add', ++$this->addedSongs, $song);
        }
    }

    /**
     * Add the song to the DB
     * @param array $song
     */
    protected function insertSong($song): ?int
    {
        $inserted = Song::insert($song);
        if ($inserted) {
            debug_event(self::class, 'Adding song ' . $song['file'], 5);
            flush();

            return $inserted;
        }

        debug_event(self::class, 'Insert failed for ' . $song['file'], 1);
        if (!defined('SSE_OUTPUT') && !defined('CLI') && !defined('API')) {
            /* HINT: filename (file path) */
            AmpError::add('general', T_('Unable to add Song - %s'), $song['file']);
            echo AmpError::display('general');
        }

        return null;
    }

    /**
     * verify_catalog_proc
     */
    public function verify_catalog_proc(?int $limit = 0, ?Interactor $interactor = null): int
    {
        debug_event(self::class, 'Verify: Starting on ' . $this->name, 5);
        set_time_limit(0);

        $date = time();
        /** @var Handler $parser */
        $parser = $this->getParser();
        /** @see self::verifySong() */
        $parser->setHandler($this, 'verifySong');
        $parser->start($parser->getTimedCommand($this->listCommand, 'mtime', $this->last_update));
        $this->updateUi('verify', $this->verifiedSongs, null, true);
        $this->update_last_update($date);

        return $this->verifiedSongs;
    }

    /**
     * Verify and update a song
     * @param array<string, scalar> $beetsSong
     */
    public function verifySong(array $beetsSong): void
    {
        $song                  = new Song($this->getIdFromPath((string) $beetsSong['file']));
        $beetsSong['album_id'] = $song->album;

        if ($song->isNew() === false) {
            $song->update($beetsSong);
            if ($this->getMetadataManager()->isCustomMetadataEnabled()) {
                $this->updateMetadata($song, $beetsSong);
            }
            $this->updateUi('verify', ++$this->verifiedSongs, $beetsSong);
        }
    }

    /**
     * Cleans the Catalog.
     * This way is a little fishy, but if we start beets for every single file, it may take horribly long.
     * So first we get the difference between our and the beets database and then clean up the rest.
     */
    public function clean_catalog_proc(?Interactor $interactor = null): int
    {
        /** @var Handler $parser */
        $parser      = $this->getParser();
        $this->songs = $this->getAllSongfiles();
        /** @see self::removeFromDeleteList() */
        $parser->setHandler($this, 'removeFromDeleteList');
        $parser->start($this->listCommand);
        $count = count($this->songs);
        if ($count > 0) {
            $this->deleteSongs($this->songs);
        }

        $metadataManager = $this->getMetadataManager();

        if ($metadataManager->isCustomMetadataEnabled()) {
            $metadataManager->collectGarbage();
        }
        $this->updateUi('clean', $this->cleanCounter, null, true);

        return (int)$count;
    }

    /**
     * @return string[]
     */
    public function check_catalog_proc(?Interactor $interactor = null): array
    {
        return [];
    }

    /**
     * move_catalog_proc
     * This function updates the file path of the catalog to a new location (unsupported)
     */
    public function move_catalog_proc(string $new_path): bool
    {
        return false;
    }

    /**
     * cache_catalog_proc
     */
    public function cache_catalog_proc(): bool
    {
        return false;
    }

    /**
     * Remove a song from the "to be deleted"-list if it was found.
     * @param array $song
     */
    public function removeFromDeleteList($song): void
    {
        $key = array_search($song['file'], $this->songs, true);
        $this->updateUi('clean', ++$this->cleanCounter, $song);
        if ($key) {
            unset($this->songs[$key]);
        }
    }

    /**
     * Delete Song from DB
     * @param array $songs
     */
    protected function deleteSongs($songs): void
    {
        $ids = implode(',', array_keys($songs));
        $sql = "DELETE FROM `song` WHERE `id` IN ($ids)";
        Dba::write($sql);
    }

    /**
     * getIdFromPath
     */
    protected function getIdFromPath(string $path): int
    {
        $sql        = "SELECT `id` FROM `song` WHERE `file` = ?";
        $db_results = Dba::read($sql, [$path]);
        $row        = Dba::fetch_row($db_results);
        if (empty($row)) {
            return 0;
        }

        return (int)$row[0];
    }

    /**
     * Get all songs from the DB into a array
     * @return string[] (id => file)
     */
    public function getAllSongfiles(): array
    {
        $sql        = "SELECT `id`, `file` FROM `song` WHERE `catalog` = ?";
        $db_results = Dba::read($sql, [$this->id]);

        $files = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $files[(int)$row['id']] = (string)$row['file'];
        }

        return $files;
    }

    /**
     * Assembles a virtual Path. Mostly just to looks nice in the UI.
     * @param array $song
     */
    protected function getVirtualSongPath($song): string
    {
        return implode('/', [
            $song['artist'],
            $song['album'],
            $song['title'],
        ]);
    }

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description(): string
    {
        return $this->description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type(): string
    {
        return $this->type;
    }

    /**
     * get_rel_path
     */
    public function get_rel_path(string $file_path): string
    {
        return '';
    }

    /**
     * @deprecated inject dependency
     */
    private function getMetadataManager(): MetadataManagerInterface
    {
        global $dic;

        return $dic->get(MetadataManagerInterface::class);
    }
}
