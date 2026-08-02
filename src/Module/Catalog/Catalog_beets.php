<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Module\Beets\Catalog;
use Ampache\Module\Beets\CliHandler;
use Ampache\Module\System\AmpError;
use DateTime;
use Exception;
use Override;

/**
 * This class handles all actual work in regards to local Beets catalogs.
 */
class Catalog_beets extends Catalog
{
    protected string $beetsdb = '';

    #[Override]
    protected string $description = 'Beets Catalog';

    #[Override]
    protected string $listCommand = 'ls';

    #[Override]
    protected string $type = 'beets';

    #[Override]
    protected string $version = '000001';

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     * @param array{
     *     beetsdb?: string,
     * } $data
     */
    public static function create_type(int $catalog_id, array $data): bool
    {
        // TODO: This Method should be required / provided by parent
        $beetsdb = $data['beetsdb'] ?? '';

        if (preg_match('/^[\s]+$/', $beetsdb)) {
            AmpError::add('general', T_('Beets Catalog was selected, but no Beets DB file was provided'));

            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        $catalogRepository = self::getCatalogRepository();
        if ($catalogRepository->subTypeValueExists(CatalogTypeEnum::BEETS, 'beetsdb', $beetsdb)) {
            debug_event(self::class, 'Cannot add catalog with duplicate uri ' . $beetsdb, 1);
            AmpError::add('general', sprintf(T_('This path belongs to an existing Beets Catalog: %s'), $beetsdb));

            return false;
        }

        return $catalogRepository->insertSubType(CatalogTypeEnum::BEETS, ['beetsdb' => $beetsdb], $catalog_id);
    }

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string}
     * >
     */
    public function catalog_fields(): array
    {
        return ['beetsdb' => ['description' => T_('Beets Database File'), 'type' => 'text']];
    }

    /**
     * Check if a song was added before
     * @throws Exception
     */
    public function checkSong(array $song): bool
    {
        $date       = new DateTime($song['added']);
        $last_added = date("Y-m-d H:i:s", $this->last_add);
        $last_date  = new DateTime($last_added);
        if ($date < $last_date) {
            debug_event(self::class, 'Skipping ' . $song['file'] . ' File modify time before last add run', 3);

            return true;
        }

        return (bool) $this->getIdFromPath($song['file']);
    }

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help(): string
    {
        return "<ul><li>Fetch songs from beets command over CLI.</li><li>You have to ensure that the beets command ( beet ), the music directories and the Database file are accessible by the Webserver.</li></ul>";
    }

    /**
     * get_f_info
     */
    public function get_f_info(): string
    {
        return $this->beetsdb;
    }

    /**
     * get_path
     * This returns the current catalog path/uri
     */
    public function get_path(): string
    {
        return $this->beetsdb;
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install(): bool
    {
        self::getCatalogRepository()->createSubTypeTable(CatalogTypeEnum::BEETS, ['beetsdb' => 'VARCHAR(255)']);

        return true;
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        return self::getCatalogRepository()->subTypeTableExists(CatalogTypeEnum::BEETS);
    }

    /**
     * getParser
     */
    protected function getParser(): CliHandler
    {
        return new CliHandler($this);
    }
}
