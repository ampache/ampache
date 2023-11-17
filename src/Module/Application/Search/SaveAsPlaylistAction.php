<?php

/**
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

declare(strict_types=0);

namespace Ampache\Module\Application\Search;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
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

    private RequestParserInterface $requestParser;

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        RequestParserInterface $requestParser,
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->requestParser   = $requestParser;
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
        $browse  = $this->modelFactory->createBrowse((int)$this->requestParser->getFromRequest('browse_id'));
        $objects = $browse->get_saved();

        // Make sure we have a unique name
        $playlist_name = (isset($_POST['browse_name']))
            ? (string) $this->requestParser->getFromRequest('browse_name')
            : Core::get_global('user')->username . ' - ' . get_datetime(time());
        // keep the same public/private type as the search
        $playlist_type = (isset($_POST['browse_type']))
            ? (string) $this->requestParser->getFromRequest('browse_type')
            : 'public';

        if (!empty($objects)) {
            // create the playlist
            $playlist_id = (int)Playlist::create($playlist_name, $playlist_type);
            $playlist    = $this->modelFactory->createPlaylist($playlist_id);
            $playlist->delete_all();
            // different browses could store objects in different ways
            if (is_array($objects[0])) {
                $playlist->add_medias($objects);
            } else {
                $playlist->add_songs($objects);
            }

            $this->ui->showConfirmation(
                T_('No Problem'),
                /* HINT: playlist name */
                sprintf(
                    T_('Your search has been saved as a Playlist with the name %s'),
                    $playlist_name
                ),
                sprintf(
                    '%1$s/playlist.php?action=show&playlist_id=%2$s',
                    $this->configContainer->getWebPath(),
                    $playlist_id
                )
            );
        } else {
            $this->ui->showConfirmation(
                T_('There Was a Problem'),
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
