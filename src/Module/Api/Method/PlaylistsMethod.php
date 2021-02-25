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

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\SearchRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PlaylistsMethod implements MethodInterface
{
    public const ACTION = 'playlists';

    private SearchRepositoryInterface $searchRepository;

    private StreamFactoryInterface $streamFactory;

    private PlaylistRepositoryInterface $playlistRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        SearchRepositoryInterface $searchRepository,
        StreamFactoryInterface $streamFactory,
        PlaylistRepositoryInterface $playlistRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->searchRepository   = $searchRepository;
        $this->streamFactory      = $streamFactory;
        $this->playlistRepository = $playlistRepository;
        $this->configContainer    = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns playlists based on the specified filter
     *
     * @param array $input
     * filter      = (string) Alpha-numeric search term (match all if missing) //optional
     * exact       = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add         = self::set_filter(date) //optional
     * update      = self::set_filter(date) //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * @return boolean
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $like   = ((int) ($input['exact'] ?? 0) == 1) ? false : true;
        $hide   = ((int) ($input['hide_search'] ?? 0) == 1) || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::HIDE_SEARCH);
        $userId = $gatekeeper->getUser()->getId();
        $filter = (string) ($input['filter'] ?? '');

        // regular playlists
        $playlist_ids = $this->playlistRepository->getPlaylists(
            $userId,
            $filter,
            $like
        );

        // merge with the smartlists
        if (!$hide) {
            $playlist_ids = array_merge(
                $playlist_ids,
                $this->searchRepository->getSmartlists(
                    $userId,
                    $filter,
                    $like
                )
            );
        }

        if ($playlist_ids === []) {
            $result = $output->emptyResult('playlist');
        } else {
            $result = $output->playlists(
                $playlist_ids,
                $userId,
                false,
                true,
                (int) ($input['limit'] ?? 0),
                (int) ($input['offset'] ?? 0)
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
