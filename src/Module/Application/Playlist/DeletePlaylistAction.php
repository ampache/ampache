<?php

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

declare(strict_types=0);

namespace Ampache\Module\Application\Playlist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class DeletePlaylistAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'delete_playlist';

    private ModelFactoryInterface $modelFactory;

    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory    = $modelFactory;
        $this->responseFactory = $responseFactory;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $playlistId = $request->getQueryParams()['playlist_id'] ?? null;
        if ($playlistId !== null) {
            // Check rights
            $playlist = $this->modelFactory->createPlaylist((int) $playlistId);
            if ($playlist->has_access()) {
                $playlist->delete();

                // Go elsewhere
                return $this->responseFactory
                    ->createResponse(StatusCode::FOUND)
                    ->withHeader(
                        'Location',
                        sprintf('%s/browse.php?action=playlist', $this->configContainer->getWebPath())
                    );
            }
        }

        throw new AccessDeniedException();
    }
}
