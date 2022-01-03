<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Session;

/**
 * Class Rate3Method
 */
final class Rate3Method
{
    public const ACTION = 'rate';

    /**
     * rate
     * This rate a library item
     * @param array $input
     */
    public static function rate(array $input)
    {
        ob_end_clean();
        $type      = ObjectTypeToClassNameMapper::map((string)$input['type']);
        $object_id = (int) $input['id'];
        $rating    = (string) $input['rating'];
        $user      = User::get_from_username(Session::username($input['auth']));

        if (!InterfaceImplementationChecker::is_library_item($type) || !$object_id) {
            echo Xml3_Data::error('401', T_('Wrong library item type.'));
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                echo Xml3_Data::error('404', T_('Library item not found.'));
            } else {
                $rate = new Rating($object_id, $type);
                $rate->set_rating($rating, $user->id);
                echo Xml3_Data::single_string('success');
            }
        }
    } // rate
}
