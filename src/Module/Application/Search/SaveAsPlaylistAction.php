<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Search;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SaveAsPlaylistAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'save_as_playlist';

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
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === false) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        // load the search
        $search = $this->modelFactory->createSearch();
        $search->parse_rules(Search::clean_request($_REQUEST));
        $search->limit  = (filter_has_var(INPUT_POST, 'limit'))
            ? (int) Core::get_request('limit')
            : $search->limit;
        $search->random = (filter_has_var(INPUT_POST, 'random'))
            ? (int) Core::get_request('random')
            : $search->limit;
        $search->name = (filter_has_var(INPUT_POST, 'name'))
            ? (string) Core::get_request('name')
            : $search->name;

        // Make sure we have a unique name
        $playlist_name = ($search->name) ?: Core::get_global('user')->username . ' - ' . get_datetime(time());
        // create the playlist
        $playlist_id = (int)Playlist::create($playlist_name, $search->type);
        $playlist    = new Playlist($playlist_id);
        if ($playlist->id) {
            $playlist->delete_all();
            $playlist->add_medias($search->get_items());

            $this->ui->showConfirmation(
                T_('No Problem'),
                /* HINT: playlist name */
                sprintf(
                    T_('Your search has been saved as a Playlist with the name %s'),
                    $playlist_name
                ),
                sprintf(
                    '%1$s/playlist.php?action=show_playlist&playlist_id=%2$s',
                    $this->configContainer->getWebPath(),
                    $playlist_id
                )
            );
        } else {
            $this->ui->showConfirmation(
                T_("There Was a Problem"),
                T_("Failed to create playlist"),
                sprintf(
                    '%s/index.php',
                    $this->configContainer->getWebPath()
                )
            );
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
