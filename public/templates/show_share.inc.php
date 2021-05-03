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
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\Share;
use Ampache\Module\Util\Ui;

/** @var Share $share */
/** @var Stream_Playlist $embed */

$embed = $_REQUEST['embed'];

$is_share = true;

require Ui::find_template('show_web_player.inc.php');

if (empty($embed)) {
    echo "<a href='" . $share->getPublicUrl() . "'>" . T_('Shared by') . ' ' . $share->getUserName() . "</a><br />";
    if ($share->getAllowDownload()) {
        echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=download&id=" . $share->getId() . "&secret=" . $share->getSecret() . "\">" . Ui::get_icon('download', T_('Download')) . "</a> ";
        echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=download&id=" . $share->getId() . "&secret=" . $share->getSecret() . "\">" . T_('Download') . "</a>";
    }
}

if (!empty($embed)) {
    Ui::show_box_bottom();
} else { ?>
</body>
</html>
<?php
}
