<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Beets Catalog Class
 *
 * This class handles all actual work in regards to local Beets catalogs.
 *
 */
class Catalog_beets extends Beets\Catalog
{
    protected $version     = '000001';
    protected $type        = 'beets';
    protected $description = 'Beets Catalog';

    protected $listCommand = 'ls';

    /**
     *
     * @var string Beets Database File
     */
    protected $beetsdb;

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help()
    {
        $help = "<ul>" .
                "<li>Fetch songs from beets command over CLI.</li>" .
                "<li>You have to ensure that the beets command ( beet ), the music directories and the Database file are accessable by the Webserver.</li></ul>";

        return $help;
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed()
    {
        $sql        = "SHOW TABLES LIKE 'catalog_beets'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `catalog_beets` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
                "`beetsdb` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
                "`catalog_id` INT( 11 ) NOT NULL" .
                ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        Dba::query($sql);

        return true;
    }

    public function catalog_fields()
    {
        $fields['beetsdb'] = array('description' => T_('Beets Database File'), 'type' => 'text');

        return $fields;
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     */
    public static function create_type($catalog_id, $data)
    { // TODO: This Method should be required / provided by parent
        $beetsdb = $data['beetsdb'];

        if (preg_match('/^[\s]+$/', $beetsdb)) {
            AmpError::add('general', T_('Error: Beets selected, but no Beets DB File provided'));

            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        $selectSql  = 'SELECT `id` FROM `catalog_beets` WHERE `beetsdb` = ?';
        $db_results = Dba::read($selectSql, array($beetsdb));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate uri ' . $beetsdb, 1);
            AmpError::add('general', sprintf(T_('Error: Catalog with %s already exists'), $beetsdb));

            return false;
        }

        $insertSql = 'INSERT INTO `catalog_beets` (`beetsdb`, `catalog_id`) VALUES (?, ?)';
        Dba::write($insertSql, array($beetsdb, $catalog_id));

        return true;
    }

    protected function getParser()
    {
        return new Beets\CliHandler();
    }

    /**
     * Check if a song was added before
     * @param array $song
     * @return boolean
     */
    public function checkSong($song)
    {
        $date = new DateTime($song['added']);
        if ($date->format('U') < $this->last_add) {
            debug_event('Check', 'Skipping ' . $song['file'] . ' File modify time before last add run', '3');

            return true;
        }

        return (boolean) $this->getIdFromPath($song['file']);
    }

    public function getBeetsDb()
    {
        return $this->beetsdb;
    }

    public function format()
    {
        parent::format();
        $this->f_info      = $this->beetsdb;
        $this->f_full_info = $this->f_info;
    }
}
