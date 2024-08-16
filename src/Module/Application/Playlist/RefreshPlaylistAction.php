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

namespace Ampache\Module\Application\Playlist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class RefreshPlaylistAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'refresh_playlist';

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
        $userId     = $request->getQueryParams()['user_id'] ?? null;
        $playlistId = $request->getQueryParams()['playlist_id'] ?? null;
        $searchId   = $request->getQueryParams()['search_id'] ?? null;
        if ($userId !== null && $playlistId !== null && $searchId !== null) {
            // Check rights
            $user     = $this->modelFactory->createUser((int)$userId);
            $playlist = $this->modelFactory->createPlaylist((int)$playlistId);
            $search   = $this->modelFactory->createSearch((int)$searchId, 'song', $user);
            $objects  = $search->get_items();
            if ($playlist->has_access() && !empty($objects)) {
                $playlist->delete_all();
                debug_event(self::class, 'Refreshing playlist {' . $playlist->id . '} from search {' . $search->id . '} for user {' . $user->id . '}', 5);
                $playlist->add_medias($objects);
            }
        }

        // Go elsewhere
        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf('%s/browse.php?action=playlist', $this->configContainer->getWebPath('/client'))
            );
    }
}
