<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Lib\ArtItemRetrieverInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\StatusCode;

final class GetArtMethod implements MethodInterface
{
    public const ACTION = 'get_art';

    private ArtItemRetrieverInterface $artItemRetriever;

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ArtItemRetrieverInterface $artItemRetriever,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->artItemRetriever = $artItemRetriever;
        $this->streamFactory    = $streamFactory;
        $this->configContainer  = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Get an art image.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * id   = (string) $object_id
     * type = (string) 'song', 'artist', 'album', 'playlist', 'search', 'podcast')
     *
     * @return ResponseInterface
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        foreach (['id', 'type'] as $key) {
            if (!array_key_exists($key, $input)) {
                return $response->withStatus(StatusCode::BAD_REQUEST);
            }
        }

        $object_id = (int) $input['id'];
        $type      = (string) $input['type'];
        $size      = (int) ($input['size'] ?? 0);

        // confirm the correct data
        if (!in_array($type, ['song', 'album', 'artist', 'playlist', 'search', 'podcast'])) {
            return $response->withStatus(StatusCode::BAD_REQUEST);
        }

        $art = $this->artItemRetriever->retrieve(
            $gatekeeper->getUser(),
            $type,
            $object_id
        );

        if ($art != null) {
            if (
                $art->has_db_info() &&
                $size &&
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RESIZE_IMAGES) === true
            ) {
                $thumb = $art->get_thumb([
                    'width' => $size,
                    'height' => $size
                ]);
                if ($thumb !== []) {
                    return $this->sendResponse(
                        $response,
                        $thumb['thumb_mime'],
                        strlen((string) $thumb['thumb']),
                        $thumb['thumb']
                    );
                }
            }

            if ($art->raw_mime !== null) {
                return $this->sendResponse(
                    $response,
                    $art->raw_mime,
                    strlen((string) $art->raw),
                    $art->raw
                );
            }
        }
        // art not found
        return $response->withStatus(StatusCode::NOT_FOUND);
    }

    private function sendResponse(
        ResponseInterface $response,
        string $mimeType,
        int $contentLength,
        string $image
    ): ResponseInterface {
        return $response
            ->withHeader(
                'Content-Type',
                $mimeType
            )
            ->withHeader(
                'Content-Length',
                $contentLength
            )
            ->withHeader(
                'Access-Control-Allow-Origin',
                '*'
            )
            ->withBody(
                $this->streamFactory->createStream($image)
            );
    }
}
