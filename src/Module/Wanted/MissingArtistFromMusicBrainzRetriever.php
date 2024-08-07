<?php

declare(strict_types=1);

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
 */

namespace Ampache\Module\Wanted;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\LegacyLogger;
use Exception;
use MusicBrainz\MusicBrainz;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Uses MusicBrainz to retrieve missing artist data
 *
 * @phpstan-type MissingArtistResult array{
 *  mbid: string,
 *  name: string,
 *  link: string
 * }
 */
final class MissingArtistFromMusicBrainzRetriever implements MissingArtistRetrieverInterface
{
    private MusicBrainz $musicBrainz;

    private CacheInterface $cache;

    private LoggerInterface $logger;

    public function __construct(
        MusicBrainz $musicBrainz,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->musicBrainz = $musicBrainz;
        $this->cache       = $cache;
        $this->logger      = $logger;
    }

    /**
     * Get missing artist data.
     *
     * @param string $musicBrainzId mbid of the artist
     *
     * @return null|MissingArtistResult
     */
    public function retrieve(string $musicBrainzId): ?array
    {
        // prevent too early timeouts due to lagging external service
        set_time_limit(600);

        if (trim($musicBrainzId) === '') {
            return null;
        }

        $cacheKey = sprintf('wanted:artist:%s', $musicBrainzId);

        /** @var null|MissingArtistResult $item */
        $item = $this->cache->get($cacheKey);

        if ($item !== null) {
            return $item;
        }

        $artist = [
            'mbid' => $musicBrainzId,
            'name' => T_('Unknown Artist'),
            'link' => '',
        ];

        try {
            /** @var object{name: string, error?: string} $result */
            $result = $this->musicBrainz->lookup('artist', $musicBrainzId);
        } catch (Exception $error) {
            $this->logger->debug(
                sprintf(
                    'Error retrieving MusicBrainz info for artist `%s`: %s',
                    $musicBrainzId,
                    $error->getMessage()
                ),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return $artist;
        }

        // if the requests fails (i.e. unknown mbid), the service will return a result containing an error-property
        if (property_exists($result, 'error')) {
            $this->cache->set($cacheKey, $artist);

            return $artist;
        }

        // prepare the result
        $artist['name'] = (string) $result->name;
        $artist['link'] = sprintf(
            '<a href="%s/artists.php?action=show_missing&mbid=%s" title="%s">%s</a>',
            AmpConfig::get('web_path', ''),
            $artist['mbid'],
            $artist['name'],
            $artist['name']
        );

        $this->cache->set($cacheKey, $artist);

        return $artist;
    }
}
