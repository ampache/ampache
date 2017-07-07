<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

require $prefix . '/templates/install_header.inc.php';
?>
<div class="jumbotron">
    <h1><?php echo T_('Install progress'); ?></h1>
    <div class="progress">
        <div class="progress-bar progress-bar-warning"
            role="progressbar"
            aria-valuenow="60"
            aria-valuemin="0"
            aria-valuemax="100"
            style="width: 33%">
            33%
        </div>
    </div>
    <p><strong><?php echo T_('Step 1 - Create the Ampache database'); ?></strong></p>
    <dl>
        <dd><?php echo T_('This step creates and inserts the Ampache database, so please provide a MySQL account with database creation rights. This step may take some time on slower computers.'); ?></dd>
    </dl>
    <ul class="list-unstyled">
        <li><?php echo T_('Step 2 - Create configuration files (ampache.cfg.php ...)'); ?></li>
        <li><?php echo T_('Step 3 - Set up the initial account'); ?></li>
    </ul>
</div>
<?php AmpError::display('general'); ?>
<h2><?php echo T_('Insert Ampache Database'); ?></h2>
<form role="form" class="form-horizontal" method="post" action="<?php echo $web_path . "/install.php?action=create_db&amp;htmllang=$htmllang&amp;charset=$charset"; ?>" enctype="multipart/form-data" autocomplete="off">
    <div class="form-group">
        <label for="local_db" class="col-sm-4 control-label"><?php echo T_('Desired Database Name'); ?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="local_db" name="local_db" value="ampache">
        </div>
    </div>
    <div class="form-group">
        <label for="local_host" class="col-sm-4 control-label"><?php echo T_('MySQL Hostname'); ?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="local_host" name="local_host" value="localhost">
        </div>
    </div>
    <div class="form-group">
        <label for="local_port" class="col-sm-4 control-label"><?php echo T_('MySQL port (optional)'); ?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="local_port" name="local_port"/>
       </div>
   </div>
    <div class="form-group">
        <label for="local_username" class="col-sm-4 control-label"><?php echo T_('MySQL Administrative Username'); ?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="local_username" name="local_username" value="root">
        </div>
    </div>
    <div class="form-group">
        <label for="local_pass" class="col-sm-4 control-label"><?php echo T_('MySQL Administrative Password'); ?></label>
        <div class="col-sm-8">
            <input type="password" class="form-control" id="local_pass" name="local_pass" placeholder="Password">
        </div>
    </div>
    <div class="form-group">
        <label for="create_db" class="col-sm-4 control-label"><?php echo T_('Create Database'); ?></label>
        <div class="col-sm-8">
            <input
                type="checkbox" value="1" checked
                id="create_db" name="create_db"
                onclick='$("#overwrite_db_div").toggle();'
            />
        </div>
    </div>
    <div class="form-group" id="overwrite_db_div">
        <label for="overwrite_db" class="col-sm-4 control-label"><?php echo T_('Overwrite if database already exists'); ?></label>
        <div class="col-sm-8">
            <input
                type="checkbox" value="1"
                id="overwrite_db" name="overwrite_db"
            />
        </div>
    </div>
    <div class="form-group">
        <label for="create_tables" class="col-sm-4 control-label"><?php echo T_('Create Tables'); ?> (<a href="sql/ampache.sql">ampache.sql</a>)</label>
        <div class="col-sm-8">
            <input
                type="checkbox" value="1" checked
                id="create_tables" name="create_tables"
            />
        </div>
    </div>
    <div class="form-group">
        <label for="db_user" class="col-sm-4 control-label"><?php echo T_('Create Database User'); ?></label>
        <div class="col-sm-8">
            <input
                type="checkbox" value="create_db_user" name="db_user"
                id="db_user"
                onclick='$("#specificuser").toggle();$("#specificpass").toggle();'
            />
        </div>
    </div>
    <div class="form-group" style="display: none;" id="specificuser">
        <label for="db_username" class="col-sm-4 control-label"><?php echo T_('Ampache Database Username'); ?></label>
        <div class="col-sm-8">
            <input type="text" class="form-control" id="db_username" name="db_username" value="ampache">
        </div>
    </div>
    <div class="form-group" style="display: none;" id="specificpass">
        <label for="db_password" class="col-sm-4 control-label"><?php echo T_('Ampache Database User Password'); ?></label>
        <div class="col-sm-8">
            <input type="password" class="form-control" id="db_password" name="db_password" placeholder="Password (Required)">
        </div>
    </div>
    <div class="col-sm-4">
        <button type="submit" class="btn btn-warning" name="skip_admin"><?php echo T_('Skip'); ?></button>
    </div>
    <div class="col-sm-8">
        <button type="submit" class="btn btn-warning"><?php echo T_('Insert Database'); ?></button>
    </div>
</form>
<?php require $prefix . '/templates/install_footer.inc.php'; ?>
