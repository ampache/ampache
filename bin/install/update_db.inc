<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

if(php_sapi_name() != 'cli') {
    exit(1);
}

define('NO_SESSION', 1);
define('OUTDATED_DATABASE_OK', 1);
define('CLI', 1);

$path = dirname(__FILE__);
$prefix = realpath($path . '/../../');
require_once $prefix . '/lib/init.php';

$options = getopt('u', array('update'));

$run_update = (isset($options['u']) || isset($options['update']));

if (Update::need_update() && $run_update) {
    Update::run_update();
}

if (Update::need_update()) {
    echo T_('The following updates need to be performed:'), "\n";
}

Update::display_update();

?>
