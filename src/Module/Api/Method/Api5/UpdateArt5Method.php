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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Gathers new art for an artist or album.
 *
 * Version 5 reads the object id from `id` only and checks the parameters before the access level,
 * so it keeps a method of its own.
 */
final class UpdateArt5Method implements MethodInterface
{
    public const string ACTION = 'update_art';

    public function __construct(
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * update_art
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song running the gather_art process
     * Existing art is replaced unless you send overwrite=0, which keeps whatever is already there.
     *
     * type = (string) 'artist', 'album'
     * id = (integer) $artist_id, $album_id
     * overwrite = (integer) 0,1 //optional
     *
     * @param array{
     *     id: string,
     *     type: string,
     *     overwrite?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
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
        foreach (['type', 'id'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::MANAGER,
                $user->id
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::MANAGER->value)
            );
        }

        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        // Catalog::gather_art_item() takes `db_art_first`, i.e. the inverse: keep the art we already have
        $db_art_first = array_key_exists('overwrite', $input) && (int) $input['overwrite'] == 0;
        $art_url      = Art::url($object_id, $type, $input['auth']);

        // confirm the correct data
        if (!in_array(strtolower($type), ['artist', 'album'])) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $type),
                        self::ACTION,
                        'type'
                    )
                )
            );
        }

        $className = ObjectTypeToClassNameMapper::map($type);

        /** @var Artist|Album $item */
        $item = new $className($object_id);
        if ($item->isNew() || $art_url === null) {
            throw new ResultEmptyException(
                (string) $object_id,
                'id'
            );
        }

        // update your object
        if (!Catalog::gather_art_item($type, $object_id, $db_art_first, true)) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $object_id),
                        self::ACTION,
                        'system'
                    )
                )
            );
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    $apiVersion,
                    'Gathered new art for: ' . $object_id . ' (' . $type . ')',
                    ['art' => $art_url]
                )
            )
        );
    }
}
