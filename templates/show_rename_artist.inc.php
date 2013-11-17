<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
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
<script type='text/javascript'>
function insert()
{
    document.getElementById('artist_name').value = '<?php echo $artist->name; ?>';
}
</script>
<?php /* HINT: Artist Name */ UI::show_box_top(sprintf(T_('Rename %s'), $artist->name)); ?>
<form name="rename_artist" method="post" action="<?php echo Config::get('web_path'); ?>/artists.php?action=rename&amp;artist=<?php echo $artist->id; ?>" style="Display:inline;">
        <?php show_artist_pulldown($artist->id, "artist_id", 4); ?>
    <br />
    <?php echo T_('OR'); ?><br />
    <input type="text" name="artist_name" size="30" value="<?php echo scrub_out($_REQUEST['artist_name']); ?>" id="artist_name" />
    <a href="javascript:insert()">[<?php echo T_('Insert current'); ?>]</a><br />
    <?php $GLOBALS['error']->print_error('artist_name'); ?>
    <input type="checkbox" name="update_id3" value="yes" />&nbsp; <?php echo T_('Update id3 tags') ?><br />
    <input type="submit" value="<?php echo T_('Rename'); ?>" /><br />
</form>
<?php UI::show_box_bottom(); ?>
