<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

require $prefix . '/templates/install_header.inc.php';
?>
<?php require_once $prefix . '/templates/show_install_check.inc.php'; ?>

<div class="content">
    <strong><?php echo T_('Choose Installation Language'); ?></strong>
    <p>
    <?php Error::display('general'); ?>
    </p>
<form method="post" action="<?php echo $web_path . "/install.php?action=init"; ?>" enctype="multipart/form-data" >

<?php
$languages = get_languages();
$var_name = $value . "_lang";
${$var_name} = "selected=\"selected\"";

echo "<select name=\"htmllang\">\n";

foreach ($languages as $lang=>$name) {
    $var_name = $lang . "_lang";

    echo "\t<option value=\"$lang\" " . ${$var_name} . ">$name</option>\n";
} // end foreach
echo "</select>\n";
?>

<input type="submit" value="<?php echo T_('Start configuration'); ?>" />

    </form>
     </div>
    <div id="bottom">
        <p><strong>Ampache Installation.</strong><br />
        For the love of Music.</p>
   </div>
</div>

</body>
</html>
