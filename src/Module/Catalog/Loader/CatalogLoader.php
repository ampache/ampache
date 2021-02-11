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

declare(strict_types=1);

namespace Ampache\Module\Catalog\Loader;

use Ampache\Module\Catalog\Catalog_beets;
use Ampache\Module\Catalog\Catalog_beetsremote;
use Ampache\Module\Catalog\Catalog_dropbox;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Module\Catalog\Catalog_remote;
use Ampache\Module\Catalog\Catalog_Seafile;
use Ampache\Module\Catalog\Catalog_soundcloud;
use Ampache\Module\Catalog\Catalog_subsonic;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Catalog;
use Psr\Log\LoggerInterface;

/**
 * @todo Database related stuff into repository
 */
final class CatalogLoader implements CatalogLoaderInterface
{
    private const CATALOG_TYPES = [
        'beets' => Catalog_beets::class,
        'beetsremote' => Catalog_beetsremote::class,
        'dropbox' => Catalog_dropbox::class,
        'local' => Catalog_local::class,
        'remote' => Catalog_remote::class,
        'seafile' => Catalog_Seafile::class,
        'soundcloud' => Catalog_soundcloud::class,
        'subsonic' => Catalog_subsonic::class,
    ];

    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Create a catalog by id
     *
     * @throws CatalogNotFoundException
     */
    public function byId(int $catalogId): Catalog
    {
        $sql        = 'SELECT `catalog_type`, `enabled` FROM `catalog` WHERE `id` = ?';
        $db_results = Dba::read($sql, [$catalogId]);

        if (Dba::num_rows($db_results) === 0) {
            throw new CatalogNotFoundException((string) $catalogId);
        }
        $result = Dba::fetch_assoc($db_results);

        return static::byType(
            $result['catalog_type'],
            $catalogId,
            (bool) $result['enabled']
        );
    }

    /**
     * Attempts to create a catalog by type
     *
     * @throws Exception\InvalidCatalogTypeException
     */
    public function byType(string $catalogType, int $catalogId = 0, bool $enabled = false): Catalog
    {
        if (!$catalogType) {
            throw new Exception\InvalidCatalogTypeException($catalogType);
        }

        $controller = static::CATALOG_TYPES[$catalogType] ?? null;

        if ($controller === null) {
            $this->logger->error(
                sprintf('Unable to load %s catalog type', $catalogType),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            throw new Exception\InvalidCatalogTypeException($catalogType);
        } // include
        if ($catalogId > 0) {
            $catalog = new $controller($catalogId);
        } else {
            $catalog = new $controller();
        }
        if (!($catalog instanceof Catalog)) {
            $this->logger->critical(
                $catalogType . ' not an instance of Catalog abstract, unable to load',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            throw new Exception\InvalidCatalogTypeException($catalogType);
        }
        $catalog->enabled = $enabled;

        return $catalog;
    }
}
