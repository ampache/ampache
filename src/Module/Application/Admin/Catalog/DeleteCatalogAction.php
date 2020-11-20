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

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\Catalog;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteCatalogAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete_catalog';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!Access::check('interface', 75)) {
            Ui::access_denied();

            return null;
        }
        if (AmpConfig::get('demo_mode')) {
            Ui::access_denied();

            return null;
        }

        if (!Core::form_verify('delete_catalog')) {
            Ui::access_denied();

            return null;
        }

        $catalogs = isset($_REQUEST['catalogs']) ? filter_var_array($_REQUEST['catalogs'], FILTER_SANITIZE_STRING) : array();
        $deleted  = true;
        /* Delete the sucker, we don't need to check perms as thats done above */
        foreach ($catalogs as $catalog_id) {
            $deleted = Catalog::delete($catalog_id);
            if (!$deleted) {
                break;
            }
        }

        $this->ui->showHeader();

        $next_url = sprintf('%s/admin/catalog.php', $this->configContainer->getWebPath());
        if ($deleted) {
            show_confirmation(T_('No Problem'), T_('The Catalog has been deleted'), $next_url);
        } else {
            show_confirmation(T_("There Was a Problem"), T_("There was an error deleting this Catalog"), $next_url);
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
