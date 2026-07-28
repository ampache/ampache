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
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AddObjectAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'add_object';

    public function __construct(
        private CollectionRepositoryInterface $collectionRepository,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!check_http_referer()) {
            throw new AccessDeniedException();
        }

        $params = $request->getQueryParams();
        // Named `object_type` rather than `type` so the same call works through the REST rewrite, which spends
        // `type` on the resource name and would otherwise overwrite whichever copy PHP keeps.
        $objectType = (string) ($params['object_type'] ?? '');
        $objectId   = (int) ($params['object_id'] ?? 0);

        $user       = Core::get_global('user');
        $collection = $this->collectionRepository->findById((int) ($params['collection'] ?? 0));
        if (
            $collection === null
            || !$collection->has_collaborate($user instanceof User ? $user : null)
        ) {
            throw new AccessDeniedException();
        }

        // `acceptsType()` is the only authority on what a pinned collection takes, so the interface asks it rather
        // than deciding for itself and drifting from the rule the API enforces.
        if (!$collection->acceptsType($objectType)) {
            $this->ui->showHeader();
            echo T_('This collection does not accept items of that type');
            $this->ui->showFooter();

            return null;
        }

        // Curating an id that is not in its own table would leave the collection holding a dangling member.
        if ($this->collectionRepository->objectExists($objectType, $objectId)) {
            $this->collectionRepository->addItem($collection->getId(), $objectId, $objectType);
        }

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('Added'),
            T_('Object added to collection'),
            $collection->get_link()
        );
        $this->ui->showFooter();

        return null;
    }
}
