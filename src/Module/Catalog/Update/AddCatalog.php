<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Catalog\Update;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\System\Dba;
use Ampache\Repository\UserRepositoryInterface;
use PDOStatement;

final class AddCatalog extends AbstractCatalogUpdater implements AddCatalogInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer,
    ) {
        $this->configContainer = $configContainer;
    }
    public function add(
        Interactor $interactor,
        string $catalogName,
        string $catalogPath,
        string $catalogType,
        string $mediaType,
        string $filePattern,
        string $folderPattern
    ): void {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return;
        }

        ob_end_flush();
        $data = array(
            'name' => $catalogName,
            'path' => $catalogPath,
            'type' => $catalogType,
            'gather_media' => $mediaType,
            'rename_pattern' => $filePattern,
            'sort_pattern' => $folderPattern
        );
        $catalog_id = Catalog::create($data);

        if (!$catalog_id) {
            $buffer = ob_get_contents();

            ob_end_clean();

            $interactor->info(
                $this->cleanBuffer($buffer),
                true
            );
            $interactor->error(
                T_('Failed to create the catalog, check the debug logs'),
                true
            );

            return;
        }

        // Add catalog to filter table
        Catalog::add_catalog_filter_group_map($catalog_id);

        $buffer = ob_get_contents();

        ob_end_clean();

        $interactor->info(
            $this->cleanBuffer($buffer),
            true
        );
        $interactor->info(
            T_('Catalog created'),
            true
        );
    }
}
