<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Json_Data;
use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\BookmarkRepositoryInterface;

/**
 * Class BookmarksMethod
 * @package Lib\ApiMethods
 */
final class BookmarksMethod
{
    public const ACTION = 'bookmarks';

    /**
     * bookmarks
     * MINIMUM_API_VERSION=5.0.0
     *
     * Get information about bookmarked media this user is allowed to manage.
     *
     * client = (string) Filter results to a specific comment/client name //optional
     * include = (integer) 0,1, if true include the object in the bookmark //optional
     *
     * @param array{
     *     client?: string,
     *     include?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function bookmarks(array $input, User $user): bool
    {
        $include = make_bool($input['include'] ?? false);
        $results = (!empty($input['client']))
            ? self::getBookmarkRepository()->getByUserAndComment($user, scrub_in((string) $input['client']))
            : self::getBookmarkRepository()->getByUser($user);
        if (empty($results)) {
            Api::empty('bookmark', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json_Data::bookmarks($results, $input['auth'], $include);
                break;
            default:
                echo Xml_Data::bookmarks($results, $input['auth'], $include);
        }

        return true;
    }

    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }
}
