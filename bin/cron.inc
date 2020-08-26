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

use Ampache\Module\Util\Cron;

define('NO_SESSION', '1');
define('CLI', 1);

require_once __DIR__ . '/../lib/init.php';

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
require_once __DIR__ . '/compute_cache.inc';

/**
 * ping your channels to keep them alive
 */
$channels = Channel::get_channel_list();
foreach ($channels as $channel_id) {
    $channel = new Channel($channel_id);
    if ($channel->id) {
        if ($channel->check_channel()) {
            debug_event('cron', 'Checking channel: ' . $channel->id, 4);
            $url = $channel->get_stream_proxy_url();
            $ch  = curl_init($url);
            if ($ch) {
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_exec($ch);
                curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            }
        }
    }
}

// mark the date this cron was completed.
Cron::set_cron_date();
debug_event('cron', 'finished cron process', 4);
