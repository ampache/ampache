<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\LegacyLogger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Generates a list of media item urls depending on the playlist type
 */
final class MediaUrlListGenerator implements MediaUrlListGeneratorInterface
{
    private ContainerInterface $dic;

    private ResponseFactoryInterface $responseFactory;

    private LoggerInterface $logger;

    public function __construct(
        ContainerInterface $dic,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger
    ) {
        $this->dic             = $dic;
        $this->responseFactory = $responseFactory;
        $this->logger          = $logger;
    }

    public function generate(
        Stream_Playlist $playlist,
        string $type
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse();

        if ($playlist->urls === []) {
            $this->logger->error(
                sprintf('Error: Empty URL array for %d', $playlist->id),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return $response;
        }

        $this->logger->info(
            sprintf('Generating a {%s} object...', $type),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        /** @var callable[] $map */
        $map = [
            'download' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(DownloadMediaUrlListGeneratorType::class);
            },
            'democratic' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(DemocraticMediaUrlListGeneratorType::class);
            },
            'localplay' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(LocalplayMediaUrlGeneratorType::class);
            },
            'web_player' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(WebPlayerMediaUrlListGeneratorType::class);
            },
            'asx' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(AsxMediaUrlListGeneratorType::class);
            },
            'pls' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(PlsMediaUrlListGeneratorType::class);
            },
            'simple_m3u' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(SimpleM3uMediaUrlListGeneratorType::class);
            },
            'xspf' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(XspfMediaUrlListGeneratorType::class);
            },
            'hls' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(HlsMediaUrlListGeneratorType::class);
            },
            'm3u' => function (): MediaUrlListGeneratorTypeInterface {
                return $this->dic->get(M3uMediaUrlListGeneratorType::class);
            },
        ];

        $handler = $map[$type] ?? $map['m3u'];

        /** @var MediaUrlListGeneratorTypeInterface $generatorType */
        $generatorType = $handler();

        return $generatorType->generate($playlist, $response);
    }
}
