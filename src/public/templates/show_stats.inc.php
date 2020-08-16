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

$stats    = Catalog::get_stats();
$catalogs = Catalog::get_catalogs(); ?>
<?php UI::show_box_top(T_('Statistics'), 'box box_stats'); ?>
<em><?php echo T_('Catalogs'); ?></em>
<table class="tabledata">
    <thead>
        <tr class="th-top">
            <th><?php echo T_('Connected Users'); ?></th>
            <th><?php echo T_('Total Users'); ?></th>
            <th><?php echo T_('Albums'); ?></th>
            <th><?php echo T_('Artists'); ?></th>
            <th><?php echo T_('Songs'); ?></th>
            <?php if (Video::get_item_count('Video')) { ?>
                <th><?php echo T_('Videos'); ?></th>
            <?php
} ?>
            <?php if (AmpConfig::get('podcast')) { ?>
                <th><?php echo T_('Podcasts'); ?></th>
                <th><?php echo T_('Podcast Episodes'); ?></th>
            <?php
    } ?>
            <th><?php echo T_('Tags'); ?></th>
            <th><?php echo T_('Catalog Size'); ?></th>
            <th><?php echo T_('Catalog Time'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php echo $stats['connected']; ?></td>
            <td><?php echo $stats['user'] ?></td>
            <td><?php echo $stats['album']; ?></td>
            <td><?php echo $stats['artist']; ?></td>
            <td><?php echo $stats['song']; ?></td>
            <?php if (Video::get_item_count('Video')) { ?>
                <td><?php echo $stats['video']; ?></td>
            <?php
    } ?>
            <?php if (AmpConfig::get('podcast')) { ?>
                <td><?php echo $stats['podcast']; ?></td>
                <td><?php echo $stats['podcast_episode']; ?></td>
            <?php
    } ?>
            <td><?php echo $stats['tags']; ?></td>
            <td><?php echo $stats['formatted_size']; ?></td>
            <td><?php echo $stats['time_text']; ?></td>
        </tr>
    </tbody>
</table>
<hr />
<table class="tabledata">
    <colgroup>
      <col id="col_catalog" />
      <col id="col_path" />
      <col id="col_lastverify" />
      <col id="col_lastadd" />
      <col id="col_lastclean" />
      <col id="cel_items" />
      <col id="col_total" />
    </colgroup>
    <thead>
        <tr class="th-top">
            <th class="cel_catalog"><?php echo T_('Name'); ?></th>
            <th class="cel_path"><?php echo T_('Path'); ?></th>
            <th class="cel_lastverify"><?php echo T_('Last Verify'); ?></th>
            <th class="cel_lastadd"><?php echo T_('Last Add'); ?></th>
            <th class="cel_lastclean"><?php echo T_('Last Clean'); ?></th>
            <th class="cel_items"><?php echo T_('Item Count'); ?></th>
            <th class="cel_total"><?php echo T_('Catalog Size'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($catalogs as $catalog_id) {
        $catalog = Catalog::create_from_id($catalog_id);
        $catalog->format();
        $stats = Catalog::get_stats($catalog_id); ?>
    <tr>
        <td class="cel_catalog"><?php echo $catalog->name; ?></td>
        <td class="cel_path"><?php echo scrub_out($catalog->f_full_info); ?></td>
        <td class="cel_lastverify"><?php echo scrub_out($catalog->f_update); ?></td>
        <td class="cel_lastadd"><?php echo scrub_out($catalog->f_add); ?></td>
        <td class="cel_lastclean"><?php echo scrub_out($catalog->f_clean); ?></td>
        <td class="cel_items"><?php echo scrub_out($stats['items']); ?></td>
        <td class="cel_total"><?php echo scrub_out($stats['formatted_size']); ?></td>
    </tr>
<?php
    } ?>
    </tbody>
</table>
<?php UI::show_box_bottom(); ?>

<?php
if (AmpConfig::get('statistical_graphs') && is_dir(AmpConfig::get('prefix') . '/lib/vendor/szymach/c-pchart/src/Chart/')) {
        Graph::display_from_request();
    }
?>
