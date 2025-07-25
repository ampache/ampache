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
use Ampache\Module\LastFm\Exception\LastFmQueryFailedException;
use Ampache\Repository\Model\Art;
use Ampache\Module\LastFm\LastFmQueryInterface;
use Ampache\Module\System\LegacyLogger;
use Exception;
use Psr\Log\LoggerInterface;

final class LastFmCollectorModule implements CollectorModuleInterface
{
    private const API_URL = 'http://ws.audioscrobbler.com/2.0/';

    private ConfigContainerInterface $configContainer;

    private LastFmQueryInterface $lastFmQuery;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LastFmQueryInterface $lastFmQuery,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->lastFmQuery     = $lastFmQuery;
        $this->logger          = $logger;
    }

    /**
     * This returns the art from lastfm. It doesn't currently require an
     * account but may in the future.
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
    public function collectArt(
        Art $art,
        int $limit = 5,
        array $data = []
    ): array {
        $images = [];

        $lastFmApiKey = $this->configContainer->get('lastfm_api_key');

        try {
            $coverart = [];
            // search for album objects
            if ((!empty($data['artist']) && !empty($data['album']))) {
                try {
                    $xmldata = $this->lastFmQuery->queryLastFm(
                        sprintf(
                            '%s?method=album.getInfo&artist=%s&album=%s&api_key=%s',
                            self::API_URL,
                            urlencode($data['artist']),
                            urlencode($data['album']),
                            $lastFmApiKey
                        )
                    );
                } catch (LastFmQueryFailedException) {
                    return [];
                }
                if (!$xmldata->album->image) {
                    return [];
                }
                foreach ($xmldata->album->image as $albumart) {
                    $coverart[] = (string)$albumart;
                }
            }
            // Albums only for last FM
            if (empty($coverart)) {
                return [];
            }
            ksort($coverart);
            foreach ($coverart as $url) {
                // We need to check the URL for the /noimage/ stuff
                if (is_array($url) || strpos($url, '/noimage/') !== false) {
                    $this->logger->notice(
                        'LastFM: Detected as noimage, skipped',
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    continue;
                }
                $this->logger->notice(
                    'LastFM: found image ' . $url,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                // HACK: we shouldn't rely on the extension to determine file type
                $results = pathinfo($url);
                if (is_array($results) && array_key_exists('extension', $results) && !empty($results['extension'])) {
                    $mime     = 'image/' . $results['extension'];
                    $images[] = [
                        'url' => $url,
                        'mime' => $mime,
                        'title' => 'LastFM'
                    ];
                    if ($limit && count($images) >= $limit) {
                        return $images;
                    }
                }
            } // end foreach
        } catch (Exception $error) {
            $this->logger->error(
                'LastFM error: ' . $error->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        // Drop any duplicates
        $images = array_map("unserialize", array_unique(array_map("serialize", $images)));

        // Order is smallest to largest so reverse it
        return array_reverse($images);
    }
}
