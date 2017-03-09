<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$threshold = AmpConfig::get('stats_threshold');
$count     = 6;
?>
<p>
    <input type="button" value="<?php echo T_('Browse Library') ?>" onclick="NavigateTo('<?php echo AmpConfig::get('web_path') ?>/browse.php?action=<?php echo $object_type ?>');" />
    <br /><br /><br />
</p>
<?php
UI::show_box_top(T_('Trending'));
$object_ids = Stats::get_top($object_type, $count, 7);
$browse     = new Browse();
$browse->set_type($object_type);
$browse->set_show_header(false);
$browse->set_grid_view(false, false);
$browse->show_objects($object_ids);
UI::show_box_bottom();

?>
<a href="<?php echo AmpConfig::get('web_path') ?>/stats.php?action=newest#browse_content_<?php echo $object_type ?>"><?php UI::show_box_top(T_('Newest')) ?></a>
<?php
$object_ids = Stats::get_newest($object_type, $count);
$browse     = new Browse();
$browse->set_type($object_type);
$browse->set_show_header(false);
$browse->set_grid_view(false, false);
$browse->show_objects($object_ids);
UI::show_box_bottom();
?>
<a href="<?php echo AmpConfig::get('web_path') ?>/stats.php?action=popular"><?php UI::show_box_top(T_('Popular')) ?></a>
<?php
$object_ids = Stats::get_top($object_type, $count, $threshold);
$browse     = new Browse();
$browse->set_type($object_type);
$browse->set_show_header(false);
$browse->set_grid_view(false, false);
$browse->show_objects($object_ids);
UI::show_box_bottom();
