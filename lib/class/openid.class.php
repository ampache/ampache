<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$openid_path = AmpConfig::get('prefix') . "/modules";
$path        = ini_get('include_path');
$path        = $openid_path . PATH_SEPARATOR . $path;
ini_set('include_path', $path);

require_once "Auth/OpenID/Consumer.php";
require_once "Auth/OpenID/FileStore.php";
require_once "Auth/OpenID/SReg.php";
require_once "Auth/OpenID/PAPE.php";

class Openid
{
    public static function get_store()
    {
        $store      = null;
        $store_path = Core::get_tmp_dir() . DIRECTORY_SEPARATOR . '_openid';

        if (!file_exists($store_path) && !mkdir($store_path)) {
            debug_event('openid.class', 'Could not access/create the FileStore directory ' . $store_path . '. Please check the effective permissions.', 3);
        } else {
            $store = new Auth_OpenID_FileStore($store_path);

            return $store;
        }

        return $store;
    }

    public static function get_consumer()
    {
        $consumer = null;
        $store    = self::get_store();
        if ($store) {
            $consumer = new Auth_OpenID_Consumer($store);
        }

        return $consumer;
    }

    public static function get_return_url()
    {
        return AmpConfig::get('web_path') . '/login.php?auth_mod=openid&step=2';
    }

    public static function get_policies()
    {
        $openid_required_pape = (string) AmpConfig::get('openid_required_pape');
        $policies             = array();
        if (!empty($openid_required_pape)) {
            $papes = explode(',', $openid_required_pape);
            foreach ($papes as $pape) {
                $policies[] = constant($pape);
            }
        }

        return $policies;
    }
} // end of Openid class
