<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    return false;
}

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'sync':
        if (!Access::check('interface', 75)) {
            debug_event('podcast.ajax', Core::get_global('user')->username . ' attempted to sync podcast', 1);

            return false;
        }

        if (isset($_REQUEST['podcast_id'])) {
            $podcast = new Podcast($_REQUEST['podcast_id']);
            if ($podcast->id) {
                $podcast->sync_episodes(true);
            } else {
                debug_event('podcast.ajax', 'Cannot find podcast', 1);
            }
        } elseif (isset($_REQUEST['podcast_episode_id'])) {
            $episode = new Podcast_Episode($_REQUEST['podcast_episode_id']);
            if ($episode->id !== null) {
                $episode->gather();
            } else {
                debug_event('podcast.ajax', 'Cannot find podcast episode', 1);
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
