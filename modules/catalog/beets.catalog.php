<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

/**
 * Beets Catalog Class
 *
 * This class handles all actual work in regards to local Beets catalogs.
 *
 */
class Catalog_beets extends Catalog {

    private $version = '000001';
    private $type = 'beets';
    private $description = 'Beets Catalog';

    /**
     *
     * @var string Beets Database File 
     */
    private $beetsdb;
    private $addedSongs = 0;
    private $verifiedSongs = 0;
    private $songs;

    /**
     * get_description
     * This returns the description of this catalog
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * get_version
     * This returns the current version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * get_type
     * This returns the current catalog type
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help() {
        $help = "<ul>" .
                "<li></li>" .
                "<li></li></ul>";
        return $help;
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed() {
        $sql = "SHOW TABLES LIKE 'catalog_beets'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install() {
        $sql = "CREATE TABLE `catalog_beets` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
                "`beetsdb` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
                "`catalog_id` INT( 11 ) NOT NULL" .
                ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        Dba::query($sql);

        return true;
    }

    public function catalog_fields() {
        $fields['beetsdb'] = array('description' => T_('Beets Database File'), 'type' => 'textbox');

        return $fields;
    }

    /**
     * Doesent seems like we need this...
     * @param string $file_path
     */
    public function get_rel_path($file_path) {
        
    }

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
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     */
    public static function create_type($catalog_id, $data) { // TODO: This Method should be required / provided by parent
        $beetsdb = $data['beetsdb'];

        if (preg_match('/^[\s]+$/', $beetsdb)) {
            Error::add('general', T_('Error: Beets selected, but no Beets DB File provided'));
            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        $selectSql = 'SELECT `id` FROM `catalog_beets` WHERE `beetsdb` = ?';
        $db_results = Dba::read($selectSql, array($beetsdb));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate uri ' . $beetsdb, 1);
            Error::add('general', sprintf(T_('Error: Catalog with %s already exists'), $beetsdb));
            return false;
        }

        $insertSql = 'INSERT INTO `catalog_beets` (`beetsdb`, `catalog_id`) VALUES (?, ?)';
        Dba::write($insertSql, array($beetsdb, $catalog_id));
        return true;
    }

    /**
     * format
     *
     * This makes the object human-readable.
     */
    public function format() {
        parent::format();
    }

    public function prepare_media($media) {
        debug_event('play', 'Started remote stream - ' . $media->file, 5);
        return $media;
    }

    public function addSong($song) {
        $song['catalog'] = $this->id;

        if ($this->checkSong($song)) {
            debug_event('beets_catalog', 'Skipping existing song ' . $song['file'], 5);
        } else {
            if ($this->insertSong($song)) {
                $this->updateCounter($song);
            }
        }
    }

    private function insertSong($song) {
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
     * Check if a song was added before
     * @param array $song
     * @return boolean
     */
    public function checkSong($song) {
        $date = new DateTime($song['added']);
        if ($date->format('U') < $this->last_add) {
            debug_event('Check', 'Skipping ' . $song['file'] . ' File modify time before last add run', '3');
            return true;
        }

        return (boolean) $this->getIdFromPath($song['file']);
    }

    public function add_to_catalog($options = null) {
        require AmpConfig::get('prefix') . '/templates/show_adds_catalog.inc.php';
        flush();
        set_time_limit(0);

        UI::show_box_top(T_('Running Beets Update') . '. . .');
        $parser = new Beets\CliHandler();
        $parser->setHandler($this, 'addSong');
        $parser->start('ls');

        UI::show_box_bottom();
    }

    /**
     * Cleans the Catalog.
     * This way is a little fishy, but if we start beets for every single file, it may take horribly long.
     * So first we get the difference between our and the beets database and then clean up the rest.
     * @return integer
     */
    public function clean_catalog_proc() {
        $parser = new Beets\CliHandler();
        $this->songs = $this->getAllSongfiles();
        $parser->setHandler($this, 'removeFromDeleteList');
        $parser->start('ls');
        $count = count($this->songs);
        $this->deleteSongs($this->songs);
        return $count;
    }

    protected function deleteSongs($songs) {
        $ids = implode(',', array_keys($songs));
        $sql = "DELETE FROM `song` WHERE `id` IN " .
                '(' . $ids . ')';
        Dba::write($sql);
    }

    public function removeFromDeleteList($song) {
        $key = array_search($song['file'], $this->songs, true);
        if ($key) {
            unset($this->songs[$key]);
        }
    }

    public function verify_catalog_proc() {
        debug_event('verify', 'Starting on ' . $this->name, 5);
        set_time_limit(0);

        $parser = new Beets\CliHandler();
        $parser->setHandler($this, 'verifySong');
        $parser->start('ls');
    }

    public function verifySong($beetsSong) {
        $song = new Song($this->getIdFromPath($beetsSong['file']));
        if ($song->id) {
            $song->update($beetsSong);
            $this->verifiedSongs++;
            $this->verifyUpdateUi($beetsSong);
        }
    }

    protected function verifyUpdateUi($song) {
        if (UI::check_ticker()) {
            UI::update_text('verify_count_' . $this->id, $this->verifiedSongs);
            UI::update_text('verify_dir_' . $this->id, $song->file);
        }
    }

    public function getBeetsDb() {
        return $this->beetsdb;
    }

    protected function updateCounter($song) {
        $this->addedSongs++;
        UI::update_text('add_count_' . $this->id, $this->addedSongs);
        UI::update_text('add_dir_' . $this->id, scrub_out($song['file']));
    }

    protected function getIdFromPath($path) {
        $sql = "SELECT `id` FROM `song` WHERE `file` = ?";
        $db_results = Dba::read($sql, array($path));

        $row = Dba::fetch_row($db_results);
        return $row[0];
    }

    public function getAllSongfiles() {
        $sql = "SELECT `id`, `file` FROM `song` WHERE `catalog` = ?";
        $db_results = Dba::read($sql, array($this->id));

        $files = array();
        while ($row = Dba::fetch_row($db_results)) {
            $files[$row[0]] = $row[1];
        }
        return $files;
    }

}
