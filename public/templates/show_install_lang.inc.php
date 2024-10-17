<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\System\AmpError;

/** @var string $web_path */
/** @var string $htmllang */

require __DIR__ . '/install_header.inc.php'; ?>
        <!-- Main jumbotron for a primary marketing message or call to action -->
        <br><br>
        <div class="page-header">
            <h1><?php echo T_('Choose Installation Language'); ?></h1>
        </div>
        <p><?php echo AmpError::display('general'); ?></p>
        <form role="form" method="post" action="<?php echo $web_path . "/install.php?action=check"; ?>" enctype="multipart/form-data" >
            <div class="form-group">
        <?php $languages = get_languages();
$var_name                = $htmllang . "_lang";

echo "<select class=\"form-control\" name=\"htmllang\">\n";

foreach ($languages as $lang => $name) {
    $lang_name = $lang . "_lang";
    $selected  = ($var_name == $lang_name) ? "selected=\"selected\"" : '';

    echo "\t<option value=\"$lang\" " . $selected . ">$name</option>\n";
}
echo "</select>\n"; ?>
            </div>
            <button type="submit" class="btn btn-warning"><?php echo T_('Start Configuration'); ?></button>
        </form>
<?php require __DIR__ . '/install_footer.inc.php'; ?>
