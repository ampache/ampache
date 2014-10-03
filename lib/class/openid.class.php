<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$openid_path = AmpConfig::get('prefix') . "/modules";
$path = ini_get('include_path');
$path = $openid_path . PATH_SEPARATOR . $path;
ini_set('include_path', $path);

require_once "Auth/OpenID/Consumer.php";
require_once "Auth/OpenID/FileStore.php";
require_once "Auth/OpenID/SReg.php";
require_once "Auth/OpenID/PAPE.php";

class Openid
{
    public static function get_store()
    {
        $store = null;
        $store_path = AmpConfig::get('tmp_dir_path');
        if (empty($store_path)) {
            if (function_exists('sys_get_temp_dir')) {
                $store_path = sys_get_temp_dir();
            } else {
                if (strpos(PHP_OS, 'WIN') === 0) {
                    $store_path = $_ENV['TMP'];
                    if (!isset($store_path)) {
                        $store_path = 'C:\Windows\Temp';
                    }
                } else {
                    $store_path = @$_ENV['TMPDIR'];
                    if (!isset($store_path)) {
                        $store_path = '/tmp';
                    }
                }
            }
            $store_path .= DIRECTORY_SEPARATOR . '_openid';
        }

        if (empty($store_path) || (!file_exists($store_path) && !mkdir($store_path))) {
            debug_event('openid', 'Could not access/create the FileStore directory ' . $store_path . '. Please check the effective permissions.', '5');
        } else {
            $store = new Auth_OpenID_FileStore($store_path);
            return $store;
        }

        return $store;
    }

    public static function get_consumer()
    {
        $consumer = null;
        $store = self::get_store();
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
        $openid_required_pape = AmpConfig::get('openid_required_pape');
        $policies = array();
        if (!empty($openid_required_pape)) {
            $papes = explode(',', $openid_required_pape);
            foreach ($papes as $pape) {
                $policies[] = constant($pape);
            }
        }

        return $policies;
    }

} // end of Openid class
