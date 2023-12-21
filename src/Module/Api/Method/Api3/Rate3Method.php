<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;

/**
 * Class Rate3Method
 */
final class Rate3Method
{
    public const ACTION = 'rate';

    /**
     * rate
     * This rate a library item
     */
    public static function rate(array $input, User $user): void
    {
        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = (int) $input['id'];
        $rating    = (int) $input['rating'];

        if (!InterfaceImplementationChecker::is_library_item($type) || !$object_id) {
            echo Xml3_Data::error(401, T_('Wrong library item type.'));
        } else {
            $className = ObjectTypeToClassNameMapper::map($type);
            /** @var library_item $item */
            $item = new $className($object_id);
            if ($item->getId() === 0) {
                echo Xml3_Data::error(404, T_('Library item not found.'));
            } else {
                $rate = new Rating($object_id, $type);
                $rate->set_rating($rating, $user->id);
                echo Xml3_Data::single_string('success');
            }
        }
    }
}
