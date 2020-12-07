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

namespace Lib\ApiMethods;

use AmpConfig;
use Api;
use JSON_Data;
use Session;
use User;
use XML_Data;

/**
 * Class VideosMethod
 * @package Lib\ApiMethods
 */
final class VideosMethod
{
    private const ACTION = 'videos';

    /**
     * videos
     * This returns video objects!
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, Whether to match the exact term or not //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return bool
     */
    public static function videos(array $input)
    {
        if (!AmpConfig::get('allow_video')) {
            Api::error(T_('Enable: video'), '4703', self::ACTION, 'system', $input['api_format']);

            return false;
        }
        Api::$browse->reset_filters();
        Api::$browse->set_type('video');
        Api::$browse->set_sort('title', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter']);

        $video_ids = Api::$browse->get_objects();
        $user      = User::get_from_username(Session::username($input['auth']));
        if (empty($video_ids)) {
            Api::empty('video', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::videos($video_ids, $user->id);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::videos($video_ids, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    }
}
