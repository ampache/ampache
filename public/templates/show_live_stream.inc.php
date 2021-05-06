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
use Ampache\Repository\Model\Art;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Live_Stream;

/** @var Live_Stream $radio */

$formattedName = scrub_out($radio->getName());
?>

<?php Ui::show_box_top($formattedName . ' ' . T_('Details'), 'box box_live_stream_details'); ?>
<div class="item_right_info">
    <?php
        $thumb = Ui::is_grid_view('live_stream') ? 2 : 11;
        echo Art::display('live_stream', $radio->getId(), $formattedName, $thumb); ?>
</div>
<dl class="media_details">
<dt><?php echo T_('Action'); ?></dt>
    <dd>
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $radio->getId(), 'play', T_('Play'), 'play_live_stream_' . $radio->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $radio->getId() . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_live_stream_' . $radio->id); ?>
                <?php
            } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $radio->getId() . '&append=true', 'play_add', T_('Play last'), 'addplay_live_stream_' . $radio->id); ?>
            <?php
        } ?>
        <?php
    } ?>
        <?php echo Ajax::button('?action=basket&type=live_stream&id=' . $radio->getId(), 'add', T_('Add to Temporary Playlist'), 'add_live_stream_' . $radio->id); ?>
    </dd>
<?php
    $itemprops[T_('Name')]     = $formattedName;
    $itemprops[T_('Website')]  = scrub_out($radio->getSiteUrl());
    $itemprops[T_('Stream')]   = sprintf(
        '<a target="_blank" href="%s">%s</a>',
        $radio->getUrl(),
        $radio->getUrl()
    );
    $itemprops[T_('Codec')]    = scrub_out($radio->getCodec());

    foreach ($itemprops as $key => $value) {
        if (trim($value)) {
            echo "<dt>" . T_($key) . "</dt><dd>" . $value . "</dd>";
        }
    } ?>
</dl>
<?php Ui::show_box_bottom(); ?>
