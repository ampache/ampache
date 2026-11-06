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

namespace Ampache\Gui\Catalog;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Catalog\Catalog;
use Override;

/**
 * One row of the catalog management browse.
 */
final class CatalogRowView extends AbstractView
{
    public function __construct(
        private readonly string $adminPath,
        private readonly Catalog $catalog,
        private readonly bool $mayDisable,
    ) {}

    /**
     * The actions that only make sense once a catalog has been set up.
     *
     * @return array<string, string>
     */
    public function getActions(): array
    {
        $actions = [];
        if ($this->catalog->isReady()) {
            $actions = [
                'add_to_catalog' => T_('Add'),
                'update_catalog' => T_('Verify'),
                'clean_catalog' => T_('Clean'),
                'scan_catalog_folders' => T_('Scan Folders'),
                'full_service' => T_('Update'),
                'gather_media_art' => T_('Gather Art'),
                'import_to_catalog' => T_('Import'),
                'update_file_tags' => T_('Update File Tags'),
                'garbage_collect' => T_('Garbage Collection'),
            ];
        }

        $actions['show_delete_catalog'] = T_('Delete');

        return $actions;
    }

    public function getActionUrl(): string
    {
        return $this->adminPath . '/catalog.php';
    }

    public function getCatalog(): Catalog
    {
        return $this->catalog;
    }

    /**
     * @return array{id: string, symbol: string, label: string}
     */
    public function getFlipState(): array
    {
        return [
            'id' => 'button_flip_state_' . $this->catalog->id,
            'symbol' => $this->catalog->enabled ? 'unpublished' : 'check_circle',
            'label' => $this->catalog->enabled ? T_('Disable') : T_('Enable'),
        ];
    }

    public function getMakeReadyUrl(): string
    {
        return $this->adminPath . '/catalog.php?action=add_to_catalog&catalogs[]=' . $this->catalog->id;
    }

    public function isReady(): bool
    {
        return $this->catalog->isReady();
    }

    public function mayDisable(): bool
    {
        return $this->mayDisable;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('catalog_row.phtml');
    }
}
