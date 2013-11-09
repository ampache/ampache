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

$icon = $song->enabled ? 'disable' : 'enable';
$button_flip_state_id = 'button_flip_state_' . $song->id;
?>
<?php UI::show_box_top($song->title . ' ' . T_('Details'), 'box box_song_details'); ?>
<dl class="song_details">
<?php if (Config::get('ratings')) { ?>
<dt class="<?php echo UI::flip_class(); ?>"><?php echo T_('Rating'); ?></dt>
<dd><div id="rating_<?php echo $song->id; ?>_song"><?php Rating::show($song->id,'song'); ?></div></dd>
<?php } ?>
<?php if (Config::get('userflags')) { ?>
<dt class="<?php echo UI::flip_class(); ?>"><?php echo T_('Flag'); ?></dt>
<dd><div id="userflag_<?php echo $song->id; ?>_song"><?php Userflag::show($song->id,'song'); ?></div></dd>
<?php } ?>
<dt class="<?php echo $rowparity; ?>"><?php echo T_('Action'); ?></dt>
    <dd class="<?php echo UI::flip_class(); ?>">
        <?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add', T_('Add'),'add_song_' . $song->id); ?>
        <?php if (Access::check_function('download')) { ?>
            <a href="<?php echo Song::play_url($song->id); ?>"><?php echo UI::get_icon('link', T_('Link')); ?></a>
            <a href="<?php echo Config::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $song->id; ?>"><?php echo UI::get_icon('download', T_('Download')); ?></a>
        <?php } ?>
        <?php if (Access::check('interface','75')) { ?>
            <span id="<?php echo($button_flip_state_id); ?>">
            <?php echo Ajax::button('?page=song&action=flip_state&song_id=' . $song->id,$icon, T_(ucfirst($icon)),'flip_song_' . $song->id); ?>
            </span>
        <?php } ?>
    </dd>
<?php
  $songprops[gettext_noop('Title')]   = scrub_out($song->title);
  $songprops[gettext_noop('Artist')]  = $song->f_artist_link;
  $songprops[gettext_noop('Album')]   = $song->f_album_link . " (" . scrub_out($song->year). ")";
  $songprops[gettext_noop('Genre')]   = $song->f_genre_link;
  $songprops[gettext_noop('Length')]  = scrub_out($song->f_time);
  $songprops[gettext_noop('Comment')] = scrub_out($song->comment);
  $songprops[gettext_noop('Label')]   = scrub_out($song->label);
  $songprops[gettext_noop('Song Language')]= scrub_out($song->language);
  $songprops[gettext_noop('Catalog Number')]   = scrub_out($song->catalog_number);
  $songprops[gettext_noop('Bitrate')]   = scrub_out($song->f_bitrate);
  if (Access::check('interface','75')) {
    $songprops[gettext_noop('Filename')]   = scrub_out($song->file) . " " . $song->f_size;
  }
  if ($song->update_time) {
    $songprops[gettext_noop('Last Updated')]   = date("d/m/Y H:i",$song->update_time);
  }
  $songprops[gettext_noop('Added')]   = date("d/m/Y H:i",$song->addition_time);

  foreach ($songprops as $key => $value)
  {
    if(trim($value))
    {
      $rowparity = UI::flip_class();
      echo "<dt class=\"".$rowparity."\">" . T_($key) . "</dt><dd class=\"".$rowparity."\">" . $value . "</dd>";
    }
  }
?>
</dl>
<?php UI::show_box_bottom(); ?>
