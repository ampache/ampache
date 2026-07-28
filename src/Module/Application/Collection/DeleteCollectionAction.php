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

namespace Ampache\Module\Application\Collection;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode\RFC\RFC7231;

final readonly class DeleteCollectionAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'delete_collection';

    public function __construct(
        private CollectionRepositoryInterface $collectionRepository,
        private ResponseFactoryInterface $responseFactory,
        private ConfigContainerInterface $configContainer,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ResponseInterface
    {
        if (check_http_referer()) {
            $collectionId = $request->getQueryParams()['collection'] ?? null;
            if ($collectionId !== null) {
                // Deleting is the owner's call alone: a collaborator may curate the contents but not discard them.
                $collection = $this->collectionRepository->findById((int) $collectionId);
                if (
                    $collection !== null
                    && $collection->has_access()
                ) {
                    $this->collectionRepository->delete($collection->getId());

                    return $this->responseFactory
                        ->createResponse(RFC7231::FOUND)
                        ->withHeader(
                            'Location',
                            sprintf('%s/browse.php?action=collection', $this->configContainer->getWebPath())
                        );
                }
            }
        }

        throw new AccessDeniedException();
    }
}
