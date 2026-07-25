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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Deletes a smartlist
 */
final class SmartlistDeleteMethod implements MethodInterface
{
    public const string ACTION = 'smartlist_delete';

    public const string REST_ACTION = 'smartlists_delete';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This deletes a smartlist
     *
     * filter = (string) UID of smartlist
     *
     * @param array{
     *     filter?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $smartlist = $this->modelFactory->createSearch(
            (int) str_replace('smart_', '', $input['filter']),
            'song',
            $user
        );
        if (!$smartlist->has_access($user)) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $smartlist->delete();

        $response->getBody()->write(
            $output->success($apiVersion, 'smartlist deleted')
        );

        return $response;
    }
}
