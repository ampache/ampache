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

declare(strict_types=0);

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddToAllCatalogsAction extends AbstractCatalogAction
{
    public const REQUEST_KEY = 'add_to_all_catalogs';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct($ui);
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
    }

    /**
     * @param int[] $catalogIds
     */
    protected function handle(
        ServerRequestInterface $request,
        array $catalogIds
    ): ?ResponseInterface {
        catalog_worker('add_to_all_catalogs');
        $this->ui->showConfirmation(
            T_('Catalog update process has started'),
            '',
            sprintf('%s/admin/catalog.php', $this->configContainer->getWebPath()),
            0,
            'confirmation',
            false
        );

        return null;
    }
}
