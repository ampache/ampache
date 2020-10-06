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

use AmpConfig;
use Api;

final class CatalogFileMethod
{
    /**
     * catalog_file
     * MINIMUM_API_VERSION=420000
     *
     * Perform actions on local catalog files.
     * Single file versions of catalog add, clean and verify.
     * Make sure you remember to urlencode those file names!
     *
     * @param array $input
     * file    = (string) urlencode(FULL path to local file)
     * task    = (string) 'add', 'clean', 'verify', 'remove'
     * catalog = (integer) $catalog_id)
     * @return boolean
     */
    public static function catalog_file($input)
    {
        $task = (string) $input['task'];
        if (!AmpConfig::get('delete_from_disk') && $task == 'remove') {
            Api::message('error', T_('Access Denied: delete from disk is not enabled.'), '403', $input['api_format']);

            return false;
        }
        if (!Api::check_access('interface', 50, \User::get_from_username(\Session::username($input['auth']))->id, 'catalog_file', $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('catalog', 'file', 'task'), 'catalog_action')) {
            return false;
        }
        $file = (string) html_entity_decode($input['file']);
        // confirm the correct data
        if (!in_array($task, array('add', 'clean', 'verify', 'remove'))) {
            Api::message('error', T_('Incorrect file task') . ' ' . $task, '400', $input['api_format']);

            return false;
        }
        if (!file_exists($file) && $task !== 'clean') {
            Api::message('error', T_('File not found') . ' ' . $file, '404', $input['api_format']);

            return false;
        }
        $catalog_id = (int) $input['catalog'];
        $catalog    = \Catalog::create_from_id($catalog_id);
        if ($catalog->id < 1) {
            Api::message('error', T_('Catalog not found') . ' ' . $catalog_id, '404', $input['api_format']);

            return false;
        }
        switch ($catalog->gather_types) {
            case 'podcast':
                $type  = 'podcast_episode';
                $media = new \Podcast_Episode(\Catalog::get_id_from_file($file, $type));
                break;
            case 'clip':
            case 'tvshow':
            case 'movie':
            case 'personal_video':
                $type  = 'video';
                $media = new \Video(\Catalog::get_id_from_file($file, $type));
                break;
            case 'music':
            default:
                $type  = 'song';
                $media = new \Song(\Catalog::get_id_from_file($file, $type));
                break;
        }

        if ($catalog->catalog_type == 'local') {
            define('API', true);
            unset($SSE_OUTPUT);
            switch ($task) {
                case 'clean':
                    $catalog->clean_file($file, $type);
                    break;
                case 'verify':
                    \Catalog::update_media_from_tags($media, array($type));
                    break;
                case 'add':
                    $catalog->add_file($file);
                    break;
                case 'remove':
                    $media->remove();
                    break;
            }
            Api::message('success', 'successfully started: ' . $task . ' for ' . $file, null, $input['api_format']);
        } else {
            Api::message('error', T_('The requested catalog was not found'), '404', $input['api_format']);
        }
        \Session::extend($input['auth']);

        return true;
    }
}
