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

namespace Ampache\Module\Art\Collector;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\Art;
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;
use SpotifyWebAPI\Session as SpotifySession;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;

final class SpotifyCollectorModule implements CollectorModuleInterface
{
    private ConfigContainerInterface $configContainer;

    private SpotifyWebAPI $spotifyWebAPI;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        SpotifyWebAPI $spotifyWebAPI,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->spotifyWebAPI   = $spotifyWebAPI;
        $this->logger          = $logger;
    }

    /**
     * This function gathers art from the spotify catalog
     *
     * @param Art $art
     * @param int $limit
     * @param array $data
     *
     * @return array
     */
    public function collect(
        Art $art,
        int $limit = 5,
        array $data = []
    ): array {
        static $accessToken = null;

        $session = null;
        $images  = [];

        $clientId     = $this->configContainer->get('spotify_client_id') ?: null;
        $clientSecret = $this->configContainer->get('spotify_client_secret') ?: null;

        if ($clientId === null || $clientSecret === null) {
            $this->logger->debug(
                'gather_spotify: Missing Spotify credentials, check your config',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return $images;
        }

        if (!isset($accessToken)) {
            try {
                $session = new SpotifySession($clientId, $clientSecret);
                $session->requestCredentialsToken();
                $accessToken = $session->getAccessToken();
            } catch (SpotifyWebAPIException $error) {
                $this->logger->debug(
                    'gather_spotify: A problem exists with the client credentials',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        }
        $types = $art->type . 's';
        $this->spotifyWebAPI->setAccessToken($accessToken);
        
        if ($art->type == 'artist') {
            $this->logger->debug(
                'gather_spotify artist: ' . $data['artist'],
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            $query   = $data['artist'];
            $getType = 'getArtist';
        } elseif ($art->type == 'album') {
            $this->logger->debug(
                'gather_spotify album: ' . $data['album'],
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            $query   = 'album:' . $data['album'] . ' artist:' . $data['artist'];
            $getType = 'getAlbum';
        } else {
            return $images;
        }

        try {
            $response = $this->spotifyWebAPI->search($query, $art->type);
        } catch (SpotifyWebAPIException $error) {
            if ($error->hasExpiredToken()) {
                $accessToken = $session->getAccessToken();
            } elseif ($error->getCode() == 429) {
                $lastResponse = $this->spotifyWebAPI->getRequest()->getLastResponse();
                $retryAfter   = $lastResponse['headers']['Retry-After'];
                // Number of seconds to wait before sending another request
                sleep($retryAfter);
            }
            $response = $this->spotifyWebAPI->search($query, $art->type);
        }

        if (count($response->{$types}->items)) {
            foreach ($response->{$types}->items as $item) {
                $item_id = $item->id;
                $result  = $this->spotifyWebAPI->{$getType}($item_id);
                $image   = $result->images[0];

                $this->logger->debug(
                    'gather_spotify: found ' . $image->url,
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                $images[] = [
                    'url' => $image->url,
                    'mime' => 'image/jpeg',
                    'title' => 'Spotify'
                ];
            }
        }

        return $images;
    }
}
