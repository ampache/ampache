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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the albums of a genre.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class GenreAlbumsMethod implements MethodInterface
{
    public const string ACTION = 'genre_albums';

    public function __construct(
        private BrowseFactoryInterface $browseFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums associated with the genre in question
     *
     * filter = (string) UID of genre
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * cond   = (string) Apply additional filters to the browse using ';' separated comma string pairs //optional
     * sort   = (string) sort name or comma separated key pair. Order default 'ASC' (name, ASC) //optional
     *
     * @param array{
     *     filter?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
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

        $objectId = (int) $input['filter'];

        $genre = new Tag($objectId);
        if ($genre->isNew()) {
            throw new ResultEmptyException((string) $objectId);
        }

        $browse = $this->browseFactory->create(null, false);

        $browse->set_user_id($user);

        $browse->set_type('album');

        $originalYear = (AmpConfig::get('use_original_year')) ? 'original_year' : 'year';

        [$sort, $order] = match (AmpConfig::get('album_sort')) {
            'name_asc' => ['name', 'ASC'],
            'name_desc' => ['name', 'DESC'],
            'year_asc' => [$originalYear, 'ASC'],
            'year_desc' => [$originalYear, 'DESC'],
            default => ['name_' . $originalYear, 'ASC'],
        };

        $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), [$sort, $order]);

        $browse->set_filter('tag', $objectId);

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'album')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->albums($apiVersion, $results, [], $user, $input['auth'])
        );

        return $response;
    }
}
