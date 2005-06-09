<?php
/*

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*

 This will allow users to flag songs for having broken tags or bad rips.

*/

require_once("modules/init.php");

$action = scrub_in($_REQUEST['action']);
$song = scrub_in($_REQUEST['song']);

if ( $action == 'flag_song') {
    $flagged_type = scrub_in($_REQUEST['flagged_type']);
    	$comment = scrub_in($_REQUEST['comment']);
	insert_flagged_song($song, $flagged_type, $comment);
	$flag_text = _("Flagging song completed.");
	$action = 'flag';
}

?>
<?php  show_template('header'); ?>
<?php 
	$highlight = "Home";
	show_menu_items($highlight);

	if ( $action == 'flag' ) {
		$type = 'show_flagged_form';
		$song_id = $song;

		include(conf('prefix') . "/templates/flag.inc");
	}

show_footer();
?>
</body>
</html>
