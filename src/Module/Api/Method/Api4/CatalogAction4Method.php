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

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;

/**
 * Class CatalogAction4Method
 */
final class CatalogAction4Method
{
    public const ACTION = 'catalog_action';

    /**
     * catalog_action
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * Kick off a catalog update or clean for the selected catalog
     * Added 'verify_catalog', 'gather_art'
     *
     * @param array $input
     * @param User $user
     * task    = (string) 'add_to_catalog'|'clean_catalog'
     * catalog = (integer) $catalog_id)
     * @return boolean
     */
    public static function catalog_action(array $input, User $user): bool
    {
        if (!Api4::check_parameter($input, array('catalog', 'task'), self::ACTION)) {
            return false;
        }
        if (!Api4::check_access('interface', 75, $user->id, 'catalog_action', $input['api_format'])) {
            return false;
        }
        $task = (string) $input['task'];
        // confirm the correct data
        if (!in_array($task, array('add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art'))) {
            Api4::message('error', T_('Incorrect catalog task') . ' ' . $task, '401', $input['api_format']);

            return false;
        }
        $catalog = Catalog::create_from_id((int) $input['catalog']);

        if ($catalog) {
            if (defined('SSE_OUTPUT')) {
                unset($SSE_OUTPUT);
            }
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
            // clean up after the action
            $catalog_media_type = $catalog->gather_types;
            if ($catalog_media_type == 'music') {
                Catalog::clean_empty_albums();
                Album::update_album_artist();
            }
            $catalog->update_catalog_map();
            Catalog::update_counts();

            Api4::message('success', 'successfully started: ' . $task, null, $input['api_format']);
        } else {
            Api4::message('error', T_('The requested item was not found'), '404', $input['api_format']);
        }

        return true;
    } // catalog_action
}
