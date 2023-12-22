<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\EnvironmentInterface;

// TODO remove me
global $dic;
$environment = $dic->get(EnvironmentInterface::class);
$t_search    = T_('Search'); ?>
<div id="sb_Subsearch">
    <form name="search" method="post" action="<?php echo $web_path; ?>/search.php" enctype="multipart/form-data" style="Display:inline">
        <input type="text" name="rule_1_input" id="searchString" placeholder="<?php echo $t_search; ?>" />
        <input type="hidden" name="action" value="search" />
        <input type="hidden" name="rule_1_operator" value="0" />
        <input type="hidden" name="object_type" value="song" />
        <select name="rule_1" id="searchStringRule">
            <option value="anywhere"><?php echo T_('Anywhere'); ?></option>
            <option value="title"><?php echo T_('Songs'); ?></option>
            <?php if (AmpConfig::get('album_group')) { ?>
                <option value="album"><?php echo $t_albums; ?></option>
            <?php } else { ?>
                <option value="album_disk"><?php echo $t_albums; ?></option>
            <?php } ?>
            <option value="artist"><?php echo $t_artists; ?></option>
            <option value="playlist"><?php echo $t_playlists; ?></option>
            <?php if (AmpConfig::get('label')) { ?>
                <option value="label"><?php echo T_('Labels'); ?></option>
            <?php } ?>
            <?php if (AmpConfig::get('wanted')) { ?>
                <option value="missing_artist"><?php echo T_('Missing Artists'); ?></option>
            <?php } ?>
        </select>
        <?php if ($environment->isMobile()) {
            echo "<input class=\"button\" type=\"submit\" value=\"" . $t_search . "\"style=\"display: none;\" id=\"searchBtn\" />";
        } else {
            echo "<input class=\"button\" type=\"submit\" value=\"" . $t_search . "\" id=\"searchBtn\" />";
        } ?>
    </form>
</div>

