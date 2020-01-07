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

define('NO_SESSION','1');

$unmigratable = array('auth_methods'=>'mysql',
    'tag_order'=>'id3v2,id3v1,vorbiscomment,quicktime,ape,asf',
    'album_art_order'=>'db,id3,folder,lastfm,amazon',
    'amazon_base_urls'=>'http://webservices.amazon.com');

$translate = array('local_host'=>'database_hostname',
    'local_db'=>'database_name',
    'local_username'=>'database_username',
    'local_pass'=>'database_password',
    'local_length'=>'session_length',
    'stream_cmd_flac'=>'transcode_cmd_flac',
    'stream_cmd_mp3'=>'transcode_cmd_mp3',
    'stream_cmd_m4a'=>'transcode_cmd_m4a',
    'stream_cmd_ogg'=>'transcode_cmd_ogg',
    'stream_cmd_mpc'=>'transcode_cmd_mpc',
    'sess_name'=>'session_name',
    'sess_cookielife'=>'session_cookielife',
    'sess_cookiesecure'=>'session_cookiesecure');

$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
$old_config = file_get_contents($prefix . '/config/ampache.cfg.php');

$data = explode("\n",$old_config);

echo "Parsing old config file...";
echo "\n";

foreach ($data as $line) {

    // Replace a # with ;
    if ($line['0'] == '#') {
        $line = substr_replace($line,";",0,1);
    }

    foreach ($unmigratable as $option=>$default) {
        if (strstr($line,$option) && !$migrated[$option]) {
            $line = $option . " = \"$default\"";
            $migrated[$option] = true;
        }
        elseif (strstr($line,$option)) {
            $line = ';' . $line;
        }
    }

    foreach ($translate as $old=>$new) {
        if (strstr($line,$old)) {
            $line = str_replace($old,$new,$line);
        }
    }

    $new_config .= $line . "\n";

} // end foreach lines

echo "Parse complete, writing";
echo "\n";

$handle = fopen($prefix . '/config/ampache.cfg.php','w');

$worked = fwrite($handle,$new_config);

if ($worked) {
    echo "Write success, config migrated";
    echo "\n";
}
else {
    echo "Access denied, config migration failed";
    echo "\n";
}
