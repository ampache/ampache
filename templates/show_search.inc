<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
/**
 * search template
 * This is the template for the searches... amazing!
 */
?>
<?php show_box_top(_('Search Ampache') . "..."); ?>
<form name="search" method="post" action="<?php echo  conf('web_path'); ?>/search.php" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellspacing="0" cellpadding="3" border="0">
<tr class="table-header">
	<td colspan="4">&nbsp;</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
        <td><?php echo _('Keywords') ?></td>
        <td>
            <input type="text" id="s_all" name="s_all" value="<?php echo scrub_out($_REQUEST['s_all']); ?>"/>
        </td>
	<td><?php echo _('Comment'); ?></td>
        <td>
		<input type="text" id="s_comment" name="s_comment" value="<?php echo scrub_out($_REQUEST['s_comment']); ?>" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Title'); ?></td>
	<td>
		<input type="text" id="s_title" name="s_title" value="<?php echo scrub_out($_REQUEST['s_title']); ?>"  />
	</td>
	<td><?php echo _('Artist'); ?></td>
	<td>
		<input type="text" id="s_artist" name="s_artist" value="<?php echo scrub_out($_REQUEST['s_artist']); ?>"  />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Album'); ?></td>
	<td>
		<input type="text" id="s_album" name="s_album" value="<?php echo scrub_out($_REQUEST['s_album']); ?>" />
	</td>
	<td><?php echo _('Genre'); ?></td>
	<td>
		<input type="text" id="s_genre" name="s_genre" value="<?php echo scrub_out($_REQUEST['s_genre']); ?>" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Year'); ?></td>
	<td>
		<input type="text" id="s_year" name="s_year" size="5" value="<?php echo scrub_out($_REQUEST['s_year']); ?>" />
		-
		<input type="text" id="s_year2" name="s_year2" size="5" value="<?php echo scrub_out($_REQUEST['s_year2']); ?>" />
	</td>
	<td><?php echo _('Filename'); ?></td>
	<td>
		<input type="text" id="s_filename" name="s_filename" value="<?php echo scrub_out($_REQUEST['s_filename']); ?>" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Played'); ?></td>
	<td>
		<select id="s_played" name="s_played" >
			<option value="">&nbsp;</option>
			<option value="1" <?php if($_REQUEST['s_played']=="1") echo "selected=\"selected\""?>><?php echo _('Yes'); ?></option>
			<option value="0" <?php if($_REQUEST['s_played']=="0") echo "selected=\"selected\""?>><?php echo _('No'); ?></option>
		</select>
	</td>
	<td><?php echo _('Min Bitrate'); ?></td>
	<td>
		<select id="s_minbitrate" name="s_minbitrate" >
			<option value="">&nbsp;</option>
                 <?php foreach(array(32,40,48,56,64,80,96,112,128,160,192,224,256,320) as $val) { ?>
                            <option value="<?php echo $val?>" <?php if($_REQUEST['s_minbitrate']==$val) echo "selected=\"selected\""?>><?php echo $val?></option>
                 <?php  } ?>
		</select>
	</td>
</tr>		
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Rating'); ?></td>
	<td>
		<select id="s_rating" name="s_rating">
			<option value="">&nbsp;</option>
			<option value="1"><?php echo _('One Star'); ?></option>
			<option value="2"><?php echo _('Two Stars'); ?></option>
			<option value="3"><?php echo _('Three Stars'); ?></option>
			<option value="4"><?php echo _('Four Stars'); ?></option>
			<option value="5"><?php echo _('Five Stars'); ?></option>
		</select>
	</td>
	<td><?php echo _('Operator'); ?></td>
	<td>
		<select name="operator">
			<option value="and" <?php if($_REQUEST['operator']=="and") echo "selected=\"selected\""?>><?php echo _('AND'); ?></option>
			<option value="or"  <?php if($_REQUEST['operator']=="or") echo "selected=\"selected\""?>><?php echo _('OR'); ?></option>
		</select>
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Method'); ?></td>
	<td>
		<select name="method">
			<option value="fuzzy" <?php if($_REQUEST['method']=="fuzzy") echo "selected=\"selected\""?>><?php echo _('Fuzzy'); ?></option>
			<option value="exact" <?php if($_REQUEST['method']=="exact") echo "selected=\"selected\""?>><?php echo _('Exact'); ?></option>
		</select>
	</td>
	<td><?php echo _('Maximum Results'); ?></td>
	<td>
		<select name="limit">
			<option value="0"><?php echo _('Unlimited'); ?></option>
			<option value="25" <?php if($_REQUEST['limit']=="25") echo "selected=\"selected\""?>>25</option>
			<option value="50" <?php if($_REQUEST['limit']=="50") echo "selected=\"selected\""?>>50</option>
			<option value="100" <?php if($_REQUEST['limit']=="100") echo "selected=\"selected\""?>>100</option>
			<option value="500" <?php if($_REQUEST['limit']=="500") echo "selected=\"selected\""?>>500</option>
		</select>
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td>&nbsp;</td>
	<td>
	        <input class="button" type="submit" value="<?php echo _('Search'); ?>" />&nbsp;&nbsp;
	        <input class="button" type="reset" value="<?php echo _('Reset Form');?>" />
	        <input type="hidden" name="action" value="search" />
	</td>
	<td colspan="2">&nbsp;</td>
</tr>
</table>
</form>

<?php if ($_REQUEST['action'] == 'search' || $_REQUEST['action'] == 'quick_search') { ?>
<br />
<form method="post" action="<?php echo conf('web_path'); ?>/playlist.php?action=add_dyn_song">
<?php echo _('Save Search As Track on'); ?>:
<?php show_playlist_select($_SESSION['data']['playlist_id'],'dynamic'); ?>
<input class="button" type="submit" value="<?php echo _('Save'); ?>" /></form>
<?php } ?>
<?php require (conf('prefix') . '/templates/show_box_bottom.inc.php'); ?>
