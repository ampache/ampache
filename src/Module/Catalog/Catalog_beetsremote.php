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
use Ampache\Module\Beets\JsonHandler;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Dba;

/**
 * This class handles all actual work in regards to remote Beets catalogs.
 */
class Catalog_beetsremote extends Catalog
{
    protected string $version     = '000001';
    protected string $type        = 'beetsremote';
    protected string $description = 'Beets Remote Catalog';
    protected string $listCommand = 'item/query';

    protected string $uri = '';

    public int $catalog_id;

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help(): string
    {
        return "<ul><li>Install Beets web plugin: http://beets.readthedocs.org/en/latest/plugins/web.html</li><li>Start Beets web server</li><li>Specify URI including port (like http://localhost:8337). It will be shown when starting Beets web in console.</li></ul>";
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        $sql        = "SHOW TABLES LIKE 'catalog_beetsremote'";
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

        $sql = "CREATE TABLE `catalog_beetsremote` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uri` VARCHAR(255) COLLATE $collation NOT NULL, `catalog_id` INT(11) NOT NULL) ENGINE = $engine DEFAULT CHARSET=$charset COLLATE=$collation";
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

        $fields['uri'] = ['description' => T_('Beets Server URI'), 'type' => 'url'];

        return $fields;
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     * @param array{
     *     uri?: string,
     * } $data
     */
    public static function create_type(string $catalog_id, array $data): bool
    {
        // TODO: This Method should be required / provided by parent
        $uri = $data['uri'] ?? '';

        if (substr($uri, 0, 7) != 'http://' && substr($uri, 0, 8) != 'https://') {
            AmpError::add('general', T_('Remote Catalog type was selected, but the path is not a URL'));

            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        $selectSql  = 'SELECT `id` FROM `catalog_beets` WHERE `uri` = ?';
        $db_results = Dba::read($selectSql, [$uri]);

        if (Dba::num_rows($db_results)) {
            debug_event('beetsremote.catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            AmpError::add('general', sprintf(T_('This path belongs to an existing Beets Catalog: %s'), $uri));

            return false;
        }

        $insertSql = 'INSERT INTO `catalog_beetsremote` (`uri`, `catalog_id`) VALUES (?, ?)';
        Dba::write($insertSql, [$uri, $catalog_id]);

        return true;
    }

    /**
     * Get the parser class like CliHandler or JsonHandler
     */
    protected function getParser(): JsonHandler
    {
        return new JsonHandler($this->uri);
    }

    /**
     * Check if a song was added before
     * @param array $song
     */
    public function checkSong($song): bool
    {
        if ($song['added'] < $this->last_add) {
            debug_event('beetsremote.catalog', 'Skipping ' . $song['file'] . ' File modify time before last add run', 3);

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
        return $this->uri;
    }

    /**
     * get_f_info
     */
    public function get_f_info(): string
    {
        return $this->uri;
    }
}
