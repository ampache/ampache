<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_graphs.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Graph;
use Ampache\Module\Util\Ui;

/** @var int $object_id */
/** @var string $object_type */
/** @var int $user_id */
/** @var int $end_date */
/** @var null|string $f_end_date */
/** @var int $start_date */
/** @var null|string $f_start_date */
/** @var string $zoom */
/** @var string[] $gtypes */
/** @var string $blink */

$web_path = AmpConfig::get_web_path();

$boxtitle = T_('Statistical Graphs');
if ($blink) {
    $boxtitle .= ' - ' . $blink;
} ?>
<?php Ui::show_box_top($boxtitle, 'box box_graph'); ?>
<div class="stats_graph">
<?php
// embed as <object> not <img> so the svg keeps its scripting context and its legend stays draggable in place;
foreach ($gtypes as $gtype) {
    $graph_link = $web_path . "/graph.php?type=" . $gtype . "&start_date=" . $start_date . "&end_date=" . $end_date . "&zoom=" . $zoom . "&user_id=" . $user_id . "&object_type=" . $object_type . "&object_id=" . $object_id; ?>
    <object type="image/svg+xml" data="<?php echo $graph_link; ?>" width="700" height="260"></object>
    <br />
    <a href="<?php echo $graph_link; ?>&width=1400&height=690" target="_blank"><?php echo Ui::get_material_symbol('open_in_new', T_('Link')); ?> <?php echo T_('Link'); ?></a>
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
<?php } ?>

<form action='<?php echo get_current_path(); ?>' method='post' enctype='multipart/form-data'>
    <dl class="media_details">
        <dt><?php echo T_('Start Date'); ?></dt>
        <dd><input type="text" name="start_date" id="start_date" value="<?php echo $f_start_date; ?>" /></dd>
        <dt><?php echo T_('End Date'); ?></dt>
        <dd><input type="text" name="end_date" id="end_date" value="<?php echo $f_end_date; ?>" /></dd>
        <dt><?php echo T_('Zoom'); ?></dt>
        <dd>
            <select name="zoom">
            <?php
            $date_formats = [
                'year' => T_('Year'),
                'month' => T_('Month'),
                'day' => T_('Day'),
                'hour' => T_('Hour')
            ];
foreach ($date_formats as $dtype => $dname) {
    echo "<option value='" . $dtype . "' ";
    if ($dtype == $zoom) {
        echo "selected";
    }
    echo ">" . $dname . "</option>";
} ?>
            </select>
        </dd>
        <dt></dt>
        <dd>
            <input type="submit" value="<?php echo T_('View'); ?>" />
        </dd>
    </dl>
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
    <input type="hidden" name="object_type" value="<?php echo $object_type; ?>" />
    <input type="hidden" name="object_id" value="<?php echo $object_id; ?>" />
    <input type="hidden" name="action" value="<?php echo scrub_out(Core::get_request('action')); ?>" />
    <input type="hidden" name="type" value="<?php echo $type ?? ''; ?>" />
    <input type="hidden" name="rendered_zoom" value="<?php echo $zoom; ?>" />
</form>
<script>
    $('#start_date').datetimepicker({
        format: 'Y-m-d H:i',
        theme: 'dark'
    });
    $('#end_date').datetimepicker({
        format: 'Y-m-d H:i',
        theme: 'dark'
    });
</script>
<?php Ui::show_box_bottom(); ?>
