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

namespace Ampache\Gui\Admin;

use Ampache\Module\Catalog\Catalog;
use Override;

/**
 * The catalog backends an install can turn on.
 */
final class CatalogTypeTableView extends AbstractModuleTableView
{
    /**
     * @param array<string, class-string> $catalogTypes
     */
    public function __construct(
        string $adminPath,
        private readonly array $catalogTypes,
    ) {
        parent::__construct($adminPath);
    }

    #[Override]
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_type', 'label' => T_('Type')],
            ['class' => 'cel_description', 'label' => T_('Description')],
            ['class' => 'cel_version', 'label' => T_('Version')],
            ['class' => 'cel_action', 'label' => T_('Action')],
        ];
    }

    #[Override]
    protected function buildRows(): array
    {
        $rows = [];
        foreach ($this->catalogTypes as $key => $type) {
            $catalog = Catalog::create_catalog_type($key);
            if (!$catalog instanceof $type) {
                continue;
            }

            $action = $catalog->is_installed()
                ? ['confirm_uninstall_catalog_type', T_('Disable')]
                : ['confirm_install_catalog_type', T_('Activate')];

            $rows[] = [
                $this->e($catalog->get_type()),
                $this->e($catalog->get_description()),
                $this->e($catalog->get_version()),
                sprintf(
                    '<a href="%s/modules.php?action=%s&type=%s">%s</a>',
                    $this->e($this->getAdminPath()),
                    $action[0],
                    urlencode($catalog->get_type()),
                    $this->e($action[1])
                ),
            ];
        }

        return $rows;
    }
}
