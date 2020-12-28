<?php

declare(strict_types=0);

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

namespace Lib\ApiMethods;

use Api;
use Bookmark;
use JSON_Data;
use Session;
use User;
use XML_Data;

/**
 * Class BookmarksMethod
 * @package Lib\ApiMethods
 */
final class BookmarksMethod
{
    private const ACTION = 'bookmarks';

    /**
     * bookmarks
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get information about bookmarked media this user is allowed to manage.
     *
     * @param array $input
     * @return boolean
     */
    public static function bookmarks(array $input)
    {
        $user      = User::get_from_username(Session::username($input['auth']));
        $bookmarks = Bookmark::get_bookmarks_ids($user);
        if (empty($bookmarks)) {
            Api::empty('bookmark', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::bookmarks($bookmarks);
                break;
            default:
                echo XML_Data::bookmarks($bookmarks);
        }
        Session::extend($input['auth']);

        return true;
    }
}
