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
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Controls democratic play
 */
final class DemocraticMethod implements MethodInterface
{
    public const string ACTION = 'democratic';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling democratic play
     *
     * method = (string) 'vote', 'devote', 'playlist', 'play'
     * oid    = (string) //optional
     *
     * @param array{
     *     method?: string,
     *     oid?: int,
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
        if (!array_key_exists('method', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'method')
            );
        }

        $method = (string) $input['method'];

        // Load up democratic information
        $democratic = Democratic::get_current_playlist($user);
        $democratic->set_parent();

        switch ($method) {
            case 'vote':
                $media = $this->resolveSong($input);

                $democratic->add_vote([['song', $media->getId()]]);

                $response->getBody()->write(
                    $output->keyedArray($apiVersion, ['method' => $method, 'result' => true])
                );

                return $response;
            case 'devote':
                $media = $this->resolveSong($input);

                $objectId = $democratic->get_uid_from_object_id($media->getId(), 'song');
                if ($objectId) {
                    $democratic->remove_vote($objectId);
                }

                $response->getBody()->write(
                    $output->keyedArray($apiVersion, ['method' => $method, 'result' => true])
                );

                return $response;
            case 'playlist':
                $results = $democratic->get_items();

                Song::build_cache($democratic->object_ids);
                Democratic::build_vote_cache($democratic->vote_ids);

                $response->getBody()->write(
                    $output->democratic($apiVersion, $results, $user, $input['auth'])
                );

                return $response;
            case 'play':
                $response->getBody()->write(
                    $output->keyedArray($apiVersion, ['url' => $democratic->play_url($user)])
                );

                return $response;
            default:
                $response->getBody()->write(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        'Invalid Request',
                        self::ACTION,
                        'method'
                    )
                );

                return $response;
        }
    }

    /**
     * @param array<string, mixed> $input
     * @throws ResultEmptyException
     */
    private function resolveSong(array $input): Song
    {
        $objectId = (int) ($input['oid'] ?? 0);
        $media    = $this->modelFactory->createSong($objectId);

        if ($media->isNew()) {
            throw new ResultEmptyException(
                (string) $objectId,
                'oid'
            );
        }

        return $media;
    }
}
