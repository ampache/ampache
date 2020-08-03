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
<!-- begin
function PlayerPopUp(URL)
{
<?php
$width = 730;
if (WebPlayer::is_playlist_video($this)) {
    $width = 880;
} ?>
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
