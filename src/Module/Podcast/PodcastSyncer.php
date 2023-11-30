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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use SimpleXMLElement;

final class PodcastSyncer implements PodcastSyncerInterface
{
    /**
     * Update the feed and sync all new episodes
     */
    public function sync(
        Podcast $podcast,
        bool $gather = false
    ): bool {
        debug_event(self::class, 'Syncing feed ' . $podcast->feed . ' ...', 4);
        if ($podcast->feed === null) {
            return false;
        }
        $xmlstr = file_get_contents($podcast->feed, false, stream_context_create(Core::requests_options()));
        if ($xmlstr === false) {
            debug_event(self::class, 'Cannot access feed ' . $podcast->feed, 1);

            return false;
        }
        $xml = simplexml_load_string($xmlstr);
        if ($xml === false) {
            // I've seems some &'s in feeds that screw up
            $xml = simplexml_load_string(str_replace('&', '&amp;', $xmlstr));
        }
        if ($xml === false) {
            debug_event(self::class, 'Cannot read feed ' . $podcast->feed, 1);

            return false;
        }

        $this->addEpisodes($podcast, $xml->channel->item, $podcast->lastsync, $gather);

        return true;
    }

    /**
     * Add podcast episodes
     */
    public function addEpisodes(
        Podcast $podcast,
        SimpleXMLElement $episodes,
        int $lastSync = 0,
        bool $gather = false
    ): void {
        foreach ($episodes as $episode) {
            if ($episode) {
                $this->add_episode($podcast, $episode, $lastSync);
            }
        }
        $change = 0;
        $time   = time();
        $params = array($podcast->id);

        // Select episodes to download
        $dlnb = (int)AmpConfig::get('podcast_new_download');
        if ($dlnb <> 0) {
            $sql = "SELECT `podcast_episode`.`id` FROM `podcast_episode` INNER JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`id` = ? AND (`podcast_episode`.`addition_time` > `podcast`.`lastsync` OR `podcast_episode`.`state` = 'pending') ORDER BY `podcast_episode`.`pubdate` DESC";
            if ($dlnb > 0) {
                $sql .= " LIMIT " . (string)$dlnb;
            }
            $db_results = Dba::read($sql, $params);
            while ($row = Dba::fetch_row($db_results)) {
                $episode = new Podcast_Episode($row[0]);
                $episode->change_state('pending');
                if ($gather) {
                    $episode->gather();
                    $change++;
                }
            }
        }
        if ($change > 0) {
            // Remove items outside limit
            $keepnb = AmpConfig::get('podcast_keep');
            if ($keepnb > 0) {
                $sql        = "SELECT `podcast_episode`.`id` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ? ORDER BY `podcast_episode`.`pubdate` DESC LIMIT " . $keepnb . ",18446744073709551615";
                $db_results = Dba::read($sql, $params);
                while ($row = Dba::fetch_row($db_results)) {
                    $episode = new Podcast_Episode($row[0]);
                    $episode->remove();
                }
            }
            // update the episode count after adding / removing episodes
            $sql = "UPDATE `podcast`, (SELECT COUNT(`podcast_episode`.`id`) AS `episodes`, `podcast` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ? GROUP BY `podcast_episode`.`podcast`) AS `episode_count` SET `podcast`.`episodes` = `episode_count`.`episodes` WHERE `podcast`.`episodes` != `episode_count`.`episodes` AND `podcast`.`id` = `episode_count`.`podcast`;";
            Dba::write($sql, $params);
            Catalog::update_mapping('podcast');
            Catalog::update_mapping('podcast_episode');
            Catalog::count_table('podcast_episode');
        }
        $this->update_lastsync($podcast, $time);
    }

    /**
     * add_episode
     */
    private function add_episode(Podcast $podcast, SimpleXMLElement $episode, int $lastSync = 0): void
    {
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
        if (self::get_id_from_title($podcast->id, $title, $time) > 0) {
            debug_event(self::class, 'Episode title already exists, skipped', 3);

            return;
        }
        // podcast pubdate can be used to skip duplicate/fixed episodes when you already have them
        if (self::get_id_from_pubdate($podcast->id, $pubdate) > 0) {
            debug_event(self::class, 'Episode with the same publication date already exists, skipped', 3);

            return;
        }

        // by default you want to download all the episodes
        $state = 'pending';
        // if you're syncing an old podcast, check the pubdate and skip it if published to the feed before your last sync
        if ($lastSync > 0 && $pubdate < $lastSync) {
            $state = 'skipped';
        }

        debug_event(self::class, 'Adding new episode to podcast ' . $podcast->id . '... ' . $pubdate, 4);
        $sql = "INSERT INTO `podcast_episode` (`title`, `guid`, `podcast`, `state`, `source`, `website`, `description`, `author`, `category`, `time`, `pubdate`, `addition_time`, `catalog`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        Dba::write($sql, array(
            $title,
            $guid,
            $podcast->id,
            $state,
            $source,
            $website,
            $description,
            $author,
            $category,
            $time,
            $pubdate,
            time(),
            $podcast->catalog
        ));
    }

    /**
     * update_lastsync
     */
    private function update_lastsync(Podcast $podcast, int $time): void
    {
        Dba::write(
            'UPDATE `podcast` SET `lastsync` = ? WHERE `id` = ?',
            [
                $time,
                $podcast->id,
            ],
        );
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
