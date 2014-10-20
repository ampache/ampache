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

require_once '../lib/init.php';

if (!Access::check('interface',100) OR AmpConfig::get('demo_mode')) {
    UI::access_denied();
    exit();
}

UI::show_header();

/* Switch on action boys */
switch ($_REQUEST['action']) {
    /* This re-generates the config file comparing
     * /config/ampache.cfg to .cfg.dist
     */
    case 'generate_config':
        ob_end_clean();
        $current = parse_ini_file(AmpConfig::get('prefix') . '/config/ampache.cfg.php');
        $final = generate_config($current);
        $browser = new Horde_Browser();
        $browser->downloadHeaders('ampache.cfg.php','text/plain',false,filesize(AmpConfig::get('prefix') . '/config/ampache.cfg.php.dist'));
        echo $final;
        exit;
    case 'write_config':
        $final = write_config(AmpConfig::get('prefix') . '/config/ampache.cfg.php');
        header('Location: '. AmpConfig::get('web_path') . '/index.php');
        exit;
    case 'reset_db_charset':
        Dba::reset_db_charset();
        show_confirmation(T_('Database Charset Updated'), T_('Your Database and associated tables have been updated to match your currently configured charset'), AmpConfig::get('web_path').'/admin/system.php?action=show_debug');
    break;
    case 'show_debug':
        $configuration = AmpConfig::get_all();
        if ($_REQUEST['autoupdate'] == 'force') {
            $version = AutoUpdate::get_latest_version(true);
        }
        require_once AmpConfig::get('prefix') . '/templates/show_debug.inc.php';
    break;
    default:
        // Rien a faire
    break;
} // end switch

UI::show_footer();
