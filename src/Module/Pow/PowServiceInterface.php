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
     * Tells the browser that a delivery has actually begun.
     *
     * The interstitial cannot see when a download commits: the response goes into a hidden frame and
     * a large archive is written in full before its headers are sent. This echoes the acknowledgement
     * token the interstitial sent as a cookie, which reaches the client with those headers and at no
     * other moment, so the page can return the visitor the instant leaving is safe.
     *
     * Returns the response untouched when there is no token to echo, so it is safe to call on any
     * response.
     */
    public function confirmDelivery(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * The interstitial page that solves a challenge and then replays the original request.
     *
     * For endpoints that are plain links, where there is no markup to embed the challenge into. The
     * replay is a GET form, so a non-GET request gets a 405 rather than being silently stripped of
     * its body; a form should carry PowWidgetView inline instead, the way `register` does.
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
    public function verify(string $scope, array $answer, ?User $user = null): bool;

    /**
     * Checks the answer carried by a request, from the body or the query string: the inline widget
     * posts it with its form, the interstitial replays it as a query.
     *
     * The user is only there so a blocked attempt can be attributed when `pow_log_failures` is on.
     */
    public function verifyRequest(ServerRequestInterface $request, string $scope, ?User $user = null): bool;
}
