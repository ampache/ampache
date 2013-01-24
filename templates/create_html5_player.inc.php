<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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

?>
<html>
<head>
<title><?php echo Config::get('site_title'); ?></title>
<script language="javascript" type="text/javascript">
<!-- begin
function PlayerPopUp(URL) {
	window.open(URL, 'HTML5_player', 'width=700,height=210,scrollbars=0,toolbar=0,location=0,directories=0,status=0,resizable=0');
	window.location = '<?php echo return_referer() ?>';
	return false;
}
// end -->
</script>
</head>
<body onLoad="javascript:PlayerPopUp('<?php echo Config::get('web_path')?>/html5_player.php<?php echo '?playlist_id=' . $this->id ?>')">
</body>
</html>
