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
 ?>

<?php UI::show_box_top($radio->f_name . ' ' . T_('Details'), 'box box_live_stream_details'); ?>
<div class="item_right_info">
    <?php
        $thumb = UI::is_grid_view('live_stream') ? 2 : 11;
        Art::display('live_stream', $radio->id, $radio->f_name, $thumb); ?>
</div>
<dl class="media_details">
<?php $rowparity = UI::flip_class(); ?>
<dt class="<?php echo $rowparity; ?>"><?php echo T_('Action'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $radio->id, 'play', T_('Play'), 'play_live_stream_' . $radio->id); ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $radio->id . '&append=true', 'play_add', T_('Play last'), 'addplay_live_stream_' . $radio->id); ?>
            <?php
        } ?>
        <?php
    } ?>
        <?php echo Ajax::button('?action=basket&type=live_stream&id=' . $radio->id, 'add', T_('Add to temporary playlist'), 'add_live_stream_' . $radio->id); ?>
    </dd>
<?php
    $itemprops[T_('Name')]     = $radio->f_name;
    $itemprops[T_('Website')]  = scrub_out($radio->site_url);
    $itemprops[T_('Stream')]   = $radio->f_url_link;
    $itemprops[T_('Codec')]    = scrub_out($video->codec);

    foreach ($itemprops as $key => $value) {
        if (trim($value)) {
            $rowparity = UI::flip_class();
            echo "<dt class=\"" . $rowparity . "\">" . T_($key) . "</dt><dd class=\"" . $rowparity . "\">" . $value . "</dd>";
        }
    } ?>
</dl>
<?php UI::show_box_bottom(); ?>
