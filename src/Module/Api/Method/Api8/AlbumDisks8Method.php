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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the disks belonging to an album
 *
 * The counterpart of `album_songs`: a disk is a child of an album, so this is reached as
 * `/albums/{id}/disks` in REST. Only api version 8 knows about album disks.
 */
final class AlbumDisks8Method implements MethodInterface
{
    public const string ACTION = 'album_disks';

    private BrowseFactoryInterface $browseFactory;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        BrowseFactoryInterface $browseFactory,
    ) {
        $this->browseFactory = $browseFactory;
        $this->modelFactory  = $modelFactory;
    }

    /**
     * album_disks
     * MINIMUM_API_VERSION=800000
     *
     * This returns the album disks of an album
     *
     * filter = (string) UID of Album
     * include = (array|string) 'songs' //optional
     * offset = (integer) //optional
     * limit = (integer) //optional
     * cond = (string) Apply additional filters to the browse using ';' separated comma string pairs (e.g. 'filter1,value1;filter2,value2') //optional
     * sort = (string) sort name or comma separated key pair. Order default 'ASC' (e.g. 'name,ASC' and 'name' are the same) //optional
     *
     * @param array{
     *     filter?: string,
     *     include?: string|string[],
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     *
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
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

        $album = $this->modelFactory->createAlbum($objectId);
        if ($album->isNew()) {
            throw new ResultEmptyException((string) $objectId);
        }

        $browse = $this->browseFactory->create(null, false);
        $browse->set_user_id($user);
        $browse->set_type('album_disk');
        $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), ['disk', 'ASC']);
        $browse->set_filter('album', $objectId);
        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'album_disk')
            );

            return $response;
        }

        $include = [];
        if (array_key_exists('include', $input)) {
            if (is_array($input['include'])) {
                foreach ($input['include'] as $item) {
                    if ($item === 'songs' || $item == '1') {
                        $include[] = 'songs';
                    }
                }
            } elseif ($input['include'] === 'songs' || $input['include'] == '1') {
                $include[] = 'songs';
            }
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);
        $output->setCount($apiVersion, count($results));

        $response->getBody()->write(
            $output->albumDisks($apiVersion, $results, $include, $user, $input['auth'])
        );

        return $response;
    }
}
