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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Podcast\Feed\FeedText;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use DateTime;
use DateTimeInterface;
use SimpleXMLElement;

/**
 * Provides functions for podcast-syncing
 */
final readonly class PodcastSyncer implements PodcastSyncerInterface
{
    public function __construct(
        private PodcastRepositoryInterface $podcastRepository,
        private ModelFactoryInterface $modelFactory,
        private PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader,
        private PodcastDeleterInterface $podcastDeleter,
        private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        private ConfigContainerInterface $configContainer,
    ) {}

    /**
     * Add podcast episodes
     */
    public function addEpisodes(
        Podcast $podcast,
        SimpleXMLElement $episodes,
        ?DateTimeInterface $lastSync = null,
        bool $gather = false,
    ): void {
        foreach ($episodes as $episode) {
            if ($episode) {
                $this->add_episode($podcast, $episode, $lastSync);
            }
        }

        $change   = 0;
        $syncDate = new DateTime();

        $downloadLimit = (int) $this->configContainer->get(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD);
        // -1 means no downloads
        if ($downloadLimit < 0) {
            $downloadLimit = false;
        }

        // 0 means no limit
        if ($downloadLimit === 0) {
            $downloadLimit = null;
        }

        // Select episodes to download
        $downloadEpisodes = ($downloadLimit === false)
            ? []
            : $this->podcastEpisodeRepository->getEpisodesEligibleForDownload($podcast, $downloadLimit);

        /** @var Podcast_Episode $episode */
        foreach ($downloadEpisodes as $episode) {
            $episode->change_state(PodcastEpisodeStateEnum::PENDING);
            if ($gather) {
                $this->podcastEpisodeDownloader->fetch($episode);

                $change++;
            }
        }

        if ($change > 0) {
            // cleanup old episodes (if available)
            $this->podcastDeleter->deleteEpisode(
                $this->podcastEpisodeRepository->getEpisodesEligibleForDeletion($podcast)
            );

            Catalog::update_mapping('podcast');
            Catalog::update_mapping('podcast_episode');
        }

        // the rows are inserted whether or not anything downloaded, so the count is refreshed either way
        $podcast->setEpisodeCount(
            $this->podcastEpisodeRepository->getEpisodeCount($podcast)
        );
        $podcast->setLastSyncDate($syncDate);
        $podcast->save();
    }

    /**
     * Update the feed and sync all new episodes
     */
    public function sync(
        Podcast $podcast,
        bool $gather = false,
    ): bool {
        $feed = $podcast->getFeedUrl();
        if ($feed === '') {
            return false;
        }

        debug_event(self::class, 'Syncing feed ' . $feed . ' ...', 4);

        $xmlstr = file_get_contents($feed, false, stream_context_create(Core::requests_options()));
        if ($xmlstr === false) {
            debug_event(self::class, 'Cannot access feed ' . $feed, 1);

            return false;
        }

        $xml = simplexml_load_string($xmlstr);
        if ($xml === false) {
            // I've seems some &'s in feeds that screw up
            $xml = simplexml_load_string(str_replace('&', '&amp;', $xmlstr));
        }

        if ($xml === false) {
            debug_event(self::class, 'Cannot read feed ' . $feed, 1);

            return false;
        }

        $this->addEpisodes($podcast, $xml->channel->item, $podcast->getLastSyncDate(), $gather);

        return true;
    }

    /**
     * Syncs a single episode
     */
    public function syncEpisode(Podcast_Episode $episode): void
    {
        $this->podcastEpisodeDownloader->fetch($episode);
    }

    /**
     * Sync all podcast-items within the given catalogs
     *
     * @param iterable<Catalog> $catalogs
     * @return int Amount of new episodes
     */
    public function syncForCatalogs(
        iterable $catalogs,
    ): int {
        $newEpisodeCount = 0;
        $downloadLimit   = (int) $this->configContainer->get(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD);

        foreach ($catalogs as $catalog) {
            $podcastIds = $catalog->get_podcast_ids();

            foreach ($podcastIds as $podcastId) {
                $podcast = $this->podcastRepository->findById($podcastId);
                if ($podcast === null) {
                    continue;
                }

                $this->sync($podcast);

                $episodes = $podcast->getEpisodeIds(PodcastEpisodeStateEnum::PENDING);
                $newEpisodeCount += count($episodes);

                // -1 means no downloads
                if ($downloadLimit < 0) {
                    continue;
                }

                $downloadCount = 0;
                foreach ($episodes as $episodeId) {
                    if ($downloadLimit === 0 || $downloadCount < $downloadLimit) {
                        $this->podcastEpisodeDownloader->fetch(
                            $this->modelFactory->createPodcastEpisode($episodeId)
                        );
                    }

                    $downloadCount++;
                }
            }
        }

        return $newEpisodeCount;
    }

    /**
     * Stores the provided xml element as a podcast-episode
     *
     * An item we already hold is not added again; its description is refreshed from the feed instead.
     */
    private function add_episode(
        Podcast $podcast,
        SimpleXMLElement $episode,
        ?DateTimeInterface $lastSync,
    ): void {
        $title   = FeedText::cleanLine((string) $episode->title);
        $website = trim((string) $episode->link);
        $guid    = (string) $episode->guid;
        // the markup has to go before the length is capped, or the cap eats the tags instead of the text
        $description = Dba::check_length(FeedText::clean((string) $episode->description), 4096);
        $author      = Dba::check_length(FeedText::cleanLine((string) $episode->author), 64);
        $category    = FeedText::cleanLine((string) $episode->category);
        $source      = '';
        if ($episode->enclosure) {
            $source = (string) $episode->enclosure['url'];
        }

        $itunes   = $episode->children('itunes', true);
        $duration = (string) $itunes->duration;
        // time is missing hour e.g. "15:23"
        if (preg_grep("/^\\d\\d\\:\\d\\d\$/", [$duration])) {
            $duration = '00:' . $duration;
        }

        // process a time string "03:23:01"
        $ptime = (preg_grep("/\\d?\\d\\:\\d\\d\\:\\d\\d/", [$duration]))
            ? date_parse($duration)
            : $duration;
        // process "HH:MM:SS" time OR fall back to a seconds duration string e.g "24325"
        $time = (is_array($ptime))
            ? (int) $ptime['hour'] * 3600 + (int) $ptime['minute'] * 60 + (int) $ptime['second']
            : (int) $ptime;

        $pubdate    = 0;
        $pubdatestr = (string) $episode->pubDate;
        if ($pubdatestr !== '' && $pubdatestr !== '0') {
            $pubdate = strtotime($pubdatestr);
        }

        if ($pubdate < 1) {
            debug_event(self::class, 'Invalid episode publication date, skipped', 3);

            return;
        }

        if ($source === '' || $source === '0') {
            debug_event(self::class, 'Episode source URL not found, skipped', 3);

            return;
        }

        // an episode already in the database is refreshed instead of added a second time
        $existing = $this->find_existing_episode($podcast->getId(), $guid, $source, $title, $time, $pubdate);
        if ($existing !== null) {
            // a feed that supplies no description must not blank the one we have
            if ($description !== '' && $description !== $existing['description']) {
                debug_event(self::class, 'Refreshing the description of episode ' . $existing['id'], 4);

                $this->podcastEpisodeRepository->updateDescription($existing['id'], $description);
            }

            return;
        }

        // by default you want to download all the episodes
        $state = PodcastEpisodeStateEnum::PENDING;
        // if you're syncing an old podcast, check the pubdate and skip it if published to the feed before your last sync
        if ($lastSync !== null && $pubdate < $lastSync->getTimestamp()) {
            $state = PodcastEpisodeStateEnum::SKIPPED;
        }

        debug_event(self::class, 'Adding new episode to podcast ' . $podcast->getId() . '... ' . $pubdate, 4);
        $sql = "INSERT INTO `podcast_episode` (`title`, `guid`, `podcast`, `state`, `source`, `website`, `description`, `author`, `category`, `time`, `pubdate`, `addition_time`, `catalog`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        Dba::write($sql, [
            $title,
            $guid,
            $podcast->getId(),
            $state->value,
            $source,
            $website,
            $description,
            $author,
            $category,
            $time,
            $pubdate,
            time(),
            $podcast->getCatalogId(),
        ]);
    }

    /**
     * Reads one episode by whatever identifies it, along with the description a sync may refresh
     *
     * @param list<mixed> $params
     *
     * @return null|array{id: int, description: string}
     */
    private function find_episode(string $where, array $params): ?array
    {
        $db_results = Dba::read('SELECT `id`, `description` FROM `podcast_episode` WHERE ' . $where, $params);

        $row = Dba::fetch_assoc($db_results);
        if ($row === []) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'description' => (string) ($row['description'] ?? ''),
        ];
    }

    /**
     * Finds the episode this feed item was already stored as, null when it is new to us
     *
     * The stored description comes back with it, so an unchanged feed is recognised without a second query.
     *
     * @return null|array{id: int, description: string}
     */
    private function find_existing_episode(
        int $podcastId,
        string $guid,
        string $source,
        string $title,
        int $time,
        int $pubdate,
    ): ?array {
        // a feed item without a guid would otherwise match every episode that has none
        if ($guid !== '') {
            $existing = $this->find_episode('`guid` = ?', [$guid]);
            if ($existing !== null) {
                return $existing;
            }
        }

        $existing = $this->find_episode('`source` = ?', [$source]);
        if ($existing !== null) {
            return $existing;
        }

        // podcast urls can change over time, so the title is checked as well
        if ($title !== '') {
            $existing = $this->find_episode(
                '`podcast` = ? AND `title` = ? AND `time` = ?',
                [$podcastId, $title, $time]
            );
            if ($existing !== null) {
                return $existing;
            }
        }

        // the publication date catches the duplicate/fixed episodes you already have
        return $this->find_episode('`podcast` = ? AND `pubdate` = ?', [$podcastId, $pubdate]);
    }
}
