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

namespace Ampache\Module\Api\RefreshReordered;

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Re-renders one collection's item list after it has been dragged into a new order
 *
 * Only the members are redrawn, so the page keeps its scroll position; see `RefreshPlaylistMediasAction`.
 */
final readonly class RefreshCollectionItemsAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'refresh_collection_items';

    public function __construct(
        private RequestParserInterface $requestParser,
        private BrowseFactoryInterface $browseFactory,
        private CollectionRepositoryInterface $collectionRepository,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $collection = $this->collectionRepository->findById(
            (int) $this->requestParser->getFromRequest('id')
        );
        if ($collection === null) {
            return null;
        }

        $object_ids = $collection->get_items();

        $browse = $this->browseFactory->create();
        $browse->set_type('collection_items');
        $browse->add_supplemental_object('collection', $collection);
        $browse->set_static_content(true);
        $browse->show_objects($object_ids, true);
        $browse->store();

        return null;
    }
}
