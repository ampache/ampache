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

$web_path = Config::get('web_path');
?>
<?php
$browse = new Browse();
$browse->set_type($object_type);
UI::show_box_top($artist->f_name, 'info-box');
if (Config::get('ratings')) {
?>
<div id="rating_<?php echo intval($artist->id); ?>_artist" style="display:inline;">
    <?php show_rating($artist->id, 'artist'); ?>
</div>
<?php } ?>
<div id="information_actions">
<h3><?php echo T_('Actions'); ?>:</h3>
<ul>
<li>
    <?php if ($object_type == 'album') { ?>
    <a href="<?php echo $web_path; ?>/artists.php?action=show_all_songs&amp;artist=<?php echo $artist->id; ?>">
    <?php echo UI::get_icon('view', T_("Show All Songs By %s")); ?>
    <?php printf(T_("Show All Songs By %s"), $artist->f_name); ?></a>
    <?php } else { ?>
    <a href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo $artist->id; ?>">
    <?php echo UI::get_icon('view', T_("Show Albums By %s")); ?>
    <?php printf(T_("Show Albums By %s"), $artist->f_name); ?></a>
    <?php } ?>
</li>
<li>
    <?php /* HINT: Artist Fullname */ ?>
    <?php echo Ajax::button('?action=basket&type=artist&id=' . $artist->id,'add', T_('Add'),'add_' . $artist->id); ?>
    <?php echo Ajax::text('?action=basket&type=artist&id=' . $artist->id,sprintf(T_('Add All Songs By %s'), $artist->f_name),'add_text_' . $artist->id); ?>
</li>
<li>
    <?php /* HINT: Artist Fullname */ ?>
    <?php echo Ajax::button('?action=basket&type=artist_random&id=' . $artist->id,'random', T_('Random'),'random_' . $artist->id); ?>
    <?php echo Ajax::text('?action=basket&type=artist_random&id=' . $artist->id, sprintf(T_('Add Random Songs By %s'), $artist->f_name),'random_text_' . $artist->id); ?>
</li>
<?php if (Access::check('interface','50')) { ?>
<li>
    <a href="<?php echo $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php echo $artist->id; ?>"><?php echo UI::get_icon('cog', T_('Update from tags')); ?></a>
    <a href="<?php echo $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php echo $artist->id; ?>"><?php echo T_('Update from tags'); ?></a>
</li>
<?php } ?>
<?php if (Access::check_function('batch_download')) { ?>
<li>
    <a href="<?php echo $web_path; ?>/batch.php?action=artist&id=<?php echo $artist->id; ?>"><?php echo UI::get_icon('batch_download', T_('Download')); ?></a>
    <a href="<?php echo $web_path; ?>/batch.php?action=artist&id=<?php echo $artist->id; ?>"><?php echo T_('Download'); ?></a>
</li>
<?php } ?>
<li>
        <input type="checkbox" id="show_artist_artCB" <?php echo $string = Art::is_enabled() ? 'checked="checked"' : ''; ?>/> <?php echo T_('Show Art'); ?>
        <?php echo Ajax::observe('show_artist_artCB', 'click', Ajax::action('?page=browse&action=show_art&browse_id=' . $browse->id,'')); ?>
</ul>
</div>
<?php UI::show_box_bottom(); ?>
<?php
    $browse->show_objects($object_ids);
    $browse->store();
?>
