<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=1);

namespace Ampache\Module\Application\DemocraticPlayback;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowPlaylistAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_playlist';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        /* Make sure they have access to this */
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_DEMOCRATIC_PLAYBACK) === false) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();
        $democratic = Democratic::get_current_playlist();
        if (!$democratic->id) {
            require_once Ui::find_template('show_democratic.inc.php');

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $democratic->set_parent();
        $democratic->format();

        require_once Ui::find_template('show_democratic.inc.php');

        $objects = $democratic->get_items();
        Song::build_cache($democratic->object_ids);
        Democratic::build_vote_cache($democratic->vote_ids);

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type('democratic');
        $browse->set_static_content(false);
        $browse->save_objects($objects);
        $browse->show_objects();
        $browse->store();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
