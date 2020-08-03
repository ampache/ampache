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
 */ ?>
<!DOCTYPE html>
<html lang="en-US">

<head>
  <!-- Propelled by Ampache | ampache.org -->
  <?php UI::show_custom_style(); ?>
  <title><?php echo T_('Ampache -- Config Debug Page') ?></title>
  <link href="lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
  <link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
</head>
<body>
        <div class="navbar navbar-inverse" role="navigation">
            <div class="container" style="height: 70px;">
                <a class="navbar-brand" href="#">
                    <img src="./images/ampache-dark.png" title="<?php echo T_('Ampache'); ?>" alt="<?php echo T_('Ampache'); ?>">
                    <?php echo T_('Ampache') . ' :: ' . T_('For the Love of Music'); ?>
                </a>
            </div>
        </div>
  <div class="container" role="main">
    <div class="jumbotron" style="margin-top: 70px">
      <h1><?php echo T_('Ampache Configuration Parse Error'); ?></h1>
      <p><?php /* HINT: ampache config file path */ echo sprintf(T_('You may have reached this page because your %s configuration file was not parsable'), '<strong>/config/ampache.cfg.php</strong>'); ?></p>
      <p><a href="https://github.com/ampache/ampache/wiki/FAQ#im-getting-ampache-configuration-parse-error" rel="nofollow"><?php echo T_('Please visit the wiki for help'); ?></a></p>
    </div>
  </div>
</body>

</html>
