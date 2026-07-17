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
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Updates a single album, artist or song from its tag data
 *
 * The two live api versions only differ in how they name the object id: version 6 reports it as
 * `id` and version 8 as `filter`, each accepting the other as an alias. The version classes supply
 * that pair of names; everything else is shared.
 */
abstract class AbstractUpdateFromTagsMethod implements MethodInterface
{
    public const string ACTION = 'update_from_tags';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'id';

    // the name the version reports the object id under; overridden per version
    protected const string FILTER_KEY = 'filter';

    /**
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song from the tag data
     *
     * type = (string) 'artist', 'album', 'song'
     * id   = (string) $artist_id, $album_id, $song_id
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
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
        $filter = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        if (!array_key_exists('type', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'type')
            );
        }

        $type     = (string) $input['type'];
        $objectId = (int) $filter;

        // confirm the correct data
        if (!in_array(strtolower($type), ['artist', 'album', 'song'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    static::ACTION,
                    'type'
                )
            );

            return $response;
        }

        $className = ObjectTypeToClassNameMapper::map($type);

        /** @var Album|Artist|Song $item */
        $item = new $className($objectId);
        if ($item->isNew()) {
            throw new ResultEmptyException(
                (string) $objectId,
                'id'
            );
        }

        // update your object
        Catalog::update_single_item($type, $objectId, true);

        $response->getBody()->write(
            $output->success($apiVersion, 'Updated tags for: ' . $objectId . ' (' . $type . ')')
        );

        return $response;
    }
}
