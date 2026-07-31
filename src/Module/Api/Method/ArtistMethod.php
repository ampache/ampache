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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns a single artist based on the UID of said artist.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class ArtistMethod implements MethodInterface
{
    public const string ACTION = 'artist';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single artist based on the UID of said artist
     *
     * filter  = (string) Alpha-numeric search term
     * include = (array|string) 'albums', 'songs' //optional
     *
     * @param array{
     *     filter?: string,
     *     include?: string|string[],
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
        $objectId = (int) ($input['filter'] ?? 0);
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $artist = $this->modelFactory->createArtist($objectId);
        if ($artist->isNew()) {
            throw new ResultEmptyException((string) $objectId);
        }

        $include = [];
        if (array_key_exists('include', $input)) {
            $includeInput = (is_array($input['include']))
                ? $input['include']
                : explode(',', html_entity_decode((string) $input['include']));

            foreach ($includeInput as $item) {
                if ($item === 'songs' || $item == '1') {
                    $include[] = 'songs';
                }
                if ($item === 'albums' || $item == '1') {
                    $include[] = 'albums';
                }
            }
        }

        $response->getBody()->write(
            $output->artists($apiVersion, [$objectId], $include, $user, $input['auth'], false)
        );

        return $response;
    }
}
