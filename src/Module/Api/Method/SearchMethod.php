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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Alias of advanced_search
 */
final class SearchMethod implements MethodInterface
{
    public const string ACTION = 'search';

    private AdvancedSearchMethod $advancedSearchMethod;

    public function __construct(
        AdvancedSearchMethod $advancedSearchMethod,
    ) {
        $this->advancedSearchMethod = $advancedSearchMethod;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * Perform an advanced search given passed rules.
     *
     * @param array{
     *     operator?: string,
     *     rule_1?: string,
     *     rule_1_operator?: int,
     *     rule_1_input?: mixed,
     *     type?: string,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        return $this->advancedSearchMethod->handle(
            $gatekeeper,
            $response,
            $output,
            $input,
            $user,
            $apiVersion
        );
    }
}
