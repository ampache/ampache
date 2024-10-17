<?php

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

namespace Ampache\Module\Api\Output;

use Ampache\Repository\Model\User;

interface ApiOutputInterface
{
    public function setOffset(int $offset): void;

    public function setLimit(int $limit): void;

    public function setCount(int $count): void;

    /**
     * @param list<int> $result
     */
    public function podcastEpisodes(
        array $result,
        User $user
    ): string;

    /**
     * Generate an empty api result
     */
    public function writeEmpty(
        string $emptyType
    ): string;

    /**
     * This generates an error message
     */
    public function error(
        int $code,
        string $message,
        string $action,
        string $type
    ): string;

    /**
     * This generates an error message
     */
    public function error3(
        int $code,
        string $message
    ): string;

    /**
     * This generates an error message
     */
    public function error4(
        int $code,
        string $message
    ): string;

    /**
     * This generates an error message
     */
    public function error5(
        int $code,
        string $message,
        string $action,
        string $type
    ): string;

    /**
     * @param int[] $albums
     * @param array $include
     * @param User $user
     * @param bool $encode
     * @param bool $asObject
     *
     * @return array|string
     */
    public function albums(
        array $albums,
        array $include,
        User $user,
        bool $encode = true,
        bool $asObject = true
    );

    /**
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array<mixed> $return_data
     */
    public function success(string $string, array $return_data = []): string;
}
