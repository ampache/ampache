<?php

declare(strict_types=0);

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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class SmartlistDeleteMethod
 * @package Lib\ApiMethods
 */
final class SmartlistDeleteMethod
{
    public const ACTION = 'smartlist_delete';

    /**
     * smartlist_delete
     * MINIMUM_API_VERSION=380001
     *
     * This deletes a smartlist
     *
     * filter = (string) UID of smartlist
     *
     * @param array{
     *     filter: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function smartlist_delete(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        ob_end_clean();
        $smartlist = new Search((int) str_replace('smart_', '', $input['filter']), 'song', $user);
        if (!$smartlist->has_access($user)) {
            Api::error('Require: 100', ErrorCodeEnum::FAILED_ACCESS_CHECK, self::ACTION, 'account', $input['api_format']);
        } else {
            $smartlist->delete();
            Api::message('smartlist deleted', $input['api_format']);
            Catalog::count_table('smartlist');
        }

        return true;
    }
}
