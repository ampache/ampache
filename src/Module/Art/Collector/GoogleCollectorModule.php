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

use Ampache\Module\Util\ExternalResourceLoaderInterface;
use Ampache\Repository\Model\Art;
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;

final class GoogleCollectorModule implements CollectorModuleInterface
{
    private ExternalResourceLoaderInterface $externalResourceLoader;

    private LoggerInterface $logger;

    public function __construct(
        ExternalResourceLoaderInterface $externalResourceLoader,
        LoggerInterface $logger
    ) {
        $this->externalResourceLoader = $externalResourceLoader;
        $this->logger                 = $logger;
    }

    /**
     * Raw google search to retrieve the art, not very reliable
     *
     * @param Art $art
     * @param integer $limit
     * @param array $data
     *
     * @return array
     */
    public function collect(
        Art $art,
        int $limit = 5,
        array $data = []
    ): array {
        if (!$limit) {
            $limit = 5;
        }

        $images = [];
        $search = rawurlencode($data['keyword']);
        $size   = '&imgsz=m'; // Medium

        $url = "http://www.google.com/search?source=hp&tbm=isch&q=" . $search . "&oq=&um=1&ie=UTF-8&sa=N&tab=wi&start=0&tbo=1" . $size;

        $this->logger->debug(
            'Search url: ' . $url,
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $result = $this->externalResourceLoader->retrieve($url);
        if ($result === null) {
            return $images;
        }

        $html = (string) $result->getBody();

        if (
            preg_match_all('/"ou":"(http.+?)"/', $html, $matches, PREG_PATTERN_ORDER)
        ) {
            foreach ($matches[1] as $match) {
                if (preg_match('/lookaside\.fbsbx\.com/', $match)) {
                    break;
                }
                $match = rawurldecode($match);

                $this->logger->debug(
                    'Found image at: ' . $match,
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
                $results = pathinfo($match);
                $test    = $results['extension'];
                $pos     = strpos($test, '?');
                if ($pos > 0) {
                    $results['extension'] = substr($test, 0, $pos);
                }
                if (preg_match('~[^png|^jpg|^jpeg|^jif|^bmp]~', $test)) {
                    $results['extension'] = 'jpg';
                }

                $mime = 'image/';
                $mime .= isset($results['extension']) ? $results['extension'] : 'jpeg';

                $images[] = array('url' => $match, 'mime' => $mime, 'title' => 'Google');
                if ($limit > 0 && count($images) >= $limit) {
                    break;
                }
            }
        }

        return $images;
    }
}
