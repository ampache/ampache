<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\Exchange\PodcastExporterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Exports all podcast subscriptions
 */
final class ExportPodcastsAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'export_podcasts';

    private ConfigContainerInterface $configContainer;

    private PodcastExporterInterface $podcastExporter;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PodcastExporterInterface $podcastExporter,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->configContainer = $configContainer;
        $this->podcastExporter = $podcastExporter;
        $this->responseFactory = $responseFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        $fileName = sprintf(
            'ampache_podcast_subscriptions_%s.opml',
            date('Y-m-d_H-i-s')
        );

        $response = $this->responseFactory->createResponse()
            ->withHeader(
                'Content-Disposition',
                'attachment; filename="' . $fileName . '"'
            )
            ->withHeader(
                'Content-Type',
                $this->podcastExporter->getContentType()
            );

        // write the actual export to the body
        $response->getBody()->write(
            $this->podcastExporter->export()
        );

        return $response;
    }
}
