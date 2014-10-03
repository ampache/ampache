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

define('NO_SESSION', '1');
require_once 'lib/init.php';
// Avoid form login if still connected
if (AmpConfig::get('use_auth') && !isset($_GET['force_display'])) {
    $auth = false;
    if (Session::exists('interface', $_COOKIE[AmpConfig::get('session_name')])) {
        $auth = true;
    } else {
        if (Session::auth_remember()) {
            $auth = true;
        }
    }
    if ($auth) {
        header("Location: " . AmpConfig::get('web_path'));
        exit;
    }
}
require_once 'lib/login.php';

require AmpConfig::get('prefix') . '/templates/show_login_form.inc.php';
