<?php

/*
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

declare(strict_types=0);

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class CatalogDeleteMethod
 * @package Lib\ApiMethods
 */
final class CatalogDeleteMethod
{
    public const ACTION = 'catalog_delete';

    /**
     * catalog_delete
     * MINIMUM_API_VERSION=6.0.0
     *
     * Delete an existing catalog. (if it exists)
     *
     * filter = (string) catalog_id to delete
     */
    public static function catalog_delete(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        if (!Api::check_access('interface', 75, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $catalog_id = (int)$input['filter'];
        $catalog    = Catalog::create_from_id($catalog_id);
        if (!$catalog) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $catalog_id), '4704', self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        if (!Catalog::delete($catalog_id)) {
            Api::error(T_('Bad Request'), '4710', self::ACTION, 'system', $input['api_format']);

            return false;
        }

        Api::message('Deleted Catalog: ' . $catalog_id, $input['api_format']);

        return true;
    } // catalog_delete
}
