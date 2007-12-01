<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All Rights Reserved

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
$web_path = Config::get('web_path');
$title = _('Albums by') . " " . $artist->full_name; 
?>
<?php
show_box_top(_('Albums by') . ' ' . $artist->f_name,'info-box');  
if (Config::get('ratings')) { 
	echo "<div id=\"rating_" . $artist->id . "_artist\" style=\"display:inline;\">";
	show_rating($artist->id, 'artist'); 
	echo "</div>";
} // end if ratings ?>
<strong><?php echo _('Actions'); ?>:</strong>
<div id="information_actions">
<a href="<?php echo $web_path; ?>/artists.php?action=show_all_songs&amp;artist=<?php echo $artist->id; ?>"><?php echo _("Show All Songs By") . " " . $artist->f_name; ?></a><br />
<?php echo Ajax::text('?action=basket&type=artist&id=' . $artist->id,_('Add All Songs By') . ' ' . $artist->f_name,'play_full_artist'); ?><br />
<?php echo Ajax::text('?action=basket&type=artist_random&id=' . $artist->id,_('Add Random Songs By') . ' ' . $artist->f_name,'play_random_artist'); ?><br />
<?php if ($GLOBALS['user']->has_access('50')) { ?>
	<a href="<?php echo $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php echo $artist->id; ?>"><?php echo _("Update from tags"); ?></a><br />
	<a href="<?php echo $web_path; ?>/artists.php?action=show_similar&amp;artist=<?php echo $artist->id; ?>"><?php echo _("Find duplicate artists"); ?></a><br />
<?php } ?>
<?php if (Plugin::is_installed('OpenStrands')) { ?>
<?php echo Ajax::text('?page=stats&action=show_recommend&type=artist&id=' . $artist->id,_('Recommend Similar'),'artist_recommend_similar'); ?>
<?php } ?>
</div>
<?php show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>


