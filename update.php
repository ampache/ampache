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

if (!isset($_REQUEST['type']) || $_REQUEST['type'] != 'sources') {
    // We need this stuff
    define('NO_SESSION', 1);
    define('OUTDATED_DATABASE_OK', 1);
}
require_once 'lib/init.php';

// Get the version and format it
$version = Update::get_version();

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'update') {
    if ($_REQUEST['type'] == 'sources') {
        if (!Access::check('interface', '100')) {
            UI::access_denied();
            exit;
        }

        set_time_limit(300);
        AutoUpdate::update_files();
        AutoUpdate::update_dependencies();
        echo '<script language="javascript" type="text/javascript">window.location="' . AmpConfig::get('web_path') . '";</script>';
        exit;
    } else {
        /* Run the Update Mojo Here */
        Update::run_update();

        /* Get the New Version */
        $version = Update::get_version();
    }
}
$htmllang = str_replace("_","-",AmpConfig::get('lang'));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
    <!-- Propulsed by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo AmpConfig::get('site_title'); ?> - Update</title>
    <link href="lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    <link rel="stylesheet" href="templates/install-doped.css" type="text/css" media="screen" />
</head>
<body>
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="themes/reborn/images/ampache.png" title="Ampache" alt="Ampache">
                <?php echo T_('Ampache'); ?> - For the love of Music
            </a>
        </div>
    </div>
    <div class="container" role="main">
        <div class="page-header requirements">
            <h1><?php echo T_('Ampache Update'); ?></h1>
        </div>
        <div class="well">
             <p><?php printf(T_('This page handles all database updates to Ampache starting with <strong>3.3.3.5</strong>. Your current version is <strong>%s</strong> with database version <strong>%s</strong>.'), AmpConfig::get('version'), $version); ?></p>
             <p><?php echo T_('The following updates need to be performed:'); ?></p>
        </div>
        <?php AmpError::display('general'); ?>
        <div class="content">
            <?php Update::display_update(); ?>
        </div>
        <?php if (Update::need_update()) {
    ?>
            <form method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path');
    ?>/update.php?action=update">
                <button type="submit" class="btn btn-warning" name="update"><?php echo T_('Update Now!');
    ?></button>
            </form>
        <?php 
} ?>
    </div>
</body>
</html>
