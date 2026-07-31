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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the songs of a smartlist.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class SmartlistSongsMethod implements MethodInterface
{
    public const string ACTION = 'smartlist_songs';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=6.4.0
     *
     * This returns the songs for a smartlist
     *
     * filter = (string) UID of smartlist
     * random = (integer) 0,1, if true get random songs using limit //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     *
     * @param array{
     *     filter?: string,
     *     random?: int,
     *     offset?: int,
     *     limit?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 6|8 $apiVersion
     * @throws RequestParamMissingException|ResultEmptyException
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

        $objectId = (string) $input['filter'];
        $random   = (array_key_exists('random', $input) && (int) $input['random'] == 1);

        $smartlist = $this->modelFactory->createSmartlist(
            (int) str_replace('smart_', '', $objectId),
            $user
        );

        if ($smartlist->isNew()) {
            throw new ResultEmptyException($objectId);
        }

        if (
            $smartlist->type !== 'public'
            && !$smartlist->has_collaborate($user)
        ) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::FAILED_ACCESS_CHECK,
                    'Require: 100',
                    self::ACTION,
                    'account'
                )
            );

            return $response;
        }

        $items = ($random)
            ? $smartlist->get_random_items()
            : $smartlist->get_items();

        if ($items === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'song')
            );

            return $response;
        }

        $results = [];
        foreach ($items as $object) {
            if ($object['object_type'] === LibraryItemEnum::SONG) {
                $results[] = $object['object_id'];
            }
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->songs($apiVersion, $results, $user, $input['auth'])
        );

        return $response;
    }
}
