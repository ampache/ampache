<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Repository\Model\Search;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/** @var null|Search $playlist */

$currentType = (isset($currentType))
    ? $currentType
    : Core::get_request('type');
if (isset($playlist)) {
    $logic_operator = $playlist->logic_operator;
} else {
    $logic_operator = Core::get_request('operator');
}
$logic_operator = strtolower($logic_operator); ?>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/search.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/search-data.php?type=<?php echo $currentType ?? 'song'; ?>"></script>

<?php Ui::show_box_top(T_('Rules') . "...", 'box box_rules'); ?>
<table class="tabledata">
<tbody id="searchtable">
    <tr id="rules_operator">
    <td><?php echo T_('Match'); ?></td>
        <td>
                <select name="operator">
                    <option value="and" <?php if ($logic_operator == 'and') {
                        echo 'selected="selected"';
                    } ?>><?php echo T_('all rules'); ?></option>
                    <option value="or" <?php if ($logic_operator == 'or') {
                        echo 'selected="selected"';
                    } ?>><?php echo T_('any rule'); ?></option>
                </select>
        </td>
        </tr>
    <tr id="rules_addrowbutton">
    <td>
        <a id="addrowbutton" href="javascript:void(0)">
            <?php echo Ui::get_icon('add'); ?>
        <?php echo T_('Add Another Rule'); ?>
        </a>
        <script>$('#addrowbutton').on('click', SearchRow.add);</script>
    </td>
    </tr>
</tbody>
</table>
<?php Ui::show_box_bottom(); ?>
<?php if (isset($playlist)) {
    $out = $playlist->to_js();
} else {
    $mysearch = new Search(0, $currentType);
    $mysearch->set_rules($_REQUEST);
    $out = $mysearch->to_js();
}
if ($out) {
    echo $out;
} else {
    echo '<script>SearchRow.add();</script>';
} ?>
