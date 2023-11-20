<?php

declare(strict_types=0);

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;

final class PodcastAjaxHandler implements AjaxHandlerInterface
{
    private RequestParserInterface $requestParser;

    public function __construct(
        RequestParserInterface $requestParser
    ) {
        $this->requestParser = $requestParser;
    }

    public function handle(): void
    {
        $results = array();
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'sync':
                if (!Access::check('interface', 75)) {
                    debug_event('podcast.ajax', Core::get_global('user')->username . ' attempted to sync podcast', 1);

                    return;
                }

                if (array_key_exists('podcast_id', $_REQUEST)) {
                    $podcast = new Podcast($_REQUEST['podcast_id']);
                    if ($podcast->id) {
                        $podcast->sync_episodes(true);
                    } else {
                        debug_event('podcast.ajax', 'Cannot find podcast', 1);
                    }
                } elseif (array_key_exists('podcast_episode_id', $_REQUEST)) {
                    $episode = new Podcast_Episode($_REQUEST['podcast_episode_id']);
                    if (!isset($episode->id)) {
                        debug_event('podcast.ajax', 'Cannot find podcast episode', 1);
                    } else {
                        $episode->gather();
                    }
                }
        }
        $results['rfc3514'] = '0x1';

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
