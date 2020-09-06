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

if (!isset($_REQUEST['type']) || (string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) !== 'sources') {
    // We need this stuff
    define('NO_SESSION', 1);
    define('OUTDATED_DATABASE_OK', 1);
}
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

// Get the version and format it
$version = Update::get_version();

if (Core::get_request('action') == 'update') {
    if ((string) filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS) == 'sources') {
        if (!Access::check('interface', 100)) {
            UI::access_denied();

            return false;
        }

        set_time_limit(300);
        AutoUpdate::update_files();
        AutoUpdate::update_dependencies();
        echo '<script>window.location="' . AmpConfig::get('web_path') . '";</script>';

        return false;
    } else {
        Update::run_update();

        // Get the New Version
        $version = Update::get_version();
    }
}
$htmllang = str_replace("_", "-", AmpConfig::get('lang')); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
    <!-- Propelled by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo AmpConfig::get('site_title') . ' - ' . T_('Update'); ?></title>
    <link href="lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    <link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
</head>
<body>
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container" style="height: 70px;">
            <a class="navbar-brand" href="#">
                <img src="<?php echo UI::get_logo_url('dark'); ?>" title="<?php echo T_('Ampache'); ?>" alt="<?php echo T_('Ampache'); ?>">
                <?php echo T_('Ampache') . ' :: ' . T_('For the Love of Music') . ' - ' . T_('Installation'); ?>
            </a>
        </div>
    </div>
    <div class="container" role="main">
        <div class="page-header requirements">
            <h1><?php echo T_('Ampache Update'); ?></h1>
        </div>
        <div class="well">
             <p><?php /* HINT: %1 Displays 3.3.3.5, %2 shows current Ampache version, %3 shows current database version */ printf(T_('This page handles all database updates to Ampache starting with %1$s. Your current version is %2$s with database version %3$s'), '<strong>3.3.3.5</strong>', '<strong>' . AmpConfig::get('version') . '</strong>', '<strong>' . $version . '</strong>'); ?></p>
             <p><?php echo T_('The following updates need to be performed:'); ?></p>
        </div>
        <?php AmpError::display('general'); ?>
        <div class="content">
            <?php Update::display_update(); ?>
        </div>
        <?php if (Update::need_update()) { ?>
            <form method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/update.php?action=update">
                <button type="submit" class="btn btn-warning" name="update"><?php echo T_('Update Now!'); ?></button>
            </form>
        <?php
} ?>
    </div>
</body>
</html>
