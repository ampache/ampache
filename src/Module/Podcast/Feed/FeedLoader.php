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
 *
 */

namespace Ampache\Module\Podcast\Feed;

use Ampache\Module\Podcast\Feed\Exception\FeedLoadingException;
use Ampache\Module\Util\WebFetcher\Exception\FetchFailedException;
use Ampache\Module\Util\WebFetcher\WebFetcherInterface;
use DateTime;
use DateTimeInterface;
use SimpleXMLElement;

/**
 * Loads podcast feeds
 */
final class FeedLoader implements FeedLoaderInterface
{
    private WebFetcherInterface $webFetcher;

    public function __construct(
        WebFetcherInterface $webFetcher
    ) {
        $this->webFetcher = $webFetcher;
    }

    /**
     * Load the podcast content by its feed-url
     *
     * @return array{
     *  title: string,
     *  website: string,
     *  description: string,
     *  language: string,
     *  copyright: string,
     *  generator: string,
     *  episodes: SimpleXMLElement|null,
     *  artUrl: null|string,
     *  lastBuildDate: null|DateTimeInterface
     * }
     *
     * @throws FeedLoadingException
     */
    public function load(
        string $feedUrl
    ): array {
        $lastBuildDate = null;
        $artUrl        = null;

        try {
            $xmlstr = $this->webFetcher->fetch($feedUrl);
        } catch (FetchFailedException $error) {
            throw new FeedLoadingException($error->getMessage());
        }

        $xml = simplexml_load_string($xmlstr);

        if ($xml === false) {
            // I've seems some &'s in feeds that screw up
            $xml = simplexml_load_string(str_replace('&', '&amp;', $xmlstr));
        }
        if ($xml === false) {
            throw new FeedLoadingException();
        }

        $lastbuilddatestr = (string)$xml->channel->lastBuildDate;
        if ($lastbuilddatestr !== '') {
            $lastBuildDate = new DateTime($lastbuilddatestr);
        }

        if ($xml->channel->image) {
            $artUrl = (string)$xml->channel->image->url;
        }

        return [
            'title' => html_entity_decode((string)$xml->channel->title),
            'website' => (string)$xml->channel->link,
            'description' => html_entity_decode((string)$xml->channel->description),
            'language' => (string)$xml->channel->language,
            'copyright' => html_entity_decode((string)$xml->channel->copyright),
            'generator' => html_entity_decode((string)$xml->channel->generator),
            'episodes' => $xml->channel->item,
            'artUrl' => $artUrl,
            'lastBuildDate' => $lastBuildDate
        ];
    }
}
