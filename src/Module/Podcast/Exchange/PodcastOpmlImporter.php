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

namespace Ampache\Module\Podcast\Exchange;

use Ampache\Module\Podcast\Exception\FeedNotLoadableException;
use Ampache\Module\Podcast\Exception\InvalidCatalogException;
use Ampache\Module\Podcast\Exception\InvalidFeedUrlException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Catalog;
use Psr\Log\LoggerInterface;

/**
 * Imports exported podcast items (from ampache or from an external service)
 *
 * Uses the OPML format to import podcasts from external sources
 *
 * @see http://opml.org/spec2.opml
 */
final class PodcastOpmlImporter implements PodcastOpmlImporterInterface
{
    private PodcastOpmlLoaderInterface $podcastOpmlLoader;

    private PodcastCreatorInterface $podcastCreator;

    private LoggerInterface $logger;

    public function __construct(
        PodcastOpmlLoaderInterface $podcastOpmlLoader,
        PodcastCreatorInterface $podcastCreator,
        LoggerInterface $logger
    ) {
        $this->podcastOpmlLoader = $podcastOpmlLoader;
        $this->podcastCreator    = $podcastCreator;
        $this->logger            = $logger;
    }

    /**
     * Loads the opml-xml and tries to create all contained podcasts
     *
     * @param string $xml xml following the opml specs
     *
     * @throws InvalidCatalogException
     */
    public function import(Catalog $catalog, string $xml): int
    {
        $imported = 0;

        foreach ($this->podcastOpmlLoader->load($xml) as $feedUrl) {
            $this->logger->debug(
                sprintf('Importing feed: %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            try {
                $this->podcastCreator->create(
                    $feedUrl,
                    $catalog
                );

                $imported++;
            } catch (InvalidFeedUrlException $e) {
                $this->logger->warning(
                    sprintf('Feed-url invalid: %s', $feedUrl),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            } catch (FeedNotLoadableException $e) {
                $this->logger->warning(
                    sprintf('Feed-url not readable: %s', $feedUrl),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }

        return $imported;
    }
}
