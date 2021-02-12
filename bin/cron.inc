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
 * (at your option); any later version.
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

use Lib\Cron;

define('NO_SESSION', '1');
define('CLI', 1);

$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

ob_end_flush();

if (!AmpConfig::get('cron_cache')) {
    debug_event('cron.inc', 'ENABLE \'Cache computed SQL data (eg. media hits stats) using a cron\'\n * In Admin -> Server Config -> System -> Catalog\n', 5);

    return false;
}
debug_event('cron', 'started cron process', 3);

/**
 * Catalog garbage_collection covers these functions
 *
 * Song::garbage_collection();
 * Album::garbage_collection();
 * Artist::garbage_collection();
 * Video::garbage_collection();
 * Movie::garbage_collection();
 * Art::garbage_collection();
 * Stats::garbage_collection();
 * Rating::garbage_collection();
 * Userflag::garbage_collection();
 * Useractivity::garbage_collection();
 * Playlist::garbage_collection();
 * Tmp_Playlist::garbage_collection(); FIXME Duplicated with Session
 * Shoutbox::garbage_collection();
 * Tag::garbage_collection();
 * Metadata::garbage_collection();
 * MetadataField::garbage_collection();
 */
Catalog::garbage_collection();

/**
 * Session garbage_collection covers these functions.
 *
 * Query::garbage_collection();
 * Stream_Playlist::garbage_collection();
 * Song_Preview::garbage_collection();
 * Tmp_Playlist::garbage_collection(); FIXME Duplicated with Catalog
 */
Session::garbage_collection();

/**
 * Clean up remaining functions.
 */
Share::garbage_collection();
Stream::garbage_collection();
Podcast_Episode::garbage_collection();
Bookmark::garbage_collection();
Recommendation::garbage_collection();

/**
 * Run compute_cache
 */
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/bin/compute_cache.inc';

// mark the date this cron was completed.
Cron::set_cron_date();
debug_event('cron', 'finished cron process', 4);
