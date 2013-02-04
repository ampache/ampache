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

/**
 * Delete Disabled
 * This WILL DELETE MUSIC!!!! it does what it's name would suggest and deletes
 * any disabled songs
 */

/* will not delete anything if debug is true */
$debug = true;

define('NO_SESSION', '1');
$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';

if ($debug) { echo T_('DEBUG ENABLED WILL NOT DELETE FILES!'); echo "\n"; }

/* Get a list of filenames */
$sql = "SELECT `id`, `file` FROM song WHERE enabled='0'";
$db_results = Dba::read($sql);

while ($row = Dba::fetch_assoc($db_results)) {
    if ($debug) {
        printf (T_('Would Delete: %s'), $row['file']);
        echo "\n";
    }
    else {
        printf (T_('Deleting: %s'), $row['file']);
        echo "\n";
        unlink($row['file']);
        $sql = 'DELETE FROM `song` WHERE `id` = ?';
        $del_results = Dba::write($sql, array($row['id']));
    }
}
?>
