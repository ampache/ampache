<?php

/*
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

namespace Ampache\Module\Art\Collector;

use Ampache\Repository\Model\Art;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Exception;
use Psr\Log\LoggerInterface;
use WpOrg\Requests\Requests;

final class GoogleCollectorModule implements CollectorModuleInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
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

        try {
            // Need this to not be considered as a bot (are we? ^^)
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0',
            ];

            $query = Requests::get($url, $headers, Core::requests_options());
            $html  = $query->body;

            if (preg_match_all('/"ou":"(http.+?)"/', $html, $matches, PREG_PATTERN_ORDER)) {
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
                    $mime .= $results['extension'] ?? 'jpg';

                    $images[] = array('url' => $match, 'mime' => $mime, 'title' => 'Google');
                    if ($limit > 0 && count($images) >= $limit) {
                        break;
                    }
                }
            }
        } catch (Exception $error) {
            $this->logger->error(
                'Error getting google images: ' . $error->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }

        return $images;
    }
}
