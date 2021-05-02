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

declare(strict_types=0);

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Authorization\Access;
use Ampache\Module\Podcast\PodcastEpisodeDownloaderInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;

final class PodcastAjaxHandler implements AjaxHandlerInterface
{
    private PodcastSyncerInterface $podcastSyncer;

    private PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        PodcastSyncerInterface $podcastSyncer,
        PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->podcastSyncer            = $podcastSyncer;
        $this->podcastEpisodeDownloader = $podcastEpisodeDownloader;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->podcastRepository        = $podcastRepository;
    }

    public function handle(): void
    {
        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'sync':
                if (!Access::check('interface', 75)) {
                    debug_event('podcast.ajax', Core::get_global('user')->username . ' attempted to sync podcast', 1);

                    return;
                }

                if (isset($_REQUEST['podcast_id'])) {
                    $podcast = $this->podcastRepository->findById((int) $_REQUEST['podcast_id']);
                    if ($podcast !== null) {
                        $this->podcastSyncer->sync($podcast, true);
                    } else {
                        debug_event('podcast.ajax', 'Cannot find podcast', 1);
                    }
                } elseif (isset($_REQUEST['podcast_episode_id'])) {
                    $episode = $this->podcastEpisodeRepository->findById(
                        (int) $_REQUEST['podcast_episode_id']
                    );
                    if ($episode === null) {
                        debug_event('podcast.ajax', 'Cannot find podcast episode', 1);
                    } else {
                        $this->podcastEpisodeDownloader->download($episode);
                    }
                }
                $results['rfc3514'] = '0x1';
                break;
            default:
                $results['rfc3514'] = '0x1';
                break;
        }

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
