<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

final class AlbumDiskAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'album_disk';

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
        $browse->set_type(self::REQUEST_KEY);
        $browse->set_simple_browse(true);

        $this->ui->showHeader();

        $this->ui->show('show_form_browse.inc.php');

        // Browser is able to save page on current session. Only applied to main menus.
        $browse->set_update_session(true);

        $year_sort = ($this->configContainer->get('use_original_year')) ? "original_year" : "year";
        $sort_type = $this->configContainer->get('album_sort');
        switch ($sort_type) {
            case 'name_asc':
                $browse->set_sort('name', 'ASC');
                break;
            case 'name_desc':
                $browse->set_sort('name', 'DESC');
                break;
            case 'year_asc':
                $browse->set_sort($year_sort, 'ASC');
                break;
            case 'year_desc':
                $browse->set_sort($year_sort, 'DESC');
                break;
            case 'default':
            default:
                $browse->set_sort('name_' . $year_sort, 'ASC');
        }
        if (array_key_exists('catalog', $_SESSION)) {
            $browse->set_filter('catalog', (int)$_SESSION['catalog']);
        }
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE)) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->update_browse_from_session(); // Update current index depending on what is in session.
        $browse->show_objects();

        $browse->store();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
