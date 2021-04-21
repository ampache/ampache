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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

final class PodcastEpisodeCreator implements PodcastEpisodeCreatorInterface
{
    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private LoggerInterface $logger;

    public function __construct(
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        LoggerInterface $logger
    ) {
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->logger                   = $logger;
    }

    public function create(
        PodcastInterface $podcast,
        SimpleXMLElement $episode,
        int $afterdate = 0
    ): bool {
        $this->logger->info(
            sprintf('Adding new episode to podcast %d...', $podcast->getId()),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $source      = '';
        if ($episode->enclosure) {
            $source = (string) $episode->enclosure['url'];
        }

        $duration = (string) $episode->children('itunes', true)->duration;

        // time is missing hour e.g. "15:23"
        if (preg_grep("/^[0-9][0-9]\:[0-9][0-9]$/", [$duration])) {
            $duration = '00:' . $duration;
        }
        // process a time string "03:23:01"
        $ptime = (preg_grep("/[0-9][0-9]\:[0-9][0-9]\:[0-9][0-9]/", [$duration]))
            ? date_parse((string)$duration)
            : $duration;
        // process "HH:MM:SS" time OR fall back to a seconds duration string e.g "24325"
        $time = (is_array($ptime))
            ? (int) $ptime['hour'] * 3600 + (int) $ptime['minute'] * 60 + (int) $ptime['second']
            : (int) $ptime;

        $pubdate    = 0;
        $pubdatestr = (string) $episode->pubDate;
        if ($pubdatestr) {
            $pubdate = strtotime($pubdatestr);
        }
        if ($pubdate < 1) {
            $this->logger->error(
                'Invalid episode publication date, skipped',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        if ($pubdate > $afterdate) {
            return $this->podcastEpisodeRepository->create(
                $podcast,
                html_entity_decode((string) $episode->title),
                (string) $episode->guid,
                $source,
                (string) $episode->link,
                html_entity_decode((string) $episode->description),
                html_entity_decode((string) $episode->author),
                html_entity_decode((string) $episode->category),
                $time,
                $pubdate
            );
        } else {
            $this->logger->info(
                sprintf('Episode published before %d (%d), skipped', $afterdate, $pubdate),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return true;
        }
    }
}
