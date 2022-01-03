<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\System\Session;

/**
 * Class Video4Method
 */
final class Video4Method
{
    public const ACTION = 'video';

    /**
     * video
     * This returns a single video
     *
     * @param array $input
     * filter = (string) UID of video
     * @return boolean
     */
    public static function video(array $input): bool
    {
        if (!Api4::check_parameter($input, array('filter'), 'video')) {
            return false;
        }
        $video_id = scrub_in($input['filter']);
        $user     = User::get_from_username(Session::username($input['auth']));

        switch ($input['api_format']) {
            case 'json':
                echo Json4_Data::videos(array($video_id), $user->id);
            break;
            default:
                echo Xml4_Data::videos(array($video_id), $user->id);
        }

        return true;
    } // video
}
