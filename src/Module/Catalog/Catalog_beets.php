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

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Module\Beets\Catalog;
use Ampache\Module\Beets\CliHandler;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Dba;
use DateTime;
use Exception;

/**
 * This class handles all actual work in regards to local Beets catalogs.
 */
class Catalog_beets extends Catalog
{
    protected string $version     = '000001';
    protected string $type        = 'beets';
    protected string $description = 'Beets Catalog';
    protected string $listCommand = 'ls';

    protected string $beetsdb = '';

    public int $catalog_id;

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help(): string
    {
        return "<ul><li>Fetch songs from beets command over CLI.</li><li>You have to ensure that the beets command ( beet ), the music directories and the Database file are accessible by the Webserver.</li></ul>";
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'catalog_beets'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install(): bool
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $sql = "CREATE TABLE `catalog_beets` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `beetsdb` VARCHAR(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
        Dba::query($sql);

        return true;
    }

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string}
     * >
     */
    public function catalog_fields(): array
    {
        $fields = [];

        $fields['beetsdb'] = ['description' => T_('Beets Database File'), 'type' => 'text'];

        return $fields;
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     * @param string $catalog_id
     * @param array $data
     */
    public static function create_type($catalog_id, $data): bool
    {
        // TODO: This Method should be required / provided by parent
        $beetsdb = $data['beetsdb'];

        if (preg_match('/^[\s]+$/', $beetsdb)) {
            AmpError::add('general', T_('Beets Catalog was selected, but no Beets DB file was provided'));

            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        $selectSql  = 'SELECT `id` FROM `catalog_beets` WHERE `beetsdb` = ?';
        $db_results = Dba::read($selectSql, [$beetsdb]);

        if (Dba::num_rows($db_results)) {
            debug_event(self::class, 'Cannot add catalog with duplicate uri ' . $beetsdb, 1);
            AmpError::add('general', sprintf(T_('This path belongs to an existing Beets Catalog: %s'), $beetsdb));

            return false;
        }

        $insertSql = 'INSERT INTO `catalog_beets` (`beetsdb`, `catalog_id`) VALUES (?, ?)';
        Dba::write($insertSql, [$beetsdb, $catalog_id]);

        return true;
    }

    /**
     * getParser
     */
    protected function getParser(): CliHandler
    {
        return new CliHandler($this);
    }

    /**
     * Check if a song was added before
     * @param array $song
     * @return bool
     * @throws Exception
     */
    public function checkSong($song): bool
    {
        $date       = new DateTime($song['added']);
        $last_added = date("Y-m-d H:i:s", $this->last_add);
        $last_date  = new DateTime($last_added);
        if (date_diff($date, $last_date) < 0) {
            debug_event(self::class, 'Skipping ' . $song['file'] . ' File modify time before last add run', 3);

            return true;
        }

        return (bool)$this->getIdFromPath($song['file']);
    }

    /**
     * get_path
     * This returns the current catalog path/uri
     */
    public function get_path(): string
    {
        return $this->beetsdb;
    }

    public function format(): void
    {
        $this->f_info = $this->beetsdb;
    }
}
