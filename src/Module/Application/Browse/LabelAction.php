<?php

declare(strict_types=0);

/**
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

namespace Ampache\Module\Application\Browse;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LabelAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'label';

    private ModelFactoryInterface $modelFactory;

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory    = $modelFactory;
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        session_start();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(static::REQUEST_KEY);
        $browse->set_simple_browse(true);

        $this->ui->showHeader();

        $this->ui->show('show_form_browse.inc.php');

        // Browser is able to save page on current session. Only applied to main menus.
        $browse->set_update_session(true);

        if (array_key_exists('catalog', $_SESSION)) {
            $browse->set_filter('catalog', $_SESSION['catalog']);
        }
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE)) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_sort('name', 'ASC');
        $browse->update_browse_from_session();
        $browse->show_objects();

        $browse->store();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
