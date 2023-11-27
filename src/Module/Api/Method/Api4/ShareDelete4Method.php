<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;

/**
 * Class ShareDelete4Method
 */
final class ShareDelete4Method
{
    public const ACTION = 'share_delete';

    /**
     * share_delete
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing share.
     *
     * filter = (string) UID of share to delete
     */
    public static function share_delete(array $input, User $user): bool
    {
        if (!AmpConfig::get('share')) {
            Api4::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $object_id = $input['filter'];
        if (in_array($object_id, Share::get_share_list($user))) {
            if (Share::delete_share($object_id, $user)) {
                Api4::message('success', 'share ' . $object_id . ' deleted', null, $input['api_format']);
            } else {
                Api4::message('error', 'share ' . $object_id . ' was not deleted', '401', $input['api_format']);
            }
        } else {
            Api4::message('error', 'share ' . $object_id . ' was not found', '404', $input['api_format']);
        }

        return true;
    }
}
