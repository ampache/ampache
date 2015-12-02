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
?>
<?php UI::show_box_top(T_('Options'),'info-box'); ?>
<div id="information_actions">
<ul>
<li>
    <?php echo Ajax::button('?action=basket&type=browse_set&browse_id=' . $browse->id,'add', T_('Add Search Results'),'add_search_results'); ?>
    <?php echo T_('Add Search Results'); ?>
</li>
    <?php if (Access::check_function('batch_download') && check_can_zip($_REQUEST['type'])) {
    ?>
<li>
    <a rel="nohtml" href="<?php echo AmpConfig::get('web_path');
    ?>/batch.php?action=browse&amp;type=<?php echo scrub_out($_REQUEST['type']);
    ?>&amp;browse_id=<?php echo $browse->id;
    ?>"><?php echo UI::get_icon('batch_download', T_('Batch Download'));
    ?></a>
    <?php echo T_('Batch Download');
    ?>
</li>
    <?php 
} ?>
</ul>
</div>
<?php UI::show_box_bottom(); ?>
