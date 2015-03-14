<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace Beets;

use AmpConfig;
use UI;
use Dba;
use Song;

/**
 * Catalog parent for local and remote beets catalog
 *
 * @author raziel
 */
abstract class Catalog extends \Catalog
{
    /**
     * Added Songs counter
     * @var integer
     */
    protected $addedSongs = 0;

    /**
     * Verified Songs counter
     * @var integer
     */
    protected $verifiedSongs = 0;

    /**
     * Array of all songs
     * @var array
     */
    protected $songs = array();

    /**
     *
     * @var string command which provides the list of all songs
     */
    protected $listCommand;

    /**
     * Constructor
     *
     * Catalog class constructor, pulls catalog information
     */
    public function __construct($catalog_id = null) { // TODO: Basic constructer should be provided from parent
        if ($catalog_id) {
            $this->id = intval($catalog_id);
            $info = $this->get_info($catalog_id);

            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     *
     * @param \media $media
     * @return \media
     */
    public function prepare_media($media)
    {
        debug_event('play', 'Started remote stream - ' . $media->file, 5);
        return $media;
    }

    /**
     *
     * @param string $prefix Prefix like add, updated, verify and clean
     * @param integer $count song count
     * @param array $song Song array
     * @param boolean $ignoreTicker ignoring the ticker for the last update
     */
    protected function updateUi($prefix, $count, $song = null, $ignoreTicker = false)
    {
        if ($ignoreTicker || UI::check_ticker()) {
            UI::update_text($prefix . '_count_' . $this->id, $count);
            if (isset($song)) {
                UI::update_text($prefix . '_dir_' . $this->id, scrub_out($this->getVirtualSongPath($song)));
            }
        }
    }

    /**
     * Get the parser class like CliHandler or JsonHandler
     */
    abstract protected function getParser();

    /**
     * Adds new songs to the catalog
     * @param array $options
     */
    public function add_to_catalog($options = null)
    {
        if (!defined('SSE_OUTPUT')) {
            require AmpConfig::get('prefix') . '/templates/show_adds_catalog.inc.php';
            flush();
        }
        set_time_limit(0);
        if (!defined('SSE_OUTPUT')) {
            UI::show_box_top(T_('Running Beets Update') . '. . .');
        }
        $parser = $this->getParser();
        $parser->setHandler($this, 'addSong');
        $parser->start($parser->getTimedCommand($this->listCommand, 'added', $this->last_add));
        $this->updateUi('add', $this->addedSongs, null, true);
        $this->update_last_add();

        if (!defined('SSE_OUTPUT')) {
            UI::show_box_bottom();
        }
    }

    /**
     * Add $song to ampache if it isn't already
     * @param array $song
     */
    public function addSong($song)
    {
        $song['catalog'] = $this->id;

        if ($this->checkSong($song)) {
            debug_event('beets_catalog', 'Skipping existing song ' . $song['file'], 5);
        } else {
            if ($this->insertSong($song)) {
                $this->updateUi('add', ++$this->addedSongs, $song);
            }
        }
    }

    /**
     * Add the song to the DB
     * @param array $song
     * @return integer
     */
    protected function insertSong($song)
    {
        $inserted = Song::insert($song);
        if ($inserted) {
            debug_event('beets_catalog', 'Adding song ' . $song['file'], 5, 'ampache-catalog');
        } else {
            debug_event('beets_catalog', 'Insert failed for ' . $song['file'], 1);
            Error::add('general', T_('Unable to Insert Song - %s'), $song['file']);
            Error::display('general');
        }
        flush();
        return $inserted;
    }

    /**
     * Verify songs.
     * @return array
     */
    public function verify_catalog_proc()
    {
        debug_event('verify', 'Starting on ' . $this->name, 5);
        set_time_limit(0);

        /* @var $parser Handler */
        $parser = $this->getParser();
        $parser->setHandler($this, 'verifySong');
        $parser->start($parser->getTimedCommand($this->listCommand, 'mtime', $this->last_update));
        $this->updateUi('verify', $this->verifiedSongs, null, true);
        $this->update_last_update();
        return array('updated' => $this->verifiedSongs, 'total' => $this->verifiedSongs);
    }

    /**
     * Verify and update a song
     * @param array $beetsSong
     */
    public function verifySong($beetsSong)
    {
        $song = new Song($this->getIdFromPath($beetsSong['file']));
        if ($song->id) {
            $song->update($beetsSong);
            $this->updateUi('verify', ++$this->verifiedSongs, $beetsSong);
        }
    }

    /**
     * Cleans the Catalog.
     * This way is a little fishy, but if we start beets for every single file, it may take horribly long.
     * So first we get the difference between our and the beets database and then clean up the rest.
     * @return integer
     */
    public function clean_catalog_proc()
    {
        $parser = $this->getParser();
        $this->songs = $this->getAllSongfiles();
        $parser->setHandler($this, 'removeFromDeleteList');
        $parser->start($this->listCommand);
        $count = count($this->songs);
        $this->deleteSongs($this->songs);
        $this->updateUi('clean', $this->cleanCounter, null, true);
        return $count;
    }

    /**
     * Remove a song from the "to be deleted"-list if it was found.
     * @param array $song
     */
    public function removeFromDeleteList($song)
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
    protected function deleteSongs($songs)
    {
        $ids = implode(',', array_keys($songs));
        $sql = "DELETE FROM `song` WHERE `id` IN " .
                '(' . $ids . ')';
        Dba::write($sql);
    }

    /**
     *
     * @param string $path
     * @return integer|boolean
     */
    protected function getIdFromPath($path)
    {
        $sql = "SELECT `id` FROM `song` WHERE `file` = ?";
        $db_results = Dba::read($sql, array($path));

        $row = Dba::fetch_row($db_results);
        return isset($row) ? $row[0] : false;
    }

    /**
     * Get all songs from the DB into a array
     * @return array array(id => file)
     */
    public function getAllSongfiles()
    {
        $sql = "SELECT `id`, `file` FROM `song` WHERE `catalog` = ?";
        $db_results = Dba::read($sql, array($this->id));

        $files = array();
        while ($row = Dba::fetch_row($db_results)) {
            $files[$row[0]] = $row[1];
        }
        return $files;
    }

    /**
     * Assembles a virtual Path. Mostly just to looks nice in the UI.
     * @param array $song
     * @return string
     */
    protected function getVirtualSongPath($song)
    {
        return implode('/', array(
            $song['artist'],
            $song['album'],
            $song['title']
        ));
    }

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * Doesent seems like we need this...
     * @param string $file_path
     */
    public function get_rel_path($file_path)
    {
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format()
    {
        parent::format();
    }

}
