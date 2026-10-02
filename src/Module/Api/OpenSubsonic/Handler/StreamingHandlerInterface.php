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

namespace Ampache\Module\Api\OpenSubsonic\Handler;

use Ampache\Repository\Model\User;

interface StreamingHandlerInterface
{
    /**
     * @param array<string, mixed> $input
     */
    public function download(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getavatar(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getcaptions(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getcoverart(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getlyrics(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getlyricsbysongid(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getTranscodeDecision(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function getTranscodeStream(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function hls(array $input, User $user): void;

    /**
     * @param array<string, mixed> $input
     */
    public function stream(array $input, User $user): void;
}
