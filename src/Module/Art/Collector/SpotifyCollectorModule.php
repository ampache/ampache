<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Art;
use Psr\Log\LoggerInterface;
use SpotifyWebAPI\Session as SpotifySession;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;

final readonly class SpotifyCollectorModule implements CollectorModuleInterface
{
    public function __construct(
        private ConfigContainerInterface $configContainer,
        private SpotifyWebAPI $spotifyWebAPI,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * This function gathers art from the spotify catalog
     *
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
    public function collectArt(
        Art $art,
        int $limit = 5,
        array $data = [],
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
            } catch (SpotifyWebAPIException) {
                $this->logger->debug(
                    'gather_spotify: A problem exists with the client credentials',
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return $images;
            }
        }

        $filter = [];
        $query1 = '';
        $types  = $art->object_type . 's';
        $this->spotifyWebAPI->setAccessToken($accessToken);
        $getType = 'getAlbum';

        if (
            isset($data['artist']) &&
            $art->object_type === 'artist'
        ) {
            $this->logger->debug(
                'gather_spotify artist: ' . $data['artist'],
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            $query   = $data['artist'];
            $getType = 'getArtist';
        } elseif ($art->object_type === 'album') {
            $album_str  = $data['album'] ?? '';
            $artist_str = $data['artist'] ?? '';
            $logString  = sprintf('gather_spotify album: %s, artist: %s', $album_str, $artist_str);
            $this->logger->debug(
                $logString,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            // Check for manual search
            if (array_key_exists('search_limit', $data)) {
                $limit = $data['search_limit'];
                if (array_key_exists('artist', $data) && !empty($artist_str)) {
                    $filter[] = 'artist';
                }

                if (array_key_exists('year_filter', $data)) {
                    $filter[] = $data['year_filter'];
                }
            } elseif (
                !is_null($this->configContainer->get('spotify_art_filter')) ||
                $this->configContainer->get('spotify_art_filter') !== null
            ) {
                $filter = explode(',', $this->configContainer->get('spotify_art_filter'));
            }

            if ($filter !== []) {
                foreach ($filter as $item) {
                    switch (trim($item)) {
                        case 'artist':
                            $query1 .= sprintf(' artist:"%s"', $artist_str);
                            break;
                        case preg_match('/year:.*/', $item):
                            $query1 .= ' ' . $item;
                            break;
                        default:
                    }
                }

                $query = "album:" . sprintf('"%s"', $album_str) . $query1;
            } else {
                $query = sprintf('"%s"', $album_str);
            }
        } else {
            return $images;
        }

        try {
            $response = $this->spotifyWebAPI->search($query, $art->object_type, ['limit' => $limit]);
        } catch (SpotifyWebAPIException $spotifyWebAPIException) {
            if ($spotifyWebAPIException->hasExpiredToken()) {
                $session = new SpotifySession($clientId, $clientSecret);
                $session->requestCredentialsToken();
                $accessToken = $session->getAccessToken();
            } elseif ($spotifyWebAPIException->getCode() == 429) {
                $lastResponse = $this->spotifyWebAPI->getRequest()->getLastResponse();
                $retryAfter   = $lastResponse['headers']['Retry-After'];
                // Number of seconds to wait before sending another request
                sleep($retryAfter);
            }

            try {
                $response = $this->spotifyWebAPI->search($query, $art->object_type, ['limit' => $limit]);
            } catch (SpotifyWebAPIException) {
                $response = null;
            }
        }

        $items = $response->{$types}->items ?? [];
        if (count($items) > 0) {
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
