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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the md5 hash for the songs in a playlist
 */
final class PlaylistHashMethod implements MethodInterface
{
    public const string ACTION = 'playlist_hash';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=6.6.0
     *
     * This returns the md5 hash for the songs in a playlist
     *
     * filter = (string) UID of playlist
     *
     * @param array{
     *     filter?: string,
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
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId = (string) $input['filter'];

        // a zero id means it is a smartlist, which lives in the search table
        $playlist = ((int) $objectId === 0)
            ? $this->modelFactory->createSmartlist((int) str_replace('smart_', '', $objectId), $user)
            : $this->modelFactory->createPlaylist((int) $objectId);

        if ($playlist->isNew()) {
            throw new ResultEmptyException(
                $objectId
            );
        }

        if (
            $playlist->type !== 'public'
            && !$playlist->has_collaborate($user)
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        debug_event(self::class, 'User ' . $user->getId() . ' loading playlist: ' . $objectId, 5);

        $items = $playlist->get_items();

        $response->getBody()->write(
            $output->keyedArray(
                $apiVersion,
                ['md5' => (empty($items)) ? null : md5(serialize($items))]
            )
        );

        return $response;
    }
}
