<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\AlbumRepositoryInterface;

/**
 * Class Stats3Method
 */
final class Stats3Method
{
    public const ACTION = 'stats';

    /**
     * This get library stats.
     * @param array $input
     * @param User $user
     */
    public static function stats(array $input, User $user)
    {
        $type     = $input['type'];
        $offset   = $input['offset'];
        $limit    = $input['limit'];
        $username = $input['username'];
        // override your user if you're looking at others
        if (array_key_exists('username', $input)) {
            $user    = User::get_from_username($input['username']);
        }
        $results = null;
        if ($type == "newest") {
            $results = Stats::get_newest("album", $limit, $offset);
        } else {
            if ($type == "highest") {
                $results = Rating::get_highest("album", $limit, $offset);
            } else {
                if ($type == "frequent") {
                    $results = Stats::get_top("album", $limit, '', $offset);
                } else {
                    if ($type == "recent") {
                        if (!empty($username)) {
                            if ($user !== null) {
                                $results = $user->get_recently_played('album', $limit);
                            } else {
                                debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
                            }
                        } else {
                            $results = Stats::get_recent('album', $limit, $offset);
                        }
                    } else {
                        if ($type == "flagged") {
                            $results = Userflag::get_latest('album');
                        } else {
                            if (!$limit) {
                                $limit = AmpConfig::get('popular_threshold');
                            }
                            $results = static::getAlbumRepository()->getRandom($user->id, $limit);
                        }
                    }
                }
            }
        }

        if ($results !== null) {
            ob_end_clean();
            echo Xml3_Data::albums($results, array(), $user);
        }
    } // stats

    /**
     * @deprecated Inject by constructor
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }
}
