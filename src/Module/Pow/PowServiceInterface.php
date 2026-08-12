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

namespace Ampache\Module\Pow;

use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface PowServiceInterface
{
    /**
     * The interstitial page that solves a challenge and then replays the original request.
     *
     * Used by endpoints that are plain links rather than forms, where there is no markup to embed
     * the challenge into.
     */
    public function createChallengeResponse(ServerRequestInterface $request, string $scope): ResponseInterface;

    /**
     * Whether the given scope has to be answered for with a proof of work.
     *
     * Takes `pow_mode`, the `pow_protected` scope list and the exempt access level into account.
     */
    public function isRequired(string $scope, ?User $user): bool;

    /**
     * Creates a signed challenge. Writes nothing: handing one out is free for the server, which is
     * what stops a flood of requests for challenges from costing anything.
     */
    public function issue(string $scope): PowChallenge;

    /**
     * Checks an answer given as its raw fields.
     *
     * @param array<string, string> $answer `pow_id`, `pow_exp`, `pow_diff`, `pow_sig`, `pow_nonce`
     */
    public function verify(string $scope, array $answer): bool;

    /**
     * Checks the answer carried by a request, wherever it was posted or queried from.
     */
    public function verifyRequest(ServerRequestInterface $request, string $scope): bool;
}
