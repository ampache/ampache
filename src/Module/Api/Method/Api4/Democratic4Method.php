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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Playback\Democratic;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Democratic4Method implements MethodInterface
{
    public const string ACTION = 'democratic';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * democratic
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling democratic play
     *
     * method = (string) 'vote', 'devote', 'playlist', 'play'
     * oid = (integer) //optional
     *
     * @param array{
     *     method: string,
     *     oid?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!Api4::check_parameter($input, ['method'], self::ACTION)) {
            return $response;
        }

        $democratic = Democratic::get_current_playlist($user);
        $democratic->set_parent();

        switch ($input['method']) {
            case 'vote':
                $media = new Song((int) ($input['oid'] ?? 0));
                if ($media->isNew()) {
                    Api4::message('error', 'Media object invalid or not specified', '400', $input['api_format']);

                    return $response;
                }

                $democratic->add_vote(
                    [
                        [
                            'song',
                            $media->id
                        ]
                    ]
                );

                return $response->withBody(
                    $this->streamFactory->createStream(
                        $this->keyed(['method' => $input['method'], 'result' => true], $input['api_format'])
                    )
                );
            case 'devote':
                $media = new Song((int) ($input['oid'] ?? 0));
                if ($media->isNew()) {
                    Api4::message('error', 'Media object invalid or not specified', '400', $input['api_format']);

                    return $response;
                }

                $object_id = $democratic->get_uid_from_object_id($media->id, 'song');
                if ($object_id) {
                    $democratic->remove_vote($object_id);
                }

                return $response->withBody(
                    $this->streamFactory->createStream(
                        $this->keyed(['method' => $input['method'], 'result' => true], $input['api_format'])
                    )
                );
            case 'playlist':
                $results = $democratic->get_items();
                Song::build_cache($democratic->object_ids);
                Democratic::build_vote_cache($democratic->vote_ids);

                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->democratic($apiVersion, $results, $user, $input['auth'])
                    )
                );
            case 'play':
                return $response->withBody(
                    $this->streamFactory->createStream(
                        $this->keyed(['url' => $democratic->play_url($user)], $input['api_format'])
                    )
                );
            default:
                Api4::message('error', 'Invalid request', '405', $input['api_format']);

                return $response;
        }
    }

    /**
     * Version 4 prints a keyed array as pretty json or as xml; no data builder covers that shape.
     *
     * @param array<string, mixed> $results
     */
    private function keyed(array $results, string $format): string
    {
        return ($format === 'json')
            ? (string) json_encode($results, JSON_PRETTY_PRINT)
            : Api::keyed_array($results);
    }
}
