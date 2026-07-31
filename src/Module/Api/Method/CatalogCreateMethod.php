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
 * Alias of catalog_add
 */
final class CatalogCreateMethod implements MethodInterface
{
    public const string ACTION = 'catalog_create';

    public const string REST_ACTION = 'catalogs_create';

    private CatalogAddMethod $catalogAddMethod;

    public function __construct(
        CatalogAddMethod $catalogAddMethod,
    ) {
        $this->catalogAddMethod = $catalogAddMethod;
    }

    /**
     * MINIMUM_API_VERSION=6.0.0
     *
     * Create a new catalog
     *
     * @param array{
     *     name?: string,
     *     path?: string,
     *     type?: string,
     *     beetsdb?: string,
     *     media_type?: string,
     *     file_pattern?: string,
     *     folder_pattern?: string,
     *     username?: string,
     *     password?: string,
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
        return $this->catalogAddMethod->handle(
            $gatekeeper,
            $response,
            $output,
            $input,
            $user,
            $apiVersion
        );
    }
}
