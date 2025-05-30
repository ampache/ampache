<?php

declare(strict_types=0);

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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Api4;

/**
 * Class Flag4Method
 */
final class Flag4Method
{
    public const ACTION = 'flag';

    /**
     * flag
     * MINIMUM_API_VERSION=400001
     *
     * This flags a library item as a favorite
     * Setting flag to true (1) will set the flag
     * Setting flag to false (0) will remove the flag
     *
     * type = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video' $type
     * id   = (integer) $object_id
     * flag = (integer) 0,1 $flag
     *
     * @param array{
     *     id: string,
     *     type: string,
     *     flag: int,
     *     date?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function flag(array $input, User $user): bool
    {
        if (!AmpConfig::get('ratings')) {
            Api4::message('error', T_('Access Denied: Rating features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, ['type', 'id', 'flag'], self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = $input['id'];
        $flag      = (bool)$input['flag'];
        $user_id   = null;
        if ($user->id > 0) {
            $user_id = $user->id;
        }
        // confirm the correct data
        if (!in_array(strtolower($type), ['song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video'])) {
            Api4::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }

        if (!InterfaceImplementationChecker::is_library_item($type) || !$object_id) {
            Api4::message('error', T_('Wrong library item type'), '401', $input['api_format']);
        } else {
            $className = ObjectTypeToClassNameMapper::map($type);
            /** @var library_item $item */
            $item = new $className((int)$object_id);
            if ($item->getId() === 0) {
                Api4::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            $userflag = new Userflag((int)$object_id, $type);
            if ($userflag->set_flag($flag, $user_id)) {
                $message = ($flag) ? 'flag ADDED to ' : 'flag REMOVED from ';
                Api4::message('success', $message . $object_id, null, $input['api_format']);

                return true;
            }
            Api4::message('error', 'flag failed ' . $object_id, '400', $input['api_format']);
        }

        return true;
    }
}
