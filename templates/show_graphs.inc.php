<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$boxtitle = T_('Statistical Graphs');
if ($user) {
    $u = new User($user);
    $u->format();
    $boxtitle .= ' - ' . $u->f_link;
}
?>
<?php UI::show_box_top($boxtitle, 'box box_graph'); ?>
<div class="stats_graph">
    <?php
    $types = array('user_hits', 'user_bandwidth');
    if (!$user) {
        $types[] = 'catalog_files';
        $types[] = 'catalog_size';
    }

    foreach ($types as $type) {
    ?>
        <img src="<?php echo AmpConfig::get('web_path'); ?>/graph.php?type=<?php echo $type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&zoom=<?php echo $zoom; ?>&oid=<?php echo $oid; ?>" />
        <br /><br />
    <?php } ?>
</div>
<?php if (AmpConfig::get('geolocation')) { ?>

<?php } ?>
<form action='<?php echo get_current_path(); ?>' method='post' enctype='multipart/form-data'>
    <dl class="media_details">
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Start Date'); ?></dt>
        <dd class="<?php echo $rowparity; ?>"><input type="text" name="start_date" id="start_date" value="<?php echo $start_date; ?>" /></dd>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('End Date'); ?></dt>
        <dd class="<?php echo $rowparity; ?>"><input type="text" name="end_date" id="end_date" value="<?php echo $end_date; ?>" /></dd>
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
                    if ($dtype == $zoom) echo "selected";
                    echo ">" . $dname . "</option>";
                }
            ?>
            </select>
        </dd>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"></dt>
        <dd class="<?php echo $rowparity; ?>">
            <input type="submit" value="<?php echo T_('View'); ?>" />
        </dd>
    </dl>
    <input type="hidden" name="user" value="<?php echo $user; ?>" />
    <input type="hidden" name="action" value="<?php echo $action; ?>" />
    <input type="hidden" name="type" value="<?php echo $type; ?>" />
</form>
<script>
    $('#start_date').datetimepicker({
        format: 'unixtime'
    });
    $('#end_date').datetimepicker({
        format: 'unixtime'
    });
</script>
<?php UI::show_box_bottom(); ?>
