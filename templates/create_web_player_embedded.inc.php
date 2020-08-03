<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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
 ?>
<html>
<head>
<title><?php echo scrub_out(AmpConfig::get('site_title')); ?></title>
<script>
function PlayerFrame()
{
    var appendmedia = false;
    var playnext = false;
    var $webplayer = $("#webplayer");
    if ($webplayer.is(':visible')) {
<?php
if ($_REQUEST['append']) { ?>
        appendmedia = true;
<?php
} else {
     if ($_REQUEST['playnext']) { ?>
        playnext = true;
<?php
        }
 } ?>
    }

<?php if (AmpConfig::get('webplayer_confirmclose')) { ?>
    document.onbeforeunload = null;
<?php
} ?>
    if (appendmedia) {
        <?php echo WebPlayer::add_media_js($this); ?>
    } else if (playnext) {
        <?php echo WebPlayer::play_next_js($this); ?>
    } else {
        $webplayer.show();
        $("#webplayer-minimize").show();
        $.get('<?php echo AmpConfig::get('web_path'); ?>/web_player_embedded.php?playlist_id=<?php echo $this->id; ?>', function (data) {
            var $response = $(data);
            $webplayer.empty().append($response);
        }, 'html');
    }
    return false;
}

PlayerFrame();
</script>
</head>
<body>
</body>
</html>
