<?php
/*

 Copyright (c) Ampache.org
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

<div id="information_actions">
<ul>
<li><a href="<?php echo $web_path; ?>/artists.php?action=show_all_songs&amp;artist=<?php echo $artist->id; ?>"><?php echo get_user_icon('view'); ?></a> <?php echo _("Show All Songs By") . " " . $artist->f_name; ?></li>
<li><?php echo Ajax::button('?action=basket&type=artist&id=' . $artist->id,'add',_('Add'),'add_' . $artist->id); ?><?php echo _('Add All songs By') . ' ' . $artist->f_name; ?></li>
<li><?php echo Ajax::button('?action=basket&type=artist_random&id=' . $artist->id,'random',_('Random'),'random_' . $artist->id); ?><?php echo _('Add Random Songs By') . ' ' . $artist->f_name; ?></li>
<?php if (Access::check('interface','50')) { ?>
	<li><a href="<?php echo $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php echo $artist->id; ?>"><?php echo get_user_icon('cog'); ?></a> <?php echo _('Update from tags'); ?></li>
<?php } ?>
<li>
	<input type="checkbox" id="show_artist_artCB" <?php echo $string = Browse::get_filter('show_art') ? 'checked="checked"' : ''; ?>/> <?php echo _('Show Art'); ?>
	<?php echo Ajax::observe('show_artist_artCB','click',Ajax::action('?page=browse&action=browse&key=show_art&value=1','')); ?>
</ul>
</div>
<?php show_box_bottom(); ?>
