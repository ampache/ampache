<?php

/**
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

declare(strict_types=1);

namespace Ampache\Module\Api\Output;

use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Json_Data;
use Ampache\Repository\Model\User;

final class JsonOutput implements ApiOutputInterface
{
    /**
     * @param list<int> $result
     */
    public function podcastEpisodes(array $result, User $user): string
    {
        return Json_Data::podcast_episodes($result, $user);
    }

    public function setOffset(int $offset): void
    {
        Json_Data::set_offset($offset);
    }

    public function setLimit(int $limit): void
    {
        Json_Data::set_limit($limit);
    }

    /**
     * Generate an empty api result
     */
    public function writeEmpty(string $emptyType): string
    {
        return Json_Data::empty($emptyType);
    }

    /**
     * At the moment, this method just acts as a proxy
     */
    public function error(int $code, string $message, string $action, string $type): string
    {
        return Json_Data::error(
            $code,
            $message,
            $action,
            $type
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     */
    public function error3(int $code, string $message): string
    {
        return '';
    }

    /**
     * At the moment, this method just acts as a proxy
     */
    public function error4(int $code, string $message): string
    {
        return Json4_Data::error(
            (string)$code,
            $message
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     */
    public function error5(int $code, string $message, string $action, string $type): string
    {
        return Json5_Data::error(
            $code,
            $message,
            $action,
            $type
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $albums
     * @param array $include
     * @param User $user
     * @param bool $encode
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function albums(
        array $albums,
        array $include,
        User $user,
        bool $encode = true,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Json_Data::set_offset($offset);
        Json_Data::set_limit($limit);

        return Json_Data::albums($albums, $include, $user, $encode, $asObject);
    }

    /**
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array<mixed> $return_data
     */
    public function success(string $string, array $return_data = []): string
    {
        return Json_Data::success($string, $return_data);
    }
}
