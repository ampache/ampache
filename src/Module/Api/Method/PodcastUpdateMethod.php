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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\User;

/**
 * Class PodcastUpdateMethod
 * @package Lib\ApiMethods
 */
final class PodcastUpdateMethod
{
    public const ACTION = 'podcast_update';

    /**
     * update_podcast
     * MINIMUM_API_VERSION=420000
     *
     * Sync and download new podcast episodes
     *
     * filter = (string) UID of podcast
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type: string,
     *     overwrite: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function update_podcast(array $input, User $user): bool
    {
        return UpdatePodcastMethod::update_podcast($input, $user);
    }
}
