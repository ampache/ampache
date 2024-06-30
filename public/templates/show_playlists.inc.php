<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var list<int> $object_ids */

$is_table          = $browse->is_grid_view();
$show_art          = AmpConfig::get('playlist_art') || $browse->is_mashup();
$show_ratings      = User::is_registered() && (AmpConfig::get('ratings'));
$show_playlist_add = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
$hide_genres       = AmpConfig::get('hide_genres');
//mashup and grid view need different css
$cel_cover = ($is_table) ? "cel_cover" : 'grid_cover'; ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class(); ?>" data-objecttype="playlist">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <?php if ($show_art) { ?>
            <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art'); ?></th>
            <?php } ?>
            <th class="cel_playlist essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=name', T_('Playlist Name'), 'playlist_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_last_update optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=last_update', T_('Last Update'), 'playlist_sort_last_update'); ?></th>
            <th class="cel_type optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=type', T_('Type'), 'playlist_sort_type'); ?></th>
            <th class="cel_medias optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=last_count', /* HINT: Number of items in a playlist */ T_('# Items'), 'playlist_sort_limit'); ?></th>
            <?php if ($show_ratings) { ?>
            <th class="cel_ratings optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=rating', T_('Rating'), 'playlist_sort_rating'); ?></th>
            <?php } ?>
            <th class="cel_owner essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=username', T_('Owner'), 'playlist_sort_username'); ?></th>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php global $dic;
$talFactory = $dic->get(TalFactoryInterface::class);
$guiFactory = $dic->get(GuiFactoryInterface::class);
$gatekeeper = $dic->get(GatekeeperFactoryInterface::class)->createGuiGatekeeper();
$user_id    = (!empty(Core::get_global('user'))) ? Core::get_global('user')->id : 0;
foreach ($object_ids as $playlist_id) {
    $libitem = new Playlist($playlist_id);
    if ($libitem->isNew() || (!$libitem->has_access() and $libitem->type === 'private')) {
        continue;
    }
    $libitem->format();

    // Don't show empty playlist if not admin or the owner
    if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) || $libitem->get_user_owner() == $user_id || $libitem->get_media_count() > 0) { ?>
        <tr id="playlist_row_<?php echo $libitem->id; ?>">
            <?php $content = $talFactory->createTalView()
        ->setContext('USING_RATINGS', User::is_registered() && (AmpConfig::get('ratings')))
        ->setContext('PLAYLIST', $guiFactory->createPlaylistViewAdapter($gatekeeper, $libitem))
        ->setContext('CONFIG', $guiFactory->createConfigViewAdapter())
        ->setContext('IS_SHOW_ART', $show_art)
        ->setContext('IS_SHOW_PLAYLIST_ADD', $show_playlist_add)
        ->setContext('CLASS_COVER', $cel_cover)
        ->setTemplate('playlist_row.xhtml')
        ->render();

        echo $content; ?>
        </tr>
        <?php
    }
} ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="10"><span class="nodata"><?php echo T_('No playlist found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play essential"></th>
            <?php if ($show_art) { ?>
            <th class="<?php echo $cel_cover; ?>"></th>
            <?php } ?>
            <th class="cel_playlist"></th>
            <th class="cel_add"></th>
            <th class="cel_last_update"></th>
            <th class="cel_type"></th>
            <th class="cel_medias"><?php /* HINT: Number of items in a playlist */ echo T_('# Items'); ?></th>
            <?php if ($show_ratings) { ?>
            <th class="cel_ratings"></th>
            <?php } ?>
            <th class="cel_owner"></th>
            <th class="cel_action"></th>
        </tr>
    </tfoot>
</table>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
