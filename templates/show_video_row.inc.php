<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
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
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) { ?>
        <?php echo Ajax::button('?page=stream&action=directplay&playtype=video&video_id=' . $libitem->id,'play', T_('Play'),'play_video_' . $libitem->id); ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&playtype=video&video_id=' . $libitem->id . '&append=true','play_add', T_('Play last'),'addplay_video_' . $libitem->id); ?>
        <?php } ?>
<?php } ?>
    </div>
</td>
<?php
if (Art::is_enabled()) {
?>
<td class="cel_cover">
    <a href="<?php echo $libitem->link; ?>">
        <img height="150" width="100" alt="<?php echo $libitem->f_title; ?>" title="<?php echo $libitem->f_title; ?>" src="<?php echo AmpConfig::get('web_path'); ?>/image.php?object_type=video&object_id=<?php echo $libitem->id; ?>&thumb=6" />
    </a>
</td>
<?php } ?>
<td class="cel_title"><?php echo $libitem->f_link; ?></td>
<?php
if (isset($video_type)) {
    require AmpConfig::get('prefix') . '/templates/show_partial_' . $video_type . '_row.inc.php';
}
?>
<td class="cel_codec"><?php echo $libitem->f_codec; ?></td>
<td class="cel_resolution"><?php echo $libitem->f_resolution; ?></td>
<td class="cel_length"><?php echo $libitem->f_length; ?></td>
<td class="cel_tags"><?php echo $libitem->f_tags; ?></td>
<?php if (AmpConfig::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $libitem->id; ?>_video"><?php Rating::show($libitem->id, 'video'); ?></td>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<td class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_video"><?php Userflag::show($libitem->id, 'video'); ?></td>
<?php } ?>
<td class="cel_action">
<a href="<?php echo $libitem->link; ?>"><?php echo UI::get_icon('preferences', T_('Video Information')); ?></a>
<?php if (AmpConfig::get('sociable')) { ?>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=video&id=<?php echo $libitem->id; ?>">
    <?php echo UI::get_icon('comment', T_('Post Shout')); ?>
    </a>
<?php } ?>
<?php if (AmpConfig::get('share')) { ?>
    <a href="<?php echo $web_path; ?>/share.php?action=show_create&type=video&id=<?php echo $libitem->id; ?>"><?php echo UI::get_icon('share', T_('Share')); ?></a>
<?php } ?>
<?php if (Access::check_function('download')) { ?>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&video_id=<?php echo $libitem->id; ?>"><?php echo UI::get_icon('download', T_('Download')); ?></a>
<?php } ?>
<?php if (Access::check('interface','50')) { ?>
    <a id="<?php echo 'edit_video_'.$libitem->id ?>" onclick="showEditDialog('video_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_video_'.$libitem->id ?>', '<?php echo T_('Video edit') ?>', 'video_')">
        <?php echo UI::get_icon('edit', T_('Edit')); ?>
    </a>
<?php } ?>
</td>
