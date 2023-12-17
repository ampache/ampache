<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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

namespace Ampache\Module\Podcast;

use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastRepositoryInterface;
use DateTime;
use DateTimeInterface;
use SimpleXMLElement;

/**
 * Provides functions for podcast-syncing
 */
final class PodcastSyncer implements PodcastSyncerInterface
{
    private PodcastRepositoryInterface $podcastRepository;

    private ModelFactoryInterface $modelFactory;

    private PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader;

    private PodcastDeleterInterface $podcastDeleter;

    public function __construct(
        PodcastRepositoryInterface $podcastRepository,
        ModelFactoryInterface $modelFactory,
        PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader,
        PodcastDeleterInterface $podcastDeleter
    ) {
        $this->podcastRepository        = $podcastRepository;
        $this->modelFactory             = $modelFactory;
        $this->podcastEpisodeDownloader = $podcastEpisodeDownloader;
        $this->podcastDeleter           = $podcastDeleter;
    }

    /**
     * Update the feed and sync all new episodes
     */
    public function sync(
        Podcast $podcast,
        bool $gather = false
    ): bool {
        $feed = $podcast->getFeedUrl();

        debug_event(self::class, 'Syncing feed ' . $feed . ' ...', 4);
        if ($feed === '') {
            return false;
        }
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
     * Sync all podcast-item within the given catalogs
     *
     * @param iterable<Catalog> $catalogs
     *
     * @return int Amount of new episodes
     */
    public function syncForCatalogs(
        iterable $catalogs
    ): int {
        $newEpisodeCount = 0;

        foreach ($catalogs as $catalog) {
            $podcastIds = $catalog->get_podcast_ids();

            foreach ($podcastIds as $podcastId) {
                $podcast = $this->modelFactory->createPodcast($podcastId);

                $this->sync($podcast);

                $episodes = $this->podcastRepository->getEpisodes($podcast, PodcastEpisodeStateEnum::PENDING);

                foreach ($episodes as $episodeId) {
                    $this->podcastEpisodeDownloader->fetch(
                        $this->modelFactory->createPodcastEpisode($episodeId)
                    );

                    $newEpisodeCount++;
                }
            }
        }

        return $newEpisodeCount;
    }

    /**
     * Add podcast episodes
     */
    public function addEpisodes(
        Podcast $podcast,
        SimpleXMLElement $episodes,
        ?DateTimeInterface $lastSync = null,
        bool $gather = false
    ): void {
        foreach ($episodes as $episode) {
            if ($episode) {
                $this->add_episode($podcast, $episode, $lastSync);
            }
        }
        $change   = 0;
        $syncDate = new DateTime();

        // Select episodes to download
        $downloadEpisodes = $this->podcastRepository->getEpisodesEligibleForDownload($podcast);
        foreach ($downloadEpisodes as $episode) {
            $episode->change_state('pending');
            if ($gather) {
                $this->podcastEpisodeDownloader->fetch($episode);

                $change++;
            }
        }

        if ($change > 0) {
            // cleanup old episodes (if available)
            $this->podcastDeleter->deleteEpisode(
                $this->podcastRepository->getEpisodesEligibleForDeletion($podcast)
            );

            $podcast->setEpisodeCount(
                $this->podcastRepository->getEpisodeCount($podcast)
            );
            Catalog::update_mapping('podcast');
            Catalog::update_mapping('podcast_episode');
        }

        $podcast->setLastSyncDate($syncDate);
        $podcast->save();
    }

    /**
     * Adds the provided xml element as new podcast-episode
     */
    private function add_episode(
        Podcast $podcast,
        SimpleXMLElement $episode,
        ?DateTimeInterface $lastSync
    ): void {
        $title       = html_entity_decode((string)$episode->title);
        $website     = (string)$episode->link;
        $guid        = (string)$episode->guid;
        $description = html_entity_decode(Dba::check_length((string)$episode->description, 4096));
        $author      = html_entity_decode(Dba::check_length((string)$episode->author, 64));
        $category    = html_entity_decode((string)$episode->category);
        $source      = '';
        if ($episode->enclosure) {
            $source = (string)$episode->enclosure['url'];
        }
        $itunes   = $episode->children('itunes', true);
        $duration = (string) $itunes->duration;
        // time is missing hour e.g. "15:23"
        if (preg_grep("/^[0-9][0-9]\:[0-9][0-9]$/", array($duration))) {
            $duration = '00:' . $duration;
        }
        // process a time string "03:23:01"
        $ptime = (preg_grep("/[0-9]?[0-9]\:[0-9][0-9]\:[0-9][0-9]/", array($duration)))
            ? date_parse((string)$duration)
            : $duration;
        // process "HH:MM:SS" time OR fall back to a seconds duration string e.g "24325"
        $time = (is_array($ptime))
            ? (int) $ptime['hour'] * 3600 + (int) $ptime['minute'] * 60 + (int) $ptime['second']
            : (int) $ptime;

        $pubdate    = 0;
        $pubdatestr = (string)$episode->pubDate;
        if ($pubdatestr) {
            $pubdate = strtotime($pubdatestr);
        }
        if ($pubdate < 1) {
            debug_event(self::class, 'Invalid episode publication date, skipped', 3);

            return;
        }
        if (empty($source)) {
            debug_event(self::class, 'Episode source URL not found, skipped', 3);

            return;
        }
        // don't keep adding the same episodes
        if (self::get_id_from_guid($guid) > 0) {
            debug_event(self::class, 'Episode guid already exists, skipped', 3);

            return;
        }
        // don't keep adding urls
        if (self::get_id_from_source($source) > 0) {
            debug_event(self::class, 'Episode source URL already exists, skipped', 3);

            return;
        }
        // podcast urls can change over time so check these
        if (self::get_id_from_title($podcast->getId(), $title, $time) > 0) {
            debug_event(self::class, 'Episode title already exists, skipped', 3);

            return;
        }
        // podcast pubdate can be used to skip duplicate/fixed episodes when you already have them
        if (self::get_id_from_pubdate($podcast->getId(), $pubdate) > 0) {
            debug_event(self::class, 'Episode with the same publication date already exists, skipped', 3);

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

        Dba::write($sql, array(
            $title,
            $guid,
            $podcast->getId(),
            $state,
            $source,
            $website,
            $description,
            $author,
            $category,
            $time,
            $pubdate,
            time(),
            $podcast->getCatalogId()
        ));
    }

    /**
     * get_id_from_source
     *
     * Get episode id from the source url.
     */
    private static function get_id_from_source(string $url): int
    {
        $sql        = "SELECT `id` FROM `podcast_episode` WHERE `source` = ?";
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_id_from_guid
     *
     * Get episode id from the guid.
     */
    private static function get_id_from_guid(string $url): int
    {
        $sql        = "SELECT `id` FROM `podcast_episode` WHERE `guid` = ?";
        $db_results = Dba::read($sql, array($url));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_id_from_title
     *
     * Get episode id from the source url.
     */
    private static function get_id_from_title(int $podcast_id, string $title, int $time): int
    {
        $sql        = "SELECT `id` FROM `podcast_episode` WHERE `podcast` = ? AND title = ? AND `time` = ?";
        $db_results = Dba::read($sql, array($podcast_id, $title, $time));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }

    /**
     * get_id_from_pubdate
     *
     * Get episode id from the source url.
     */
    private static function get_id_from_pubdate(int $podcast_id, int $pubdate): int
    {
        $sql        = "SELECT `id` FROM `podcast_episode` WHERE `podcast` = ? AND pubdate = ?";
        $db_results = Dba::read($sql, array($podcast_id, $pubdate));

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int)$results['id'];
        }

        return 0;
    }
}
