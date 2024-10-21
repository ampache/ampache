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

use Ampache\Repository\Model\Art;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Exception;
use MusicBrainz\MusicBrainz;
use Psr\Log\LoggerInterface;
use WpOrg\Requests\Requests;

final class MusicbrainzCollectorModule implements CollectorModuleInterface
{
    private MusicBrainz $musicBrainz;

    private LoggerInterface $logger;

    public function __construct(
        MusicBrainz $musicBrainz,
        LoggerInterface $logger
    ) {
        $this->musicBrainz = $musicBrainz;
        $this->logger      = $logger;
    }

    /**
     * This function retrieves art based on MusicBrainz' Advanced
     * Relationships
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
        $images    = [];
        $num_found = 0;

        if ($art->type != 'album') {
            return $images;
        }

        if (!array_key_exists('mb_albumid', $data) || $data['mb_albumid'] === null) {
            return $images;
        }
        $this->logger->debug(
            "gather_musicbrainz Album MBID: " . $data['mb_albumid'],
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        $includes = ['url-rels'];
        try {
            $release = $this->musicBrainz->lookup('release', $data['mb_albumid'], $includes);
        } catch (Exception $error) {
            $this->logger->warning(
                "gather_musicbrainz exception: " . $error,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return $images;
        }

        $asin = $release->asin ?? false;

        if ($asin) {
            $this->logger->debug(
                "gather_musicbrainz Found ASIN: " . $asin,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            $base_urls = [
                "01" => "ec1.images-amazon.com",
                "02" => "ec1.images-amazon.com",
                "03" => "ec2.images-amazon.com",
                "08" => "ec1.images-amazon.com",
                "09" => "ec1.images-amazon.com",
            ];
            foreach ($base_urls as $server_num => $base_url) {
                // to avoid complicating things even further, we only look for large cover art
                $url = 'http://' . $base_url . '/images/P/' . $asin . '.' . $server_num . '.LZZZZZZZ.jpg';

                $this->logger->debug(
                    "gather_musicbrainz Evaluating Amazon URL: " . $url,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                $request = Requests::get($url, [], Core::requests_options());
                if ($request->status_code == 200) {
                    $num_found++;

                    $this->logger->debug(
                        "gather_musicbrainz Amazon URL added: " . $url,
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );
                    $images[] = [
                        'url' => $url,
                        'mime' => 'image/jpeg',
                        'title' => 'MusicBrainz'
                    ];
                    if ($num_found >= $limit) {
                        return $images;
                    }
                }
            }
        }
        // The next bit is based directly on the MusicBrainz server code
        // that displays cover art.
        // I'm leaving in the releaseuri info for the moment, though
        // it's not going to be used.
        $coverartsites   = [];
        $coverartsites[] = [
            'name' => "CD Baby",
            'domain' => "cdbaby.com",
            'regexp' => '@http://cdbaby\.com/cd/(\w)(\w)(\w*)@',
            'imguri' => 'http://cdbaby.name/$matches[1]/$matches[2]/$matches[1]$matches[2]$matches[3].jpg',
            'releaseuri' => 'http://cdbaby.com/cd/$matches[1]$matches[2]$matches[3]/from/musicbrainz',
        ];
        $coverartsites[] = [
            'name' => "CD Baby",
            'domain' => "cdbaby.name",
            'regexp' => "@http://cdbaby\.name/([a-z0-9])/([a-z0-9])/([A-Za-z0-9]*).jpg@",
            'imguri' => 'http://cdbaby.name/$matches[1]/$matches[2]/$matches[3].jpg',
            'releaseuri' => 'http://cdbaby.com/cd/$matches[3]/from/musicbrainz',
        ];
        $coverartsites[] = [
            'name' => 'archive.org',
            'domain' => 'archive.org',
            'regexp' => '/^(.*\.(jpg|jpeg|png|gif))$/',
            'imguri' => '$matches[1]',
            'releaseuri' => '',
        ];
        $coverartsites[] = [
            'name' => "Jamendo",
            'domain' => "www.jamendo.com",
            'regexp' => '/http://www\.jamendo\.com/(\w\w/)?album/(\d+)/',
            'imguri' => 'http://img.jamendo.com/albums/$matches[2]/covers/1.200.jpg',
            'releaseuri' => 'http://www.jamendo.com/album/$matches[2]',
        ];
        $coverartsites[] = [
            'name' => '8bitpeoples.com',
            'domain' => '8bitpeoples.com',
            'regexp' => '/^(.*)$/',
            'imguri' => '$matches[1]',
            'releaseuri' => '',
        ];
        $coverartsites[] = [
            'name' => 'Encyclopédisque',
            'domain' => 'encyclopedisque.fr',
            'regexp' => '/http://www.encyclopedisque.fr/images/imgdb/(thumb250|main)/(\d+).jpg/',
            'imguri' => 'http://www.encyclopedisque.fr/images/imgdb/thumb250/$matches[2].jpg',
            'releaseuri' => 'http://www.encyclopedisque.fr/',
        ];
        $coverartsites[] = [
            'name' => 'Thastrom',
            'domain' => 'www.thastrom.se',
            'regexp' => '/^(.*)$/',
            'imguri' => '$matches[1]',
            'releaseuri' => '',
        ];
        $coverartsites[] = [
            'name' => 'Universal Poplab',
            'domain' => 'www.universalpoplab.com',
            'regexp' => '/^(.*)$/',
            'imguri' => '$matches[1]',
            'releaseuri' => '',
        ];
        foreach ($release->relations ?? [] as $ar) {
            $arurl = $ar->url->resource;

            $this->logger->debug(
                "gather_musicbrainz Found URL AR: " . $arurl,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            foreach ($coverartsites as $casite) {
                if (strpos($arurl, $casite['domain']) !== false) {
                    $this->logger->debug(
                        "gather_musicbrainz Matched coverart site: " . $casite['name'],
                        [LegacyLogger::CONTEXT_TYPE => self::class]
                    );

                    if (preg_match($casite['regexp'], $arurl, $matches)) {
                        $num_found++;
                        $url = $casite['imguri'];

                        $this->logger->debug(
                            "gather_musicbrainz Generated URL added: " . $url,
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );

                        $images[] = [
                            'url' => $url,
                            'mime' => 'image/jpeg',
                            'title' => 'MusicBrainz'
                        ];
                        if ($num_found >= $limit) {
                            return $images;
                        }
                    }
                }
            }
        }

        return $images;
    }
}
