<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Module\Api\Api6;
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\User;

/**
 * Class Bookmark6Method
 * @package Lib\Api6Methods
 */
final class Bookmark6Method
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
     *
     * @param array{
     *     filter: string,
     *     include?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function bookmark(array $input, User $user): bool
    {
        if (!Api6::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $bookmark = self::getBookmarkRepository()->findById((int) $input['filter']);

        if (
            $bookmark === null ||
            !$bookmark->ownedByUser($user)
        ) {
            Api6::empty(null, $input['api_format']);

            return false;
        }

        $include = make_bool($input['include'] ?? false);
        $results = [$bookmark->getId()];

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo Json6_Data::bookmarks($results, $input['auth'], $include, false);
                break;
            default:
                echo Xml6_Data::bookmarks($results, $input['auth'], $include);
        }

        return true;
    }

    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }
}
