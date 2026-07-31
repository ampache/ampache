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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the art image for an object
 *
 * The two live api versions only differ in how they name the object id: version 6 reports it as
 * `id` and version 8 as `filter`, each accepting the other as an alias. The version classes supply
 * that pair of names; everything else is shared.
 */
abstract class AbstractGetArtMethod implements MethodInterface
{
    public const string ACTION = 'get_art';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'id';

    // the name the version reports the object id under; overridden per version
    protected const string FILTER_KEY = 'filter';

    /** @var string[] */
    private const array TYPES = [
        'song',
        'artist',
        'album',
        'label',
        'live_stream',
        'playlist',
        'podcast',
        'search',
        'smartlist',
        'album_disk',
        'user',
        'video',
    ];

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Get an art image.
     *
     * id       = (string) $object_id
     * type     = (string) 'song', 'artist', 'album', 'playlist', 'search', 'podcast', 'user', 'video'
     * fallback = (integer) 0,1, if true return default art ('blankalbum.png') //optional
     * size     = (string) width x height ('640x480', 'original') //optional
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type?: string,
     *     fallback?: int,
     *     size?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException
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

        $type = (string) $input['type'];

        $this->checkTypeIsEnabled($type);

        $objectId = (int) $filter;
        $size     = (string) ($input['size'] ?? 'original');
        $fallback = (array_key_exists('fallback', $input) && (int) $input['fallback'] === 1);

        // confirm the correct data (album_disk is api version 8 only)
        if (
            !in_array(strtolower($type), self::TYPES)
            || ($apiVersion < 8 && strtolower($type) === 'album_disk')
        ) {
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

        $art = $this->resolveArt($type, $objectId, $user);

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
     * Some object types are only served when their feature is switched on
     *
     * @throws AccessDeniedException
     */
    private function checkTypeIsEnabled(string $type): void
    {
        $key = match ($type) {
            'video' => ConfigurationKeyEnum::ALLOW_VIDEO,
            'label' => ConfigurationKeyEnum::LABEL,
            'podcast' => ConfigurationKeyEnum::PODCAST,
            default => null,
        };

        if ($key !== null && !$this->configContainer->get($key)) {
            throw new AccessDeniedException(
                sprintf('Enable: %s', $type)
            );
        }
    }

    /**
     * Songs, searches and playlists fall back to the art of the album they belong to
     */
    private function resolveArt(string $type, int $objectId, User $user): Art
    {
        if ($type === 'song') {
            if (!Art::has_db($objectId, $type)) {
                // in most cases the song doesn't have a picture, but the album it belongs to does
                $song = $this->modelFactory->createSong($objectId);

                return new Art($song->album, 'album');
            }

            return new Art($objectId, $type);
        }

        if ($type === 'search' || $type === 'smartlist') {
            $objectId  = (int) str_replace('smart_', '', (string) $objectId);
            $smartlist = $this->modelFactory->createSmartlist($objectId, $user);
            $listitems = $smartlist->get_items();
            $item      = $listitems[array_rand($listitems)];

            if (!Art::has_db($objectId, 'song')) {
                $song = $this->modelFactory->createSong($item['object_id']);

                return new Art($song->album, 'album');
            }

            return new Art($item['object_id'], $item['object_type']->value);
        }

        if ($type === 'album_disk' && !Art::has_db($objectId, $type)) {
            // a disk usually inherits the album artwork rather than carrying its own
            $albumDisk = $this->modelFactory->createAlbumDisk($objectId);

            return new Art($albumDisk->album_id, 'album');
        }

        if ($type === 'playlist' && !Art::has_db($objectId, $type)) {
            $playlist  = $this->modelFactory->createPlaylist($objectId);
            $listitems = $playlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $song      = $this->modelFactory->createSong($item['object_id']);

            return new Art($song->album, 'album');
        }

        return new Art($objectId, $type);
    }
}
