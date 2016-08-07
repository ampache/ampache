<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$icon                 = $song->enabled ? 'disable' : 'enable';
$button_flip_state_id = 'button_flip_state_' . $song->id;
?>
<?php UI::show_box_top($song->title . ' ' . T_('Details'), 'box box_song_details'); ?>
<dl class="media_details">

<?php if (User::is_registered()) {
    ?>
    <?php if (AmpConfig::get('ratings')) {
    ?>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Rating'); ?></dt>
        <dd class="<?php echo $rowparity; ?>">
            <div id="rating_<?php echo $song->id; ?>_song"><?php Rating::show($song->id, 'song'); ?>
            </div>
        </dd>
    <?php

} ?>

    <?php if (AmpConfig::get('userflags')) {
    ?>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Fav.'); ?></dt>
        <dd class="<?php echo $rowparity; ?>">
            <div id="userflag_<?php echo $song->id; ?>_song"><?php Userflag::show($song->id, 'song'); ?>
            </div>
        </dd>
    <?php

} ?>
<?php

} ?>
<?php if (AmpConfig::get('waveform')) {
    ?>
    <?php $rowparity = UI::flip_class(); ?>
    <dt class="<?php echo $rowparity; ?>"><?php echo T_('Waveform'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <div id="waveform_<?php echo $song->id; ?>">
            <img src="<?php echo AmpConfig::get('web_path'); ?>/waveform.php?song_id=<?php echo $song->id; ?>" />
        </div>
    </dd>
<?php

} ?>
<?php $rowparity = UI::flip_class(); ?>
<dt class="<?php echo $rowparity; ?>"><?php echo T_('Action'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <?php if (AmpConfig::get('directplay')) {
    ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $song->id, 'play', T_('Play'), 'play_song_' . $song->id); ?>
            <?php if (Stream_Playlist::check_autoplay_append()) {
    ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $song->id . '&append=true', 'play_add', T_('Play last'), 'addplay_song_' . $song->id); ?>
            <?php

} ?>
            <?php if (Stream_Playlist::check_autoplay_next()) {
    ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $song->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_song_' . $song->id); ?>
            <?php

} ?>
            <?php echo $song->show_custom_play_actions(); ?>
        <?php

} ?>
        <?php echo Ajax::button('?action=basket&type=song&id=' . $song->id, 'add', T_('Add to temporary playlist'), 'add_song_' . $song->id); ?>
        <?php if (!AmpConfig::get('use_auth') || Access::check('interface', '25')) {
    ?>
            <?php if (AmpConfig::get('sociable')) {
    ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=song&id=<?php echo $song->id; ?>">
                <?php echo UI::get_icon('comment', T_('Post Shout')); ?>
                </a>
            <?php

} ?>
        <?php 
}
    ?>
        <?php if (Access::check('interface', '25')) {
    ?>
            <?php if (AmpConfig::get('share')) {
    ?>
                <?php Share::display_ui('song', $song->id, false); ?>
            <?php

} ?>
        <?php

} ?>
        <?php if (Access::check_function('download')) {
    ?>
            <a rel="nohtml" href="<?php echo Song::play_url($song->id); ?>"><?php echo UI::get_icon('link', T_('Link')); ?></a>
            <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $song->id; ?>"><?php echo UI::get_icon('download', T_('Download')); ?></a>
        <?php

} ?>
        <?php if (($song->user_upload > 0 && $song->user_upload == $GLOBALS['user']->id) || Access::check('interface', '50')) {
    ?>
            <?php if (AmpConfig::get('statistical_graphs')) {
    ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/stats.php?action=graph&object_type=song&object_id=<?php echo $song->id; ?>"><?php echo UI::get_icon('statistics', T_('Graphs')); ?></a>
            <?php

} ?>
        <?php

} ?>
        <?php if (Access::check('interface', '50') || ($song->user_upload == $GLOBALS['user']->id && AmpConfig::get('upload_allow_edit'))) {
    ?>
            <a onclick="showEditDialog('song_row', '<?php echo $song->id ?>', '<?php echo 'edit_song_' . $song->id ?>', '<?php echo T_('Edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
        <?php

} ?>
        <?php if (Access::check('interface', '75') || ($song->user_upload == $GLOBALS['user']->id && AmpConfig::get('upload_allow_edit'))) {
    ?>
            <span id="<?php echo($button_flip_state_id); ?>">
            <?php echo Ajax::button('?page=song&action=flip_state&song_id=' . $song->id, $icon, T_(ucfirst($icon)), 'flip_song_' . $song->id); ?>
            </span>
        <?php

} ?>
        <?php if (Catalog::can_remove($song)) {
    ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/song.php?action=delete&song_id=<?php echo $song->id; ?>">
                <?php echo UI::get_icon('delete', T_('Delete')); ?>
            </a>
        <?php

} ?>
    </dd>
<?php
  $songprops[gettext_noop('Title')]   = scrub_out($song->title);
  $songprops[gettext_noop('Artist')]  = $song->f_artist_link;
  if (!empty($song->f_albumartist_link)) {
      $songprops[gettext_noop('Album Artist')]   = $song->f_albumartist_link;
  }
  $songprops[gettext_noop('Album')]      = $song->f_album_link . ($song->year ? " (" . scrub_out($song->year) . ")" : "");
  $songprops[gettext_noop('Composer')]   = scrub_out($song->composer);
  $songprops[gettext_noop('Genre')]      = $song->f_tags;
  $songprops[gettext_noop('Year')]       = $song->year;
  $songprops[gettext_noop('Links')]      = "<a href=\"http://www.google.com/search?q=%22" . rawurlencode($song->f_artist) . "%22+%22" . rawurlencode($song->f_title) . "%22\" target=\"_blank\">" . UI::get_icon('google', T_('Search on Google ...')) . "</a>";
  $songprops[gettext_noop('Links')]      = "<a href=\"https://www.duckduckgo.com/?q=%22" . rawurlencode($song->f_artist) . "%22+%22" . rawurlencode($song->f_title) . "%22\" target=\"_blank\">" . UI::get_icon('duckduckgo', T_('Search on DuckDuckGo ...')) . "</a>";
  $songprops[gettext_noop('Links')] .= "&nbsp;<a href=\"http://www.last.fm/search?q=%22" . rawurlencode($song->f_artist) . "%22+%22" . rawurlencode($song->f_title) . "%22&type=track\" target=\"_blank\">" . UI::get_icon('lastfm', T_('Search on Last.fm ...')) . "</a>";
  $songprops[gettext_noop('Length')]           = scrub_out($song->f_time);
  $songprops[gettext_noop('Comment')]          = scrub_out($song->comment);
  $songprops[gettext_noop('Label')]            = AmpConfig::get('label') ? "<a href=\"" . AmpConfig::get('web_path') . "/labels.php?action=show&name=" . scrub_out($song->label) . "\">" . scrub_out($song->label) . "</a>" : scrub_out($song->label);
  $songprops[gettext_noop('Song Language')]    = scrub_out($song->language);
  $songprops[gettext_noop('Catalog Number')]   = scrub_out($song->catalog_number);
  $songprops[gettext_noop('Bitrate')]          = scrub_out($song->f_bitrate);
  $songprops[gettext_noop('Channels')]         = scrub_out($song->channels);
  if ($song->replaygain_track_gain != 0) {
      $songprops[gettext_noop('ReplayGain Track Gain')]   = scrub_out($song->replaygain_track_gain);
  }
  if ($song->replaygain_album_gain != 0) {
      $songprops[gettext_noop('ReplayGain Album Gain')]   = scrub_out($song->replaygain_album_gain);
  }
  if (Access::check('interface', '75')) {
      $songprops[gettext_noop('Filename')]   = scrub_out($song->file) . " " . $song->f_size;
  }
  if ($song->update_time) {
      $songprops[gettext_noop('Last Updated')]   = date("d/m/Y H:i", $song->update_time);
  }
  $songprops[gettext_noop('Added')]   = date("d/m/Y H:i", $song->addition_time);
  if (AmpConfig::get('show_played_times')) {
      $songprops[gettext_noop('# Played')]   = scrub_out($song->object_cnt);
  }

  if (AmpConfig::get('show_lyrics')) {
      $songprops[gettext_noop('Lyrics')]   = $song->f_lyrics;
  }

  if (AmpConfig::get('licensing') && $song->license) {
      $songprops[gettext_noop('Licensing')] = $song->f_license;
  }

  $owner_id = $song->get_user_owner();
  if (AmpConfig::get('sociable') && $owner_id > 0) {
      $owner = new User($owner_id);
      $owner->format();
      $songprops[gettext_noop('Uploaded by')]  = $owner->f_link;
  }


    foreach ($songprops as $key => $value) {
        if (trim($value)) {
            $rowparity = UI::flip_class();
            echo "<dt class=\"" . $rowparity . "\">" . T_($key) . "</dt><dd class=\"" . $rowparity . "\">" . $value . "</dd>";
        }
    }

    if (Song::isCustomMetadataEnabled()) {
        $dismetas = $song->getDisabledMetadataFields();
        foreach ($song->getMetadata() as $metadata) {
            if (!in_array($metadata->getField()->getName(), $dismetas)) {
                $rowparity = UI::flip_class();
                echo '<dt class="' . $rowparity . '">' . $metadata->getField()->getFormattedName() . '</dt>';
                echo '<dd class="' . $rowparity . '">' . $metadata->getData() . '</dd>';
            }
        }
    }
?>
</dl>
<?php UI::show_box_bottom(); ?>
