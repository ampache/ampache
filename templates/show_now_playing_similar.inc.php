<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

<?php if ($artists) { ?>
<div class="np_group similars">
    <div class="np_cell cel_similar">
        <label><?php echo T_('Similar Artists'); ?></label>
        <?php foreach ($artists as $a) { ?>
            <div class="np_cell cel_similar_artist">
            <?php
                if (is_null($a['id'])) {
                    if (AmpConfig::get('wanted') && $a['mbid']) {
                        echo "<a class=\"missing_album\" href=\"" . AmpConfig::get('web_path') . "/artists.php?action=show_missing&mbid=" . $a['mbid'] . "\" title=\"" . scrub_out($a['name']) . "\">" . scrub_out($a['name']) . "</a>";
                    } else {
                        echo scrub_out($a['name']);
                    }
                } else {
                    $artist = new Artist($a['id']);
                    $artist->format();
                    echo $artist->f_name_link;
                }
            ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<?php if ($songs) { ?>
<div class="np_group similars">
    <div class="np_cell cel_similar">
        <label><?php echo T_('Similar Songs'); ?></label>
        <?php foreach ($songs as $s) { ?>
            <div class="np_cell cel_similar_song">
            <?php
                $song = new Song($s['id']);
                $song->format();
                echo $song->f_link;
            ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>
