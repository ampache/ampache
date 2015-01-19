<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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
<!-- begin
function PlayerPopUp(URL)
{
<?php
$width = 730;
if (WebPlayer::is_playlist_video($this)) {
    $width = 880;
}
?>
    window.open(URL, 'Web_player', 'width=<?php echo $width; ?>,height=285,scrollbars=0,toolbar=0,location=0,directories=0,status=0,resizable=0');
    window.location = '<?php echo return_referer() ?>';
    return false;
}
// end -->
</script>
</head>
<body onLoad="javascript:PlayerPopUp('<?php echo AmpConfig::get('web_path')?>/web_player.php<?php echo '?playlist_id=' . $this->id ?>')">
</body>
</html>
