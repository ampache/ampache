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

define('NO_SESSION', '1');
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

/* Check Perms */
if (!AmpConfig::get('use_now_playing_embedded') || AmpConfig::get('demo_mode')) {
    UI::access_denied();
    exit;
} ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl(AmpConfig::get('lang')) ? 'rtl' : 'ltr';?>">
<head>
    <!-- Propelled by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
    <title><?php echo AmpConfig::get('site_title') . ' - ' . T_("Now Playing"); ?></title>
<?php
if (AmpConfig::get('now_playing_css_file')) { ?>
    <link rel="stylesheet" href="<?php echo $web_path;
    echo AmpConfig::get('now_playing_css_file'); ?>" type="text/css" media="screen" />
<?php
}
if (AmpConfig::get('now_playing_refresh_limit') > 1) {
    $refresh_limit = AmpConfig::get('now_playing_refresh_limit'); ?>
    <script>
        reload = window.setInterval(function(){ window.location.reload(); }, <?php echo $refresh_limit ?> * 1000);
    </script>
<?php
} ?>
</head>
<body>
<?php

Stream::garbage_collection();
$results = Stream::get_now_playing();

if (Core::get_request('user_id') !== '') {
    if (empty($results)) {
        $last_song = Stats::get_last_play(Core::get_request('user_id'));
        $media     = new Song($last_song['object_id']);
        $media->format();
        $client = new User($last_song['user']);
        $client->format();
        $results[] = array(
            'media' => $media,
            'client' => $client,
            'agent' => $last_song['agent'],
            'expire' => ''
        );
        debug_event('now_playing', 'no result; getting last song played instead: ' . $media->id, 5);
    }
    // If the URL specifies a specific user, filter the results on that user
    $results = array_filter($results, function ($item) {
        return ($item['client']->id === Core::get_request('user_id'));
    });
}

require AmpConfig::get('prefix') . UI::find_template('show_now_playing.inc.php'); ?>
</body>
</html>
