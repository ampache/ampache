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

$boxtitle = T_('Statistical Graphs');
if ($blink) {
    $boxtitle .= ' - ' . $blink;
} ?>
<?php UI::show_box_top($boxtitle, 'box box_graph'); ?>
<div class="stats_graph">
    <?php
    foreach ($gtypes as $gtype) {
        $graph_link = AmpConfig::get('web_path') . "/graph.php?type=" . $gtype . "&start_date=" . $start_date . "&end_date=" . $end_date . "&zoom=" . $zoom . "&user_id=" . $user_id . "&object_type=" . $object_type . "&object_id=" . $object_id; ?>
    <a href="<?php echo $graph_link; ?>&width=1400&height=690" target="_blank" title="<?php echo T_('Show large'); ?>"><img src="<?php echo $graph_link; ?>" /></a>
        <br /><br />
    <?php
    } ?>
</div>

<?php
if (AmpConfig::get('geolocation')) { ?>
    <div class="stats_graph">
    <?php
        $graph = new Graph();
        $graph->display_map($user_id, $object_type, $object_id, $start_date, $end_date, $zoom); ?>
    </div>
<?php
    } ?>

<form action='<?php echo get_current_path(); ?>' method='post' enctype='multipart/form-data'>
    <dl class="media_details">
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Start Date'); ?></dt>
        <dd class="<?php echo $rowparity; ?>"><input type="text" name="start_date" id="start_date" value="<?php echo $f_start_date; ?>" /></dd>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('End Date'); ?></dt>
        <dd class="<?php echo $rowparity; ?>"><input type="text" name="end_date" id="end_date" value="<?php echo $f_end_date; ?>" /></dd>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Zoom'); ?></dt>
        <dd class="<?php echo $rowparity; ?>">
            <select name="zoom">
            <?php
                $date_formats = array(
                    'year' => T_('Year'),
                    'month' => T_('Month'),
                    'day' => T_('Day'),
                    'hour' => T_('Hour')
                );
                foreach ($date_formats as $dtype => $dname) {
                    echo "<option value='" . $dtype . "' ";
                    if ($dtype == $zoom) {
                        echo "selected";
                    }
                    echo ">" . $dname . "</option>";
                } ?>
            </select>
        </dd>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"></dt>
        <dd class="<?php echo $rowparity; ?>">
            <input type="submit" value="<?php echo T_('View'); ?>" />
        </dd>
    </dl>
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
    <input type="hidden" name="object_type" value="<?php echo $object_type; ?>" />
    <input type="hidden" name="object_id" value="<?php echo $object_id; ?>" />
    <input type="hidden" name="action" value="<?php echo filter_input(INPUT_GET, 'action', FILTER_SANITIZE_URL); ?>" />
    <input type="hidden" name="type" value="<?php echo $type; ?>" />
</form>
<script>
    $('#start_date').datetimepicker({
        format: 'Y-m-d H:i',
        theme: 'dark'
    });
    $('#end_date').datetimepicker({
        format:'Y-m-d H:i',
        theme: 'dark'
    });
</script>
<?php UI::show_box_bottom(); ?>
