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

use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Writes a whole new member order, as dragged in the interface
 *
 * The counterpart of `Playlist\SetTrackNumbersAction`. `order` is the `collection_map` row ids in their new
 * order, which is what identifies a member when a collection holds the same object twice.
 */
final readonly class SetTrackNumbersAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'set_track_numbers';

    public function __construct(
        private RequestParserInterface $requestParser,
        private CollectionRepositoryInterface $collectionRepository,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user       = Core::get_global('user');
        $collection = $this->collectionRepository->findById(
            (int) $this->requestParser->getFromRequest('collection')
        );
        // A collaborator curates the contents, matching who may drag a playlist about
        if (
            $collection === null
            || !$collection->has_collaborate($user instanceof User ? $user : null)
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $order = $this->requestParser->getFromRequest('order');
        if ($order !== '') {
            // The list only covers the page that was dragged, so it starts numbering where that page starts
            $track = (int) $this->requestParser->getFromRequest('offset') + 1;
            if ($track < 1) {
                $track = 1;
            }

            foreach (explode(';', $order) as $mapId) {
                if ($mapId !== '') {
                    $collection->update_track_number((int) $mapId, $track);
                    ++$track;
                }
            }
        }

        // Renumber the whole collection: dragging one page can leave it holding positions from another
        $collection->regenerate_track_numbers();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
