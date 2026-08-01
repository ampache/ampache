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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Gathers new art for an artist or album
 *
 * The two live api versions only differ in how they name the object id: version 6 reports it as
 * `id` and version 8 as `filter`, each accepting the other as an alias. The version classes supply
 * that pair of names; everything else is shared.
 */
abstract class AbstractUpdateArtMethod implements MethodInterface
{
    public const string ACTION = 'update_art';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'id';

    // the name the version reports the object id under; overridden per version
    protected const string FILTER_KEY = 'filter';

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Updates a single album, artist, song running the gather script
     *
     * Existing art is replaced unless you send overwrite=0, which keeps whatever is already there.
     *
     * id        = (string) $artist_id, $album_id
     * type      = (string) 'artist', 'album'
     * overwrite = (integer) 0,1 //optional
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type?: string,
     *     overwrite?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::MANAGER,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::MANAGER->value)
            );
        }

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

        $type      = (string) $input['type'];
        $objectId  = (int) $filter;
        // Catalog::gather_art_item() takes `db_art_first`, i.e. the inverse: keep the art we already have
        $db_art_first = array_key_exists('overwrite', $input) && (int) $input['overwrite'] === 0;
        $artUrl       = Art::url($objectId, $type, $input['auth']);

        // confirm the correct data
        if (!in_array(strtolower($type), ['artist', 'album'])) {
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

        /** @var Album|Artist $item */
        $item = new $className($objectId);
        if ($item->isNew() || $artUrl === null) {
            throw new ResultEmptyException(
                (string) $objectId,
                'id'
            );
        }

        // update your object
        if (!Catalog::gather_art_item($type, $objectId, $db_art_first, true)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $objectId),
                    static::ACTION,
                    'system'
                )
            );

            return $response;
        }

        $response->getBody()->write(
            $output->success(
                $apiVersion,
                'Gathered new art for: ' . $objectId . ' (' . $type . ')',
                ['art' => $artUrl]
            )
        );

        return $response;
    }
}
