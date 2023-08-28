<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;

/**
 * Class PodcastEpisodeDelete4Method
 */
final class PodcastEpisodeDelete4Method
{
    public const ACTION = 'podcast_episode_delete';

    /**
     * podcast_episode_delete
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing podcast_episode.
     *
     * @param array $input
     * @param User $user
     * filter = (string) UID of podcast_episode to delete
     * @return boolean
     */
    public static function podcast_episode_delete(array $input, User $user): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api4::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_access('interface', 75, $user->id, 'update_podcast', $input['api_format'])) {
            return false;
        }
        if (!Api4::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $episode   = new Podcast_Episode($object_id);
        if (!isset($episode->id)) {
            Api4::message('error', 'podcast_episode ' . $object_id . ' was not found', '404', $input['api_format']);
        } else {
            if ($episode->remove()) {
                Api4::message('success', 'podcast_episode ' . $object_id . ' deleted', null, $input['api_format']);
                Catalog::count_table('podcast_episode');
            } else {
                Api4::message('error', 'podcast_episode ' . $object_id . ' was not deleted', '401', $input['api_format']);
            }
        }

        return true;
    } // podcast_episode_delete
}
