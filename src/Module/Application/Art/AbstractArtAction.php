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

use Ampache\Repository\Model\database_object;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

abstract class AbstractArtAction implements ApplicationActionInterface
{
    protected function getItem(
        GuiGatekeeperInterface $gatekeeper
    ): ?database_object {
        $object_type = filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $object_id   = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            return null;
        }

        $class_name = ObjectTypeToClassNameMapper::map($object_type);
        $item       = new $class_name($object_id);

        // If not a content manager user then kick em out
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) === false && (
                $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER) === false ||
                $item->get_user_owner() != Core::get_global('user')->id
            )
        ) {
            return null;
        }

        $item->format();

        return $item;
    }
}
