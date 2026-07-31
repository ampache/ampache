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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns the art image for an object.
 *
 * Version 5 reports the object id as `id` and serves a smaller set of object types, so it keeps
 * a method of its own.
 */
final class GetArt5Method implements MethodInterface
{
    public const string ACTION = 'get_art';

    /** @var string[] */
    private const array TYPES = [
        'song',
        'album',
        'artist',
        'playlist',
        'search',
        'podcast',
    ];

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * get_art
     * MINIMUM_API_VERSION=400001
     *
     * Get an art image.
     *
     * id = (string) $object_id
     * type = (string) 'song', 'artist', 'album', 'playlist', 'search', 'podcast')
     * fallback = (integer) 0,1, if true return default art ('blankalbum.png') //optional
     * size = (string) 'original' or size in '200x200' format //optional
     *
     * @param array{
     *     id: string,
     *     type: string,
     *     fallback?: int,
     *     size?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        foreach (['id', 'type'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $object_id = (int) $input['id'];
        $size      = (string) ($input['size'] ?? 'original');
        $fallback  = (array_key_exists('fallback', $input) && (int) $input['fallback'] == 1);

        // the type is matched case insensitively, so the art is resolved from the normalized name
        $requested_type = (string) $input['type'];
        $type           = strtolower($requested_type);

        // confirm the correct data
        if (!in_array($type, self::TYPES)) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        sprintf('Bad Request: %s', $requested_type),
                        self::ACTION,
                        'type'
                    )
                )
            );
        }

        $art = $this->resolveArt($type, $object_id, $user);

        Session::extend($input['auth'], AccessTypeEnum::API->value);

        $image = $art->getImage($size, $fallback);
        if ($image === null) {
            // art not found
            return $response->withStatus(404);
        }

        $response->getBody()->write($image['data']);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Content-Type', $image['mime'])
            ->withHeader('Content-Length', (string) strlen($image['data']));
    }

    /**
     * Songs, searches and playlists fall back to the art of the album they belong to
     */
    private function resolveArt(string $type, int $object_id, User $user): Art
    {
        $art = $this->modelFactory->createArt($object_id, $type);
        if ($type == 'song') {
            if (!Art::has_db($object_id, $type)) {
                // in most cases the song doesn't have a picture, but the album where it belongs to has
                // if this is the case, we take the album art
                $song = $this->modelFactory->createSong($object_id);
                $art  = $this->modelFactory->createArt($song->album, 'album');
            }
        } elseif ($type == 'search') {
            $smartlist = $this->modelFactory->createSmartlist($object_id, $user);
            $listitems = $smartlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $art       = $this->modelFactory->createArt($item['object_id'], $item['object_type']->value);
            if (!Art::has_db($item['object_id'], 'song')) {
                $song = $this->modelFactory->createSong($item['object_id']);
                $art  = $this->modelFactory->createArt($song->album, 'album');
            }
        } elseif ($type == 'playlist' && !Art::has_db($object_id, $type)) {
            $playlist  = $this->modelFactory->createPlaylist($object_id);
            $listitems = $playlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $song      = $this->modelFactory->createSong($item['object_id']);
            $art       = $this->modelFactory->createArt($song->album, 'album');
        }

        return $art;
    }
}
