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
use Ampache\Module\Beets\JsonHandler;
use Ampache\Module\System\AmpError;
use Override;

/**
 * This class handles all actual work in regards to remote Beets catalogs.
 */
class Catalog_beetsremote extends Catalog
{
    #[Override]
    protected string $description = 'Beets Remote Catalog';

    #[Override]
    protected string $listCommand = 'item/query';

    #[Override]
    protected string $type = 'beetsremote';

    protected string $uri = '';

    #[Override]
    protected string $version = '000001';

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
    public static function create_type(int $catalog_id, array $data): bool
    {
        // TODO: This Method should be required / provided by parent
        $uri = $data['uri'] ?? '';

        if (!str_starts_with($uri, 'http://') && !str_starts_with($uri, 'https://')) {
            AmpError::add('general', T_('Remote Catalog type was selected, but the path is not a URL'));

            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        // the duplicate check named `catalog_beets` and a column it does not have, so it never found one
        $catalogRepository = self::getCatalogRepository();
        if ($catalogRepository->subTypeValueExists(CatalogTypeEnum::BEETSREMOTE, 'uri', $uri)) {
            debug_event('beetsremote.catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            AmpError::add('general', sprintf(T_('This path belongs to an existing Beets Catalog: %s'), $uri));

            return false;
        }

        return $catalogRepository->insertSubType(CatalogTypeEnum::BEETSREMOTE, ['uri' => $uri], $catalog_id);
    }

    /**
     * @return array<
     *     string,
     *     array{description: string, type: string}
     * >
     */
    public function catalog_fields(): array
    {
        return ['uri' => ['description' => T_('Beets Server URI'), 'type' => 'url']];
    }

    /**
     * Check if a song was added before
     */
    public function checkSong(array $song): bool
    {
        if ($song['added'] < $this->last_add) {
            debug_event('beetsremote.catalog', 'Skipping ' . $song['file'] . ' File modify time before last add run', 3);

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
        return "<ul><li>Install Beets web plugin: http://beets.readthedocs.org/en/latest/plugins/web.html</li><li>Start Beets web server</li><li>Specify URI including port (like http://localhost:8337). It will be shown when starting Beets web in console.</li></ul>";
    }

    /**
     * get_f_info
     */
    public function get_f_info(): string
    {
        return $this->uri;
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
     * install
     * This function installs the remote catalog
     */
    public function install(): bool
    {
        self::getCatalogRepository()->createSubTypeTable(CatalogTypeEnum::BEETSREMOTE, ['uri' => 'VARCHAR(255)']);

        return true;
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed(): bool
    {
        return self::getCatalogRepository()->subTypeTableExists(CatalogTypeEnum::BEETSREMOTE);
    }

    /**
     * Get the parser class like CliHandler or JsonHandler
     */
    protected function getParser(): JsonHandler
    {
        return new JsonHandler($this->uri);
    }
}
