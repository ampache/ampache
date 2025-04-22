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

namespace Ampache\Module\Art\Collector;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Art;
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
     * @param array{
     *     mb_albumid?: string,
     *     artist?: string,
     *     album?: string,
     *     cover?: ?string,
     *     file?: string,
     *     year_filter?: string,
     *     search_limit?: int,
     * } $data
     * @return array<int, array{url: string, mime: string, title: string}>
     */
    public function collect(
        Art $art,
        int $limit = 5,
        array $data = []
    ): array {
        static $accessToken = null;

        $session = null;
        $images  = [];

        $clientId     = $this->configContainer->get('spotify_client_id') ?? null;
        $clientSecret = $this->configContainer->get('spotify_client_secret') ?? null;

        if ($clientId === null || $clientSecret === null) {
            $this->logger->debug(
                'gather_spotify: Missing Spotify credentials, check your config',
                [LegacyLogger::CONTEXT_TYPE => self::class]
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
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return $images;
            }
        }
        $filter = [];
        $query1 = '';
        $types  = $art->type . 's';
        $this->spotifyWebAPI->setAccessToken($accessToken);
        $getType = 'getAlbum';

        if (
            isset($data['artist']) &&
            $art->type == 'artist'
        ) {
            $this->logger->debug(
                'gather_spotify artist: ' . $data['artist'],
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            $query   = $data['artist'];
            $getType = 'getArtist';
        } elseif ($art->type == 'album') {
            $album_str  = $data['album'] ?? '';
            $artist_str = $data['artist'] ?? '';
            $logString  = sprintf('gather_spotify album: %s, artist: %s', $album_str, $artist_str);
            $this->logger->debug(
                $logString,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            // Check for manual search
            if (key_exists('search_limit', $data)) {
                $limit = $data['search_limit'];
                if (key_exists('artist', $data) && !empty($artist_str)) {
                    $filter[] = 'artist';
                }
                if (key_exists('year_filter', $data)) {
                    $filter[] = $data['year_filter'];
                }
            } elseif (
                !is_null($this->configContainer->get('spotify_art_filter')) ||
                !empty($this->configContainer->get('spotify_art_filter'))
            ) {
                $filter = explode(',', $this->configContainer->get('spotify_art_filter'));
            }
            if (!empty($filter)) {
                foreach ($filter as $item) {
                    switch (trim($item)) {
                        case 'artist':
                            $query1 .= " artist:\"{$artist_str}\"";
                            break;
                        case preg_match('/year:.*/', $item):
                            $query1 .= ' ' . $item;
                            break;
                        default:
                    }
                }
                $query = "album:" . "\"{$album_str}\"" . $query1;
            } else {
                $query = "\"{$album_str}\"";
            }
        } else {
            return $images;
        }

        try {
            $response = $this->spotifyWebAPI->search($query, $art->type, ['limit' => $limit]);
        } catch (SpotifyWebAPIException $error) {
            if ($error->hasExpiredToken()) {
                $session = new SpotifySession($clientId, $clientSecret);
                $session->requestCredentialsToken();
                $accessToken = $session->getAccessToken();
            } elseif ($error->getCode() == 429) {
                $lastResponse = $this->spotifyWebAPI->getRequest()->getLastResponse();
                $retryAfter   = $lastResponse['headers']['Retry-After'];
                // Number of seconds to wait before sending another request
                sleep($retryAfter);
            }
            try {
                $response = $this->spotifyWebAPI->search($query, $art->type, ['limit' => $limit]);
            } catch (SpotifyWebAPIException $error) {
                $response = null;
            }
        }
        $items = $response->{$types}->items ?? [];
        if (count($items)) {
            foreach ($items as $item) {
                $item_id = $item->id;
                try {
                    $result = $this->spotifyWebAPI->{$getType}($item_id);
                } catch (SpotifyWebAPIException $error) {
                    $this->logger->error(
                        'gather_spotify ' . $error->getMessage(),
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );

                    return $images;
                }

                foreach ($result->images as $image) {
                    $images[] = [
                        'url' => $image->url,
                        'mime' => 'image/jpeg',
                        'title' => 'Spotify'
                    ];
                }
            }
        }

        return $images;
    }
}
