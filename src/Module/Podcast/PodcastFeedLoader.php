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
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\ExternalResourceLoaderInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

final class PodcastFeedLoader implements PodcastFeedLoaderInterface
{
    private LoggerInterface $logger;

    private ExternalResourceLoaderInterface $externalResourceLoader;

    public function __construct(
        LoggerInterface $logger,
        ExternalResourceLoaderInterface $externalResourceLoader
    ) {
        $this->logger                 = $logger;
        $this->externalResourceLoader = $externalResourceLoader;
    }

    /**
     * @throws Exception\PodcastFeedLoadingException
     */
    public function load(
        string $feedUrl
    ): SimpleXMLElement {
        $this->logger->info(
            sprintf('Syncing feed %s ...', $feedUrl),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $content = $this->externalResourceLoader->retrieve($feedUrl);
        if ($content === null) {
            $this->logger->error(
                sprintf('Cannot access feed %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            throw new Exception\PodcastFeedLoadingException();
        }

        $root = @simplexml_load_string((string) $content->getBody());
        if ($root === false) {
            $this->logger->critical(
                sprintf('Cannot read feed %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            throw new Exception\PodcastFeedLoadingException();
        }

        return $root;
    }
}
