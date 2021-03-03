<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/**
 * Delete Disabled
 * This WILL DELETE MUSIC!!!! it does what it's name would suggest and deletes
 * any disabled songs
 */
$options = getopt("x");

// will not delete anything if debug is true
// Require "-x" to disable debug mode
$debug = !array_key_exists('x', $options);

define('NO_SESSION', '1');
$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if ($debug) {
    echo "\n" . T_('Running in Debug Mode. Use -x to execute') . "\n\n";
} else {
    echo "\n***" . T_("WARNING") . "*** " . T_("Running in Write Mode. Make sure you've tested first!") . "\n\n";
}

/* Get a list of filenames */
$sql        = "SELECT `id`, `file` FROM `song` WHERE `enabled` = 0";
$db_results = Dba::read($sql);

while ($row = Dba::fetch_assoc($db_results)) {
    if ($debug) {
        /* HINT: filename (File path) */
        printf(T_('Would delete: %s'), $row['file']);
        echo "\n";
    } else {
        /* HINT: filename (File path) */
        printf(T_('Deleting: %s'), $row['file']);
        echo "\n";
        unlink($row['file']);
        $sql         = 'DELETE FROM `song` WHERE `id` = ?';
        $del_results = Dba::write($sql, array($row['id']));
    }
}
