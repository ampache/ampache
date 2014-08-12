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

$icon = $song->enabled ? 'disable' : 'enable';
$button_flip_state_id = 'button_flip_state_' . $song->id;
?>
<?php UI::show_box_top($song->title . ' ' . T_('Details'), 'box box_song_details'); ?>
<dl class="media_details">

<?php if (AmpConfig::get('ratings')) { ?>
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Rating'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <div id="rating_<?php echo $song->id; ?>_song"><?php Rating::show($song->id,'song'); ?>
        </div>
    </dd>
<?php } ?>

<?php if (AmpConfig::get('userflags')) { ?>
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Fav.'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <div id="userflag_<?php echo $song->id; ?>_song"><?php Userflag::show($song->id,'song'); ?>
        </div>
    </dd>
<?php } ?>
<?php if (AmpConfig::get('waveform')) { ?>
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Waveform'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <div id="waveform_<?php echo $song->id; ?>">
            <img src="<?php echo AmpConfig::get('web_path'); ?>/waveform.php?song_id=<?php echo $song->id; ?>" />
        </div>
    </dd>
<?php } ?>
<?php $rowparity = UI::flip_class(); ?>
<dt class="<?php echo $rowparity; ?>"><?php echo T_('Action'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $song->id, 'play', T_('Play'),'play_song_' . $song->id); ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $song->id . '&append=true','play_add', T_('Play last'),'addplay_song_' . $song->id); ?>
            <?php } ?>
            <?php echo $song->show_custom_play_actions(); ?>
        <?php } ?>
        <?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add', T_('Add to temporary playlist'),'add_song_' . $song->id); ?>
        <?php if (AmpConfig::get('sociable')) { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=song&id=<?php echo $song->id; ?>">
            <?php echo UI::get_icon('comment', T_('Post Shout')); ?>
            </a>
        <?php } ?>
        <?php if (AmpConfig::get('share')) { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/share.php?action=show_create&type=song&id=<?php echo $song->id; ?>"><?php echo UI::get_icon('share', T_('Share')); ?></a>
        <?php } ?>
        <?php if (Access::check_function('download')) { ?>
            <a rel="nohtml" href="<?php echo Song::play_url($song->id); ?>"><?php echo UI::get_icon('link', T_('Link')); ?></a>
            <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $song->id; ?>"><?php echo UI::get_icon('download', T_('Download')); ?></a>
        <?php } ?>
        <?php if (Access::check('interface','50')) { ?>
            <a onclick="showEditDialog('song_row', '<?php echo $song->id ?>', '<?php echo 'edit_song_'.$song->id ?>', '<?php echo T_('Edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
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
  if (!empty($song->f_album_artist_link)) {
    $songprops[gettext_noop('Album Artist')]   = $song->f_album_artist_link;
  }
  $songprops[gettext_noop('Album')]   = $song->f_album_link . ($song->year ? " (" . scrub_out($song->year). ")" : "");
  $songprops[gettext_noop('Composer')]   = scrub_out($song->composer);
  $songprops[gettext_noop('Genre')]   = $song->f_tags;
  $songprops[gettext_noop('Length')]  = scrub_out($song->f_time);
  $songprops[gettext_noop('Comment')] = scrub_out($song->comment);
  $songprops[gettext_noop('Label')]   = scrub_out($song->label);
  $songprops[gettext_noop('Song Language')]= scrub_out($song->language);
  $songprops[gettext_noop('Catalog Number')]   = scrub_out($song->catalog_number);
  $songprops[gettext_noop('Bitrate')]   = scrub_out($song->f_bitrate);
  $songprops[gettext_noop('Channels')]   = scrub_out($song->channels);
  if (Access::check('interface','75')) {
    $songprops[gettext_noop('Filename')]   = scrub_out($song->file) . " " . $song->f_size;
  }
  if ($song->update_time) {
    $songprops[gettext_noop('Last Updated')]   = date("d/m/Y H:i",$song->update_time);
  }
  $songprops[gettext_noop('Added')]   = date("d/m/Y H:i",$song->addition_time);
  if (AmpConfig::get('show_played_times')) {
    $songprops[gettext_noop('# Played')]   = scrub_out($song->object_cnt);
  }

  if (AmpConfig::get('show_lyrics')) {
     $songprops[gettext_noop('Lyrics')]   = $song->f_lyrics;
  }

    if (AmpConfig::get('licensing')) {
        if ($song->license) {
            $license = new License($song->license);
            $license->format();
            $songprops[gettext_noop('Licensing')]   = $license->f_link;
        }
    }

    foreach ($songprops as $key => $value) {
        if (trim($value)) {
              $rowparity = UI::flip_class();
              echo "<dt class=\"".$rowparity."\">" . T_($key) . "</dt><dd class=\"".$rowparity."\">" . $value . "</dd>";
        }
      }
?>
</dl>
<?php UI::show_box_bottom(); ?>
