<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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

?>
<html>
<head>
<title><?php echo AmpConfig::get('site_title'); ?></title>
<script language="javascript" type="text/javascript">
function PlayerFrame()
{
    var appendmedia = false;
    var $webplayer = $("#webplayer");
    if ($webplayer.is(':visible')) {
<?php
if ($_REQUEST['append']) {
?>
        appendmedia = true;
<?php
}
?>
    }

<?php if (AmpConfig::get('webplayer_confirmclose')) { ?>
    document.onbeforeunload = null;
<?php } ?>

    if (appendmedia) {
        <?php echo WebPlayer::add_media_js($this); ?>
    } else {
        $webplayer.show();
        $.get('<?php echo AmpConfig::get('web_path'); ?>/web_player_embedded.php?playlist_id=<?php echo $this->id; ?>', function (data) {
            var $response = $(data);
            $webplayer.empty().append($response);
        },'html');
    }
    return false;
}

PlayerFrame();
</script>
</head>
<body>
</body>
</html>
