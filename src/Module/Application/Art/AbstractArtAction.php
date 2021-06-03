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

namespace Ampache\Module\Application\Art;

use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;

abstract class AbstractArtAction implements ApplicationActionInterface
{
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * @throws AccessDeniedException
     */
    protected function getItem(
        GuiGatekeeperInterface $gatekeeper,
        string $objectType,
        int $objectId
    ): library_item {
        if (!InterfaceImplementationChecker::is_library_item($objectType)) {
            throw new AccessDeniedException();
        }

        /** @var library_item $item */
        $item = $this->modelFactory->mapObjectType($objectType, $objectId);

        // If not a content manager user then kick em out
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) === false && (
                $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === false ||
                $item->get_user_owner() != $gatekeeper->getUserId()
            )
        ) {
            throw new AccessDeniedException();
        }

        $item->format();

        return $item;
    }
}
