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

require $prefix . '/templates/install_header.inc.php'; ?>
<div class="jumbotron" style="margin-top: 70px">
    <h1><?php echo T_('Install Progress'); ?></h1>
    <div class="progress">
        <div class="progress-bar progress-bar-warning"
            role="progressbar"
            aria-valuenow="60"
            aria-valuemin="0"
            aria-valuemax="100"
            style="width: 99%">
            99%
        </div>
    </div>
    <ul class="list-unstyled">
        <li><?php echo T_('Step 1 - Create the Ampache database'); ?></li>
        <li><?php echo T_('Step 2 - Create configuration files (ampache.cfg.php ...)'); ?></li>
    </ul>
    <p><strong><?php echo T_('Step 3 - Set up the initial account'); ?></strong></p>
    <dl>
        <dd><?php echo T_('This step creates your initial Ampache admin account. Once your admin account has been created you will be redirected to the login page.'); ?></dd>
    </dl>
</div>
    <?php AmpError::display('general'); ?>
    <h2><?php echo T_('Create Admin Account'); ?></h2>
    <form method="post" action="<?php echo $web_path . "/install.php?action=create_account&amp;htmllang=$htmllang&amp;charset=$charset"; ?>" enctype="multipart/form-data">

<div class="form-group">
    <label for="local_username" class="col-sm-3 control-label"><?php echo T_('Username'); ?></label>
    <div class="col-sm-9">
        <input type="text" class="form-control" id="local_username" name="local_username" value="admin">
    </div>
</div>
<div class="form-group">
    <label for="local_pass" class="col-sm-3 control-label"><?php echo T_('Password'); ?></label>
    <div class="col-sm-9">
        <input type="password" class="form-control" id="local_pass" name="local_pass" placeholder="<?php echo T_("Password"); ?>">
    </div>
</div>
<div class="form-group">
    <label for="local_pass2" class="col-sm-3 control-label"><?php echo T_('Confirm Password'); ?></label>
    <div class="col-sm-9">
        <input type="password" class="form-control" id="local_pass2" name="local_pass2" placeholder="<?php echo T_("Confirm Password"); ?>">
    </div>
</div>
<div class="col-sm-9">
    <button type="submit" class="btn btn-warning"><?php echo T_('Create Account'); ?></button>
</div>
</form>
<?php require $prefix . '/templates/install_footer.inc.php'; ?>
