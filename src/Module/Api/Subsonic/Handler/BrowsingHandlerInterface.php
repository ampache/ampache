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

namespace Ampache\Module\Api\Subsonic\Handler;

use Ampache\Repository\Model\User;

interface BrowsingHandlerInterface
{
    /**
     * @param array<string, mixed> $input
     */
    public function getalbum(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getalbuminfo(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getalbuminfo2(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getalbumlist(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getalbumlist2(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getartist(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getartistinfo(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getartistinfo2(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getartists(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getgenres(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getindexes(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getmusicdirectory(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getmusicfolders(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getrandomsongs(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs(array $input, User $user, string $elementName = 'similarSongs'): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs2(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getsong(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getsongsbygenre(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function gettopsongs(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getvideoinfo(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getvideos(array $input, User $user): void;
}
