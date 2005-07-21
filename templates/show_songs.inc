<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved.

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
$web_path = conf('web_path');
?>


<form name="songs" method="post" enctype="multipart/form-data" action="#">
<table border="0">
  <tr><td colspan="2">
    <table class="border" cellspacing="0" cellpadding="0" border="0">
    <tr class="table-header">
            <th>&nbsp;&nbsp;<a href="#" onclick="check_songs(); return false;">Select</a></th>
            <?php if (isset($playlist_id) && $playlist_id != 0 && ($user->username == $pluser->username || $user->has_access('100'))) { $tab = 1; ?> <th><?php echo _("Track"); ?></th> <?php } ?>
            <th><?php echo _("Song title"); ?></th>
            <th><?php echo _("Artist"); ?></th>
            <th><?php echo _("Album"); ?></th>
            <th><?php echo _("Track"); ?></th>
            <th><?php echo _("Time"); ?></th>
            <th><?php echo _("Size"); ?></th>
            <th><?php echo _("Bitrate"); ?></th>
            <th><?php echo _("Genre"); ?></th>
            <th><?php echo _("Flag"); ?></th>
            <th><?php echo _("Action"); ?></th>
    </tr>
    <?php
    foreach ($song_ids as $song_id) {

	    unset($text_class);
            $song = new Song($song_id);
            $song->format_song();

            // Still needed crap
            $totalsize += $song->size;
            $totaltime += $song->time;
	    if ($song->status == "disabled") { $text_class = "class=\"disabled\""; } 
    ?>
    <tr class="<?php echo flip_class(); ?>">
            <td align="center">
              <input type="checkbox" name="song[]" value="<?php echo $song->id; ?>" id="song_<?php echo $song->id; ?>"></input>
            </td>
        <?php
            if (isset($playlist_id) && $playlist_id != 0 && ($user->username == $pluser->username || $user->has_access('100'))) {
                    $tracknum = get_playlist_track_from_song($playlist_id, $song->id);
        ?>
            <td>
              <input type="text" tabindex="<?php echo $tab; ?>" size="3" name="<?php echo "tr_" . $song->id; ?>" value="<?php echo $tracknum ?>" onchange="<?php echo "document.getElementById('song_" . $song->id . "').checked='checked';"; ?>" />
            </td>
        <?php
                    $tab++;
            }
        ?>
             <td>
              <a href="<?php echo $web_path; ?>/song.php?action=m3u&amp;song=<?php echo $song->id; ?>" title="<?php echo htmlspecialchars($song->title); ?>" <?php echo $text_class; ?>> <?php echo htmlspecialchars($song->f_title); ?> </a>
            </td>
            <td>
              <a href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo htmlspecialchars($song->artist); ?>" title="<?php echo htmlspecialchars($song->f_artist_full); ?>" <?php echo $text_class; ?>> <?php echo htmlspecialchars($song->f_artist); ?> </a>
            </td>
            <td>
              <a href="<?php echo $web_path; ?>/albums.php?album=<?php echo htmlspecialchars($song->album); ?>" title="<?php echo htmlspecialchars($song->f_album_full); ?>" <?php echo $text_class; ?>> <?php echo htmlspecialchars($song->f_album); ?> </a>
            </td>
            <td align="right">
              <?php echo $song->track; ?>
            </td>
            <td align="right">
              <?php echo $song->f_time; ?>
            </td>
            <td align="right" nowrap="nowrap">
              <?php echo $song->f_size; ?> MB
            </td>
            <td align="right">
              <?php echo $song->f_bitrate; ?>
            </td>
            <td>
              <?php echo $song->f_genre; ?>
            </td>
            <td <?php echo $song->f_style; ?> title="<?php echo $song->flagcomment; ?>">
              <?php echo $song->flagtype; ?>
            </td>
            <td>
              <?php if ($user->has_access('100')) { ?>
                <a href="<?php echo $web_path; ?>/admin/song.php?action=edit&amp;song=<?php echo $song->id; ?>">Edit</a> | <a href="<?php echo $web_path; ?>/flag.php?song=<?php echo $song->id; ?>&amp;action=flag">Flag</a> | 
                  <?php if ($song->status === 'enabled') { ?>
                    <a href="<?php echo $web_path; ?>/admin/song.php?action=disable&amp;song_ids=<?php echo $song->id; ?>">Disable</a>
                  <?php } else { ?>
                    <a href="<?php echo $web_path; ?>/admin/song.php?action=enabled&amp;song_ids=<?php echo $song->id; ?>">Enable</a>
                  <?php } //status ?>
              <?php } else { ?>
                <a href="<?php echo $web_path; ?>/flag.php?song=<?php echo $song->id; ?>&amp;action=flag">Flag</a>
              <?php } //access ?>
		<?php if ($user->prefs['download']) { ?>
			| <a href="<?php echo $web_path; ?>/download/index.php?action=download&amp;song_id=<?php echo $song->id; ?>&amp;fn=<?php echo rawurlencode($song->f_artist_full . " - " . $song->title . "." . $song->type); ?>"><?php echo _("Download"); ?></a>
		<?php } ?>
		<?php if ($user->prefs['direct_link']) { ?>
		| <a href="<?php echo $web_path; ?>/play/index.php?song=<?php echo $song->id; ?>&amp;uid=<?php echo $user->username . "&amp;sid=" . session_id(); ?>&amp;fn=<?php echo rawurlencode($song->f_artist_full . " - " . $song->title . "." . $song->type); ?>"><?php echo _("Direct Link"); ?></a>
		<?php } ?>
            </td>
          </tr>
    <?
    }// foreach loop

    //
    // Another here doc
    //
    $time = floor($totaltime/60) . ":" . sprintf("%02d", ($totaltime%60) );
    $megs = sprintf("%.2f", ($totalsize/1048576));
    $num = count($song_ids);

    ?>
    <tr class="table-header">
      <td></td>
      <?php if (isset($playlist_id) && $playlist_id != 0 && ($user->username == $pluser->username || $user->access === 'admin')) { ?> <td></td> <?php } ?>
      <td><?php echo _("Total"); ?>:</td>
      <td nowrap="nowrap"><?php echo $num; ?> song(s)</td>
      <td></td>
      <td></td>
      <td align="right" nowrap="nowrap"><?php echo $time; ?></td>
      <td align="right" nowrap="nowrap"><?php echo $megs; ?> MB</td>
      <td></td>
      <td></td>
      <td></td>
      <td colspan="2"></td>
    </tr>
    </table>
</td></tr>
</table>
<br />
<?php show_play_selected(); ?>
</form>

