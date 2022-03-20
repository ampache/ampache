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
     * @param integer $limit
     * @param array $data
     * @return array
     */
    public function collect(
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
                            static::API_URL,
                            urlencode($data['artist']),
                            urlencode($data['album']),
                            $lastFmApiKey
                        )
                    );
                } catch (LastFmQueryFailedException $e) {
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
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    continue;
                }
                $this->logger->notice(
                    'LastFM: found image ' . $url,
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                // HACK: we shouldn't rely on the extension to determine file type
                $results  = pathinfo($url);
                $mime     = 'image/' . $results['extension'];
                $images[] = ['url' => $url, 'mime' => $mime, 'title' => 'LastFM'];
                if ($limit && count($images) >= $limit) {
                    return $images;
                }
            } // end foreach
        } catch (Exception $error) {
            $this->logger->error(
                'LastFM error: ' . $error->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }

        return $images;
    }
}
