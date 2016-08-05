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
?>
<div id="sb_Subsearch">
    <form name="search" method="post" action="<?php echo $web_path; ?>/search.php?type=song" enctype="multipart/form-data" style="Display:inline">
        <input type="text" name="rule_1_input" id="searchString" placeholder="<?php echo T_('Search...'); ?>" />
        <input type="hidden" name="action" value="search" />
        <input type="hidden" name="rule_1_operator" value="0" />
        <input type="hidden" name="object_type" value="song" />
        <select name="rule_1" id="searchStringRule">
            <option value="anywhere"><?php echo T_('Anywhere')?></option>
            <option value="title"><?php echo T_('Title')?></option>
            <option value="album"><?php echo T_('Album')?></option>
            <option value="artist"><?php echo T_('Artist')?></option>
            <option value="playlist_name"><?php echo T_('Playlist')?></option>
            <option value="tag"><?php echo T_('Tag')?></option>
            <?php if (AmpConfig::get('label')) {
    ?>
                <option value="label"><?php echo T_('Label')?></option>
            <?php 
} ?>
            <?php if (AmpConfig::get('wanted')) {
    ?>
                <option value="missing_artist"><?php echo T_('Missing Artist')?></option>
            <?php 
} ?>
        </select>
        <input class="button" type="submit" value="<?php echo T_('Search'); ?>" id="searchBtn" />
        <a href="<?php echo $web_path; ?>/search.php?type=song" class="button" id="advSearchBtn"><?php echo T_('Advanced Search'); ?></a>
    </form>
</div>
