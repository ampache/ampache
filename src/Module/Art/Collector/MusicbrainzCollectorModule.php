<?php

declare(strict_types=1);

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

use Ampache\Module\Art\Art;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\WebFetcher\Exception\FetchFailedException;
use Ampache\Module\Util\WebFetcher\WebFetcherInterface;
use Psr\Log\LoggerInterface;

final readonly class MusicbrainzCollectorModule implements CollectorModuleInterface
{
    private const string API_URL = 'https://coverartarchive.org';

    private const string MBID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(
        private WebFetcherInterface $webFetcher,
        private LoggerInterface $logger,
    ) {}

    /**
     * Gathers album art from the Cover Art Archive by release MBID, falling back to the release group.
     *
     * @param array{
     *     mb_albumid?: string,
     *     mb_albumid_group?: string,
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
        if ($art->object_type !== 'album') {
            return [];
        }

        $lookups = [
            'release' => (string) ($data['mb_albumid'] ?? ''),
            'release-group' => (string) ($data['mb_albumid_group'] ?? ''),
        ];
        foreach ($lookups as $entity => $mbid) {
            if (preg_match(self::MBID_PATTERN, $mbid) !== 1) {
                continue;
            }

            $images = $this->lookup($entity, strtolower($mbid), $limit);
            if ($images !== []) {
                return $images;
            }
        }

        return [];
    }

    private function getMime(string $url): string
    {
        return match (strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * @return array<int, array{url: string, mime: string, title: string}>
     */
    private function lookup(string $entity, string $mbid, int $limit): array
    {
        $url = sprintf('%s/%s/%s', self::API_URL, $entity, $mbid);

        $this->logger->debug(
            'gather_musicbrainz Cover Art Archive lookup: ' . $url,
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        try {
            $response = $this->webFetcher->fetch($url);
        } catch (FetchFailedException $fetchFailedException) {
            // the archive answers 404 for a release with no art, which is the common case
            $this->logger->debug(
                'gather_musicbrainz no Cover Art Archive result: ' . $fetchFailedException->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return [];
        }

        $result = json_decode($response, true);
        if (!is_array($result) || !is_array($result['images'] ?? null)) {
            return [];
        }

        // front covers first, keeping the archive's own order within each group
        $front = [];
        $other = [];
        foreach ($result['images'] as $image) {
            if (!is_array($image)) {
                continue;
            }

            $thumbnails = is_array($image['thumbnails'] ?? null) ? $image['thumbnails'] : [];
            $imageUrl   = (string) ($thumbnails['1200'] ?? $image['image'] ?? '');
            if ($imageUrl === '') {
                continue;
            }

            $candidate = [
                'url' => (string) preg_replace('/^http:/', 'https:', $imageUrl),
                'mime' => $this->getMime($imageUrl),
                'title' => 'Cover Art Archive',
            ];
            if (($image['front'] ?? false) === true) {
                $front[] = $candidate;
            } else {
                $other[] = $candidate;
            }
        }

        $images = array_merge($front, $other);

        return ($limit > 0)
            ? array_slice($images, 0, $limit)
            : $images;
    }
}
