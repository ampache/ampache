<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>

<script type="text/javascript" src="<?php echo Config::get('web_path'); ?>/lib/javascript/search.js"></script>
<script type="text/javascript" src="<?php echo Config::get('web_path'); ?>/lib/javascript/search-data.php?type=<?php echo $_REQUEST['type'] ? scrub_out($_REQUEST['type']) : 'song'; ?>"></script>

<?php show_box_top(_('Rules') . "..."); ?>
<table class="tabledata" cellpadding="3" cellspacing="0">
<tbody id="searchtable">
	<tr>
	<td><?php echo _('Match'); ?></td>
        <td>
                <select name="operator">
                        <option value="and" <?php if($_REQUEST['operator']=="and") echo "selected=\"selected\""?>><?php echo _('all rules'); ?></option>
                        <option value="or"  <?php if($_REQUEST['operator']=="or") echo "selected=\"selected\""?>><?php echo _('any rule'); ?></option>
                </select>
        </td>
        </tr>
	<tr>
	<td>
		<a id="addrowbutton" href="javascript:void(0)">
			<?php echo get_user_icon('add'); ?>
		<?php echo _('Add Another Rule'); ?>
		</a>
		<script type="text/javascript">Event.observe('addrowbutton', 'click', SearchRow.add);</script>
	</td>
	</tr>
</tbody>
</table>
<?php show_box_bottom(); ?>

<?php
if ($playlist) {
	$out = $playlist->to_js();
}
else {
	$mysearch = new Search($_REQUEST['type']);
	$mysearch->parse_rules(Search::clean_request($_REQUEST));
	$out = $mysearch->to_js();
}
if ($out) {
	echo $out;
}
else {
	echo '<script type="text/javascript">SearchRow.add();</script>';
}
?>
