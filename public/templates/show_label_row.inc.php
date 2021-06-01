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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Label;

/** @var Label $libitem */
/** @var MediaDeletionCheckerInterface $mediaDeletionChecker */

global $dic;
$mediaDeletionChecker = $dic->get(MediaDeletionCheckerInterface::class);

if (Art::is_enabled()) {
    $name = $libitem->getNameFormatted(); ?>
    <td class="<?php echo $cel_cover; ?>">
        <?php echo Art::display('label', $libitem->getId(), $name, 1, AmpConfig::get('web_path') . '/labels.php?action=show&label=' . $libitem->getId()); ?>
    </td>
    <?php
} ?>
<td class="cel_label"><?php
    $name = $libitem->getNameFormatted();
    echo sprintf(
        '<a href="%s" title="%s">%s',
        $libitem->getLink(),
        $name,
        $name
    ); ?></td>
<td class="cel_category"><?php echo $libitem->getCategory(); ?></td>
<td class="cel_artists"><?php echo $libitem->getArtistCount(); ?></td>
<td class="cel_action">
<?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) {
        if (AmpConfig::get('sociable')) { ?>
    <a href="<?php echo AmpConfig::get('web_path') ?>/shout.php?action=show_add_shout&type=label&amp;id=<?php echo $libitem->getId() ?>">
        <?php echo Ui::get_icon('comment', T_('Post Shout')) ?>
    </a>
    <?php
    }
        if ($mediaDeletionChecker->mayDelete($libitem, Core::get_global('user')->getId())) {?>
        <a id="<?php echo 'delete_label_' . $libitem->getId() ?>" href="<?php echo AmpConfig::get('web_path') ?>/labels.php?action=delete&label_id=<?php echo $libitem->getId() ?>">
            <?php echo Ui::get_icon('delete', T_('Delete')) ?>
        </a>
    <?php
    }
    } ?>
</td>
