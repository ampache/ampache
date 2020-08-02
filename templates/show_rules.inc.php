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

if ($playlist) {
    $logic_operator = $playlist->logic_operator;
} else {
    $logic_operator = Core::get_request('operator');
}
$logic_operator = strtolower($logic_operator); ?>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/search.js"></script>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/search-data.php?type=<?php echo (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) ? scrub_out((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) : 'song'; ?>"></script>

<?php UI::show_box_top(T_('Rules') . "...", 'box box_rules'); ?>
<table class="tabledata">
<tbody id="searchtable">
    <tr id="rules_operator">
    <td><?php echo T_('Match'); ?></td>
        <td>
                <select name="operator">
                        <option value="and" <?php if ($logic_operator == 'and') {
    echo 'selected="selected"';
}?>><?php echo T_('all rules'); ?></option>
                        <option value="or"  <?php if ($logic_operator == 'or') {
    echo 'selected="selected"';
}?>><?php echo T_('any rule'); ?></option>
                </select>
        </td>
        </tr>
    <tr id="rules_addrowbutton">
    <td>
        <a id="addrowbutton" href="javascript:void(0)">
            <?php echo UI::get_icon('add'); ?>
        <?php echo T_('Add Another Rule'); ?>
        </a>
        <script>$('#addrowbutton').on('click', SearchRow.add);</script>
    </td>
    </tr>
</tbody>
</table>
<?php UI::show_box_bottom(); ?>

<?php
if ($playlist) {
    $out = $playlist->to_js();
} else {
    $mysearch = new Search(null, (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
    $mysearch->parse_rules(Search::clean_request($_REQUEST));
    $out = $mysearch->to_js();
}
if ($out) {
    echo $out;
} else {
    echo '<script>SearchRow.add();</script>';
} ?>
