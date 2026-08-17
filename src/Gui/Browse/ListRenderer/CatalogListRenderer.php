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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Catalog\CatalogRowView;
use Ampache\Module\Catalog\Catalog;
use Override;

/**
 * The catalog management browse.
 */
final class CatalogListRenderer extends AbstractBrowseListRenderer
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
    ) {}

    /**
     * @return list<Catalog>
     */
    public function getCatalogs(): array
    {
        $catalogs = [];
        foreach ($this->getObjectIds() as $objectId) {
            $catalog = Catalog::create_from_id($objectId);
            if ($catalog !== null) {
                $catalogs[] = $catalog;
            }
        }

        return $catalogs;
    }

    /**
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        return [
            ['class' => 'cel_catalog essential persist', 'label' => T_('Name')],
            ['class' => 'cel_info essential', 'label' => T_('Path')],
            ['class' => 'cel_lastverify optional', 'label' => T_('Last Verify')],
            ['class' => 'cel_lastadd optional', 'label' => T_('Last Add')],
            ['class' => 'cel_lastclean optional', 'label' => T_('Last Clean')],
            ['class' => 'cel_action cel_action_text essential', 'label' => T_('Actions')],
        ];
    }

    public function renderRow(Catalog $catalog): string
    {
        return new CatalogRowView(
            $this->configContainer->getWebPath('/admin'),
            $catalog,
            (bool) $this->configContainer->get('catalog_disable')
        )->render();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/catalogs.phtml');
    }
}
