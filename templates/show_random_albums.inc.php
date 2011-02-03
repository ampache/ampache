<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Random Albums
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

$web_path = Config::get('web_path');
$button = Ajax::button('?page=index&action=random_albums','random',_('Refresh'),'random_refresh');
?>
<?php show_box_top(_('Albums of the Moment') . ' ' . $button); ?>

	<?php
	if ($albums) {
		foreach ($albums as $album_id) {
			$album = new Album($album_id);
			$album->format();
			$name = '[' . $album->f_artist . '] ' . scrub_out($album->full_name);
        ?>
        <div class="random_album">
                <a href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $album_id; ?>">
                <?php if (Art::is_enabled()) { ?>
                <img src="<?php echo $web_path; ?>/image.php?thumb=3&amp;id=<?php echo $album_id; ?>" width="80" height="80" alt="<?php echo $name; ?>" title="<?php echo $name; ?>" />
                <?php } else { ?>
                <?php echo '[' . $album->f_artist . '] ' . $album->f_name; ?>
                <?php } ?>
                </a>
                <?php
                if(Config::get('ratings')){
                        echo "<div id=\"rating_" . $album->id . "_album\">";
                        show_rating($album->id, 'album');
                        echo "</div>";
                }
                ?>
              	<span class="play_album"><?php echo Ajax::button('?action=basket&type=album&id=' . $album->id,'add',_('Play Album'),'play_full_' . $album->id); ?></span>
        </div>

        	<?php } // end foreach ?>
	<?php } // end if albums ?>

<?php show_box_bottom(); ?>
