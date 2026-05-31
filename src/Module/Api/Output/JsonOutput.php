<?php

declare(strict_types=1);

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

namespace Ampache\Module\Api\Output;

use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Json8_Data;
use Ampache\Repository\Model\User;

final class JsonOutput implements ApiOutputInterface
{
    public function setOffset6(int|string $offset): void
    {
        Json6_Data::set_offset($offset);
    }

    public function setOffset(int|string $offset): void
    {
        Json8_Data::set_offset($offset);
    }

    public function setLimit6(int|string $limit): void
    {
        Json6_Data::set_limit($limit);
    }

    public function setLimit(int|string $limit): void
    {
        Json8_Data::set_limit($limit);
    }

    public function setCount6(int|string $count): void
    {
        Json6_Data::set_count($count);
    }

    public function setCount(int|string $count): void
    {
        Json8_Data::set_count($count);
    }

    /**
     * @param array<int|string> $result
     */
    public function podcastEpisodes6(array $result, User $user, string $auth): string
    {
        return Json6_Data::podcast_episodes($result, $user, $auth);
    }

    /**
     * @param array<int|string> $result
     */
    public function podcastEpisodes(array $result, User $user, string $auth): string
    {
        return Json8_Data::podcast_episodes($result, $user, $auth);
    }

    /**
     * Generate an empty api result
     */
    public function writeEmpty6(string $emptyType): string
    {
        return Json6_Data::empty($emptyType);
    }

    /**
     * Generate an empty api result
     */
    public function writeEmpty(string $emptyType): string
    {
        return Json8_Data::empty($emptyType);
    }

    /**
     * At the moment, this method just acts as a proxy
     */
    public function error(int $code, string $message, string $action, string $type): string
    {
        return Json8_Data::error(
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
     */
    public function error6(int $code, string $message, string $action, string $type): string
    {
        return Json6_Data::error(
            $code,
            $message,
            $action,
            $type
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param array<int|string> $albums
     * @param string[] $include
     *
     */
    public function albums6(
        array $albums,
        array $include,
        User $user,
        string $auth,
        bool $encode = true,
        bool $asObject = true,
    ): string {
        return Json6_Data::albums($albums, $include, $user, $auth, $encode, $asObject);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param array<int|string> $albums
     * @param string[] $include
     *
     */
    public function albums(
        array $albums,
        array $include,
        User $user,
        string $auth,
        bool $encode = true,
        bool $asObject = true,
    ): string {
        return Json8_Data::albums($albums, $include, $user, $auth, $encode, $asObject);
    }

    /**
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array<string, string> $return_data
     */
    public function success6(string $string, array $return_data = []): string
    {
        return Json6_Data::success($string, $return_data);
    }

    /**
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array<string, string> $return_data
     */
    public function success(string $string, array $return_data = []): string
    {
        return Json8_Data::success($string, $return_data);
    }
}
