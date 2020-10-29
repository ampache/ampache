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

declare(strict_types=0);

namespace Lib\ApiMethods;

use Api;
use Catalog;
use Session;
use User;

/**
 * Class CatalogActionMethod
 * @package Lib\ApiMethods
 */
final class CatalogActionMethod
{
    private const ACTION = 'catalog_action';

    /**
     * catalog_action
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * Kick off a catalog update or clean for the selected catalog
     * Added 'verify_catalog', 'gather_art'
     *
     * @param array $input
     * task    = (string) 'add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art'
     * catalog = (integer) $catalog_id)
     * @return boolean
     */
    public static function catalog_action(array $input)
    {
        if (!Api::check_parameter($input, array('catalog', 'task'), self::ACTION)) {
            return false;
        }
        if (!Api::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        $task = (string) $input['task'];
        // confirm the correct data
        if (!in_array($task, array('add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art'))) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Bad Request: %s'), $task), '4710', self::ACTION, 'task', $input['api_format']);

            return false;
        }

        $catalog = Catalog::create_from_id((int) $input['catalog']);
        if ($catalog) {
            define('API', true);
            unset($SSE_OUTPUT);
            switch ($task) {
                case 'clean_catalog':
                    $catalog->clean_catalog_proc();
                    Catalog::clean_empty_albums();
                    break;
                case 'verify_catalog':
                    $catalog->verify_catalog_proc();
                    break;
                case 'gather_art':
                    $catalog->gather_art();
                    break;
                case 'add_to_catalog':
                    $options = array(
                        'gather_art' => false,
                        'parse_playlist' => false
                    );
                    $catalog->add_to_catalog($options);
                    break;
            }
            Api::message('successfully started: ' . $task, $input['api_format']);
            Catalog::count_server();
        } else {
            Api::error(T_('Not Found'), '4704', self::ACTION, 'catalog', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    }
}
