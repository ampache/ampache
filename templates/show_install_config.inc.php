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

// Try to guess the web path
$web_path_guess = $_REQUEST['web_path'];
if (empty($web_path_guess)) {
    $web_path_guess = get_web_path();
}
$db_user        = scrub_out($_REQUEST['db_user']);
$local_username = scrub_out($_REQUEST['db_username']);
if (!$db_user) {
    $local_username = scrub_out($_REQUEST['local_username']);
}
$local_pass = scrub_out($_REQUEST['db_password']);
if (empty($local_pass)) {
    $local_pass = scrub_out($_REQUEST['local_pass']);
}

require $prefix . '/templates/install_header.inc.php'; ?>
        <div class="jumbotron" style="margin-top: 70px">
            <h1><?php echo T_('Install Progress'); ?></h1>
            <div class="progress">
                <div class="progress-bar progress-bar-warning"
                    role="progressbar"
                    aria-valuenow="60"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    style="width: 66%">
                    66%
                </div>
            </div>
            <p><?php echo T_('Step 1 - Create the Ampache database'); ?></p>
                <p><strong><?php echo T_('Step 2 - Create configuration files (ampache.cfg.php ...)'); ?></strong></p>
                <dl>
                    <dd><?php printf(T_('This step takes the basic config values and generates the config file. If your config/ directory is writable, you can select "write" to have Ampache write the config file directly to the correct location. If you select "download" it will prompt you to download the config file, and you can then manually place the config file in %s.'), $prefix); ?></dd>
                </dl>
            <ul class="list-unstyled">
                <li><?php echo T_('Step 3 - Set up the initial account'); ?></li>
            </ul>
            </div>
            <?php AmpError::display('general'); ?>
<form method="post" action="<?php echo $web_path . "/install.php?action=create_config"; ?>" enctype="multipart/form-data" autocomplete="off">
            <h2><?php echo T_('Generate Configuration File'); ?></h2>
            <h3><?php echo T_('Various'); ?></h3>
<div class="form-group">
    <label for="web_path" class="col-sm-4 control-label"><?php echo T_('Web Path'); ?></label>
    <div class="col-sm-8">
        <input type="text" class="form-control" id="web_path" name="web_path" value="<?php echo scrub_out($web_path_guess); ?>">
    </div>
</div>
            <h3><?php echo T_('Database Connection'); ?></h3>
            <?php AmpError::display('config'); ?>
<div class="form-group">
    <label for="local_db" class="col-sm-4 control-label"><?php echo T_('Database Name'); ?></label>
    <div class="col-sm-8">
        <input type="text" class="form-control" id="local_db" name="local_db" value="<?php echo scrub_out($_REQUEST['local_db']); ?>">
    </div>
</div>
<div class="form-group">
    <label for="local_host" class="col-sm-4 control-label"><?php echo T_('MySQL Hostname'); ?></label>
    <div class="col-sm-8">
        <input type="text" class="form-control" id="local_host" name="local_host" value="<?php echo scrub_out($_REQUEST['local_host']); ?>">
    </div>
</div>
<div class="form-group">
    <label for="local_port" class="col-sm-4 control-label"><?php echo T_('MySQL Port (optional)'); ?></label>
    <div class="col-sm-8">
        <input type="text" class="form-control" id="local_port" name="local_port" value="<?php echo scrub_out($_REQUEST['local_port']);?>"/>
    </div>
</div>
<div class="form-group">
    <label for="local_username" class="col-sm-4 control-label"><?php echo T_('MySQL Username'); ?></label>
    <div class="col-sm-8">
        <input type="text" class="form-control" id="local_username" name="local_username" value="<?php echo $local_username; ?>"/>
    </div>
</div>
<div class="form-group">
    <label for="local_pass" class="col-sm-4 control-label"><?php echo T_('MySQL Password'); ?></label>
    <div class="col-sm-8">
        <input type="password" class="form-control" id="local_pass" name="local_pass" value="<?php echo $local_pass; ?>" placeholder="<?php echo T_("Password") . '(' . T_("required") . ')'; ?>">
    </div>
</div>

<input type="hidden" name="htmllang" value="<?php echo $htmllang; ?>" />
<input type="hidden" name="charset" value="<?php echo $charset; ?>" />

<p>&nbsp;</p>
<h3><?php echo T_('Installation Type'); ?></h3>
<div><?php echo T_('Optimize Ampache for your use case. The installer will enable/disable features automatically.'); ?></div>
<br />
<div class="form-group">
    <div class="radio">
      <label><input type="radio" name="usecase" value="default" <?php if (!isset($_REQUEST['usecase']) || $_REQUEST['usecase'] == 'default') {
    echo 'checked';
} ?>><?php echo T_('Default'); ?> &mdash; <?php echo T_('Ampache is configured for personal use with the best features.'); ?></label>
    </div>
    <div class="radio">
      <label><input type="radio" name="usecase" value="minimalist" <?php if (isset($_REQUEST['usecase']) && $_REQUEST['usecase'] == 'minimalist') {
    echo 'checked';
} ?>><?php echo T_('Minimalist'); ?> &mdash; <?php echo T_('Only essential features are enabled to simply stream your music from the Web Interface.'); ?></label>
    </div>
    <div class="radio">
      <label><input type="radio" name="usecase" value="community" <?php if (isset($_REQUEST['usecase']) && $_REQUEST['usecase'] == 'community') {
    echo 'checked';
} ?>><?php echo T_('Community'); ?> &mdash; <?php echo T_('Recommended settings when using Ampache as a frontend for a music community.'); ?></label>
    </div>
</div>

<p>&nbsp;</p>
<h3><?php echo T_('Allow Transcoding'); ?></h3>
<div>
    <?php echo T_('Transcoding allows you to convert music from one encoding to another. Ampache supports on the fly transcoding of any file type based on user, player, IP address or available bandwidth. In order to transcode, Ampache takes advantage of existing binary applications such as ffmpeg. In order for transcoding to work you must first install the supporting applications and ensure that they are executable by the web server.'); ?>
    <br />
    <?php echo T_('This section applies the default transcoding configuration according to the application you want to use. You may need to customize settings once setup has ended.'); ?> <a href="https://github.com/ampache/ampache/wiki/Transcoding" target="_blank"><?php echo T_('See wiki page'); ?>.</a>
</div>
<br />
<div class="form-group">
    <label for="transcode_template" class="col-sm-4 control-label"><?php echo T_('Template Configuration'); ?></label>
    <div class="col-sm-8">
        <select class="form-control" id="transcode_template" name="transcode_template">
        <option value=""><?php echo T_('None'); ?></option>
        <?php
            $modes = install_get_transcode_modes();
            foreach ($modes as $mode) { ?>
            <option value="<?php echo $mode; ?>" <?php if (Core::get_request('transcode_template') == $mode) {
                echo 'selected';
            } ?>><?php echo $mode; ?></option>
        <?php
            } ?>
        </select>
        <?php
        if (count($modes) == 0) { ?>
        <label><?php echo T_('No default transcoding application found. You may need to install a popular application (FFmpeg, avconv ...) or customize transcoding settings manually after installation.'); ?></label>
        <?php
        } ?>
    </div>
</div>

<p>&nbsp;</p>
<h3><?php echo T_('Players'); ?></h3>
<div><?php echo T_('Ampache is more than only a web interface. Several backends are implemented to ensure you can stream your media to any player.'); ?></div>
<div><?php echo T_('Select each backend to enable. Depending on the backend, you may need to perform additional configuration.'); ?> <a href="https://github.com/ampache/ampache/wiki/API" target="_blank"><?php echo T_('See wiki page'); ?>.</a></div>
<br />
<div class="form-group">
    <div class="checkbox-inline disabled">
        <label><input type="checkbox" value="1" checked disabled>Web Interface</label>
    </div>
    <div class="checkbox-inline disabled">
        <label><input type="checkbox" value="1" checked disabled>Ampache API</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends[]" value="subsonic" <?php if (!isset($_REQUEST['backends']) || in_array('subsonic', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>Subsonic</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends[]" value="upnp" <?php if (isset($_REQUEST['backends']) && in_array('upnp', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>UPnP</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends[]" value="daap" <?php if (isset($_REQUEST['backends']) && in_array('daap', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>DAAP (iTunes)</label>
    </div>
    <div class="checkbox-inline">
        <label><input type="checkbox" name="backends[]" value="webdav" <?php if (isset($_REQUEST['backends']) && in_array('webdav', $_REQUEST['backends'])) {
            echo 'checked';
        } ?>>WebDAV</label>
    </div>
</div>

<br /><br />
<div class="panel-group" id="accordion">
    <div id="config_files" class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><a data-toggle="collapse" data-target="#collapseConfigFiles" href="#collapseConfigFiles"><?php echo T_('File Insight'); ?></a></h3>
        </div>
        <div id="collapseConfigFiles" class="panel-collapse collapse <?php if (isset($created_config) && !$created_config) {
            echo "in";
        } ?>">
            <div class="panel-body">
                <?php if (install_check_server_apache()) { ?>
                    <div class="col-sm-4">&nbsp;</div><div class="col-sm-8">&nbsp;</div>
                    <div class="col-sm-4 control-label">
                        <?php echo T_('channel/.htaccess action'); ?>
                    </div>
                    <div class="col-sm-8">
                        <button type="submit" class="btn btn-warning" name="download_htaccess_channel"><?php echo T_('Download'); ?></button>
                        <button type="submit" class="btn btn-warning" name="write_htaccess_channel" <?php if (!check_htaccess_channel_writable()) {
            echo "disabled ";
        } ?>>
                            <?php echo T_('Write'); ?>
                        </button>
                    </div>
                    <div class="col-sm-4 control-label"><?php echo T_('channel/.htaccess exists?'); ?></div>
                    <div class="col-sm-8"><?php echo debug_result(is_readable($htaccess_channel_file)); ?></div>
                    <div class="col-sm-4 control-label"><?php echo T_('channel/.htaccess configured?'); ?></div>
                    <div class="col-sm-8"><?php echo debug_result(install_check_rewrite_rules($htaccess_channel_file, $web_path_guess)); ?></div>

                    <div class="col-sm-4">&nbsp;</div><div class="col-sm-8">&nbsp;</div>
                    <div class="col-sm-4 control-label">
                        <?php echo T_('rest/.htaccess action'); ?>
                    </div>
                    <div class="col-sm-8">
                        <button type="submit" class="btn btn-warning" name="download_htaccess_rest"><?php echo T_('Download'); ?></button>
                        <button type="submit" class="btn btn-warning" name="write_htaccess_rest" <?php if (!check_htaccess_rest_writable()) {
            echo "disabled ";
        } ?>>
                            <?php echo T_('Write'); ?>
                        </button>
                    </div>
                    <div class="col-sm-4 control-label"><?php echo T_('rest/.htaccess exists?'); ?></div>
                    <div class="col-sm-8"><?php echo debug_result(is_readable($htaccess_rest_file)); ?></div>
                    <div class="col-sm-4 control-label"><?php echo T_('rest/.htaccess configured?'); ?></div>
                    <div class="col-sm-8"><?php echo debug_result(install_check_rewrite_rules($htaccess_rest_file, $web_path_guess)); ?></div>

                    <div class="col-sm-4">&nbsp;</div><div class="col-sm-8">&nbsp;</div>
                    <div class="col-sm-4 control-label">
                        <?php echo T_('play/.htaccess action'); ?>
                    </div>
                    <div class="col-sm-8">
                        <button type="submit" class="btn btn-warning" name="download_htaccess_play"><?php echo T_('Download'); ?></button>
                        <button type="submit" class="btn btn-warning" name="write_htaccess_play" <?php if (!check_htaccess_play_writable()) {
            echo "disabled ";
        } ?>>
                            <?php echo T_('Write'); ?>
                        </button>
                    </div>
                    <div class="col-sm-4 control-label"><?php echo T_('play/.htaccess exists?'); ?></div>
                    <div class="col-sm-8"><?php echo debug_result(is_readable($htaccess_play_file)); ?></div>
                    <div class="col-sm-4 control-label"><?php echo T_('play/.htaccess configured?'); ?></div>
                    <div class="col-sm-8"><?php echo debug_result(install_check_rewrite_rules($htaccess_play_file, $web_path_guess)); ?></div>
                <?php
        } ?>

                <div class="col-sm-4">&nbsp;</div><div class="col-sm-8">&nbsp;</div>
                <div class="col-sm-4">
                    <?php echo T_('config/ampache.cfg.php action'); ?>
                </div>
                <div class="col-sm-8">
                    <button type="submit" class="btn btn-warning" name="download"><?php echo T_('Download'); ?></button>
                    <button type="submit" class="btn btn-warning" name="write" <?php if (!check_config_writable()) {
            echo T_('Disabled');
        } ?>>
                        <?php echo T_('Write'); ?>
                    </button>
                </div>
                <div class="col-sm-4 control-label"><?php echo T_('config/ampache.cfg.php exists?'); ?></div>
                <div class="col-sm-8"><?php echo debug_result(is_readable($configfile)); ?></div>
                <div class="col-sm-4 control-label"><?php echo T_('config/ampache.cfg.php configured?'); ?></div>
                <div class="col-sm-8"><?php $results = @parse_ini_file($configfile); echo debug_result(check_config_values($results)); ?></div>
                <div class="col-sm-4">&nbsp;</div><div class="col-sm-8">&nbsp;</div>

                <div class="col-sm-4"></div>
                <?php $check_url = $web_path . "/install.php?action=show_create_config&htmllang=$htmllang&charset=$charset&local_db=" . $_REQUEST['local_db'] . "&local_host=" . $_REQUEST['local_host']; ?>
                <div class="col-sm-8">
                    <a href="<?php echo $check_url; ?>">[<?php echo T_('Recheck Config'); ?>]</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-sm-4">
    <button type="submit" class="btn btn-warning" name="skip_config"><?php echo T_('Skip'); ?></button>
</div>
<div class="col-sm-8">
    <button type="submit" class="btn btn-warning" name="create_all"><?php echo T_('Create Config'); ?></button>
</div>
</form>

<?php require $prefix . '/templates/install_footer.inc.php'; ?>
