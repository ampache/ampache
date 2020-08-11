<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */

/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>
<?php UI::show_box_top(T_('Find Duplicates'), 'box box_duplicate'); ?>
<form name="duplicates" action="<?php echo AmpConfig::get('web_path'); ?>/admin/duplicates.php?action=find_duplicates" method="post" enctype="multipart/form-data">
    <div class="find-duplicates">
        <strong><?php echo T_('Search Type'); ?>:</strong><br />
        <input type="radio" name="search_type" id="title" value="title" /><label for="title"><?php echo T_('Title'); ?></label><br />
        <input type="radio" name="search_type" id="artist_title" value="artist_title" /><label for="artist_title"><?php echo T_('Artist and Title'); ?></label><br />
        <input type="radio" name="search_type" id="artist_album_title" value="artist_album_title" /><label for="artist_album_title"><?php echo T_('Artist, Album and Title'); ?></label><br />
        <input type="radio" name="search_type" id="album" value="album" /><label for="album"><?php echo T_('Album'); ?></label>
    </div>
    <div class="formValidation">
        <input type="submit" value="<?php echo T_('Find Duplicates'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>