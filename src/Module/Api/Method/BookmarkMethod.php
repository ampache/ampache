<?php

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

declare(strict_types=0);

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\BookmarkRepositoryInterface;

/**
 * Class BookmarkMethod
 * @package Lib\ApiMethods
 */
final class BookmarkMethod
{
    public const ACTION = 'bookmark';

    /**
     * bookmark
     * MINIMUM_API_VERSION=6.1.0
     *
     * Get a single bookmark
     *
     * filter  = (string) bookmark_id
     * include = (integer) 0,1, if true include the object in the bookmark //optional
     */
    public static function bookmark(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $bookmark_id = static::getBookmarkRepository()->getBookmark((int)$input['filter'], $user->getId());
        if ($bookmark_id === 0) {
            Api::empty('bookmark', $input['api_format']);

            return false;
        }
        $include = (bool)($input['include'] ?? false);
        $results = array($bookmark_id);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::bookmarks($results, $include, false);
                break;
            default:
                echo Xml_Data::bookmarks($results, $include);
        }

        return true;
    }

    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }
}
