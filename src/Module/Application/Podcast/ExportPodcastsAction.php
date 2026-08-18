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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Podcast\Exchange\PodcastExporterInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Exports the podcast subscriptions the caller can see, bounded by their catalog access
 */
final readonly class ExportPodcastsAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'export_podcasts';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PodcastExporterInterface $podcastExporter,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // A no-auth/demo guest still resolves to a real User instance, so require real account access here.
        if ($gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false) {
            throw new AccessDeniedException();
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }

        // the boundary knows who is asking, so the catalog filter is resolved here and the exporter stays a
        // plain read of whatever it is handed
        $user       = $gatekeeper->getUser();
        $catalogIds = ($user instanceof User && $user->getId() > 0)
            ? Catalog::get_catalogs('podcast', $user->getId(), true)
            : null;

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
            $this->podcastExporter->export($catalogIds)
        );

        return $response;
    }
}
