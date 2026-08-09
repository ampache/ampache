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

namespace Ampache\Module\Podcast;

use Ampache\Module\Art\Art;
use Ampache\Module\Podcast\Feed\Exception\FeedLoadingException;
use Ampache\Module\Podcast\Feed\FeedLoaderInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Podcast;
use Psr\Log\LoggerInterface;

/**
 * Refreshes the details of an existing podcast from its feed
 */
final readonly class PodcastUpdater implements PodcastUpdaterInterface
{
    public function __construct(
        private FeedLoaderInterface $feedLoader,
        private LoggerInterface $logger,
    ) {}

    /**
     * Overwrite the podcast details using the channel data of its feed
     *
     * Episodes are not touched, that's the syncers job.
     *
     * @param bool $updateArt Replace the existing art with the one the channel advertises
     *
     * @return bool True if anything was written
     *
     * @throws FeedLoadingException
     */
    public function update(Podcast $podcast, bool $updateArt = true): bool
    {
        $feedUrl = $podcast->getFeedUrl();
        if ($feedUrl === '') {
            return false;
        }

        try {
            $feed = $this->feedLoader->load($feedUrl);
        } catch (FeedLoadingException $feedLoadingException) {
            $this->logger->warning(
                sprintf('Unable to load feed %s: %s', $feedUrl, $feedLoadingException->getMessage()),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            throw $feedLoadingException;
        }

        // a channel omitting a value must not blank out what we already have
        if ($feed['title'] !== '') {
            $podcast->setTitle($feed['title']);
        }

        if ($feed['website'] !== '') {
            $podcast->setWebsite($feed['website']);
        }

        if ($feed['description'] !== '') {
            $podcast->setDescription($feed['description']);
        }

        if ($feed['language'] !== '') {
            $podcast->setLanguage($feed['language']);
        }

        if ($feed['copyright'] !== '') {
            $podcast->setCopyright($feed['copyright']);
        }

        if ($feed['generator'] !== '') {
            $podcast->setGenerator($feed['generator']);
        }

        // the setter ignores a null date
        $podcast->setLastBuildDate($feed['lastBuildDate']);
        $podcast->save();

        $artUrl = (string) $feed['artUrl'];
        if ($updateArt && $artUrl !== '') {
            // insert() resets the existing art before writing, so this replaces rather than adds
            $art = new Art($podcast->getId(), 'podcast');
            $art->insert_url($artUrl);
        }

        $this->logger->debug(
            sprintf('Updated podcast %d from feed %s', $podcast->getId(), $feedUrl),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return true;
    }
}
