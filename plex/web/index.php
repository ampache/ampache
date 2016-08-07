<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

require_once 'init.php';
?>
<html>
<head>
<title>Ampache/Plex Configuration</title>
<link rel="stylesheet" href="style.css" />
<script>
function changeUniqid()
{
    if (confirm("<?php echo T_('Changing the server UUID could break clients connectivity. Do you confirm?'); ?>")) {
        document.location='/web/?plexact=change_uniqid';
    }
}
</script>
</head>
<body>
<div id="main">
<div id="maincontainer">
    <img src="/images/plex-icon-256.png" /><br />
<?php

function init_db()
{
    if (!Preference::exists('myplex_username')) {
        Preference::insert('myplex_username', 'myPlex Username', '', '25', 'string', 'internal');
        Preference::insert('myplex_authtoken', 'myPlex Auth Token', '', '25', 'string', 'internal');
        Preference::insert('myplex_published', 'Plex Server is published to myPlex', '0', '25', 'boolean', 'internal');
        Preference::insert('plex_uniqid', 'Plex Server Unique Id', uniqid(), '25', 'string', 'internal');
        Preference::insert('plex_servername', 'Plex Server Name', 'Ampache', '25', 'string', 'internal');
        Preference::insert('plex_public_address', 'Plex Public Address', '', '25', 'string', 'internal');
        Preference::insert('plex_public_port', 'Plex Public Port', '32400', '25', 'string', 'internal');
        Preference::insert('plex_local_auth', 'myPlex authentication required on local network', '0', '25', 'boolean', 'internal');
        Preference::insert('plex_match_email', 'Link myPlex users to Ampache based on e-mail address', '1', '25', 'boolean', 'internal');

        User::rebuild_all_preferences();
    }
}

init_db();
$myplex_username     = Plex_XML_Data::getMyPlexUsername();
$myplex_authtoken    = Plex_XML_Data::getMyPlexAuthToken();
$myplex_published    = Plex_XML_Data::getMyPlexPublished();
$plex_servername     = Plex_XML_Data::getServerName();
$plex_public_address = Plex_XML_Data::getServerPublicAddress();
$plex_public_port    = Plex_XML_Data::getServerPublicPort();
$plex_local_port     = Plex_XML_Data::getServerPort();
$plex_local_auth     = AmpConfig::get('plex_local_auth');
$plex_match_email    = AmpConfig::get('plex_match_email');

$plexact = $_REQUEST['plexact'];
switch ($plexact) {
    case 'auth_myplex':
        $myplex_username  = $_POST['myplex_username'];
        $myplex_password  = $_POST['myplex_password'];
        $plex_public_port = $_POST['plex_public_port'];


        if (!empty($myplex_username)) {
            // Register the server on myPlex and get auth token
            $myplex_authtoken = Plex_Api::validateMyPlex($myplex_username, $myplex_password);
            if (!empty($myplex_authtoken)) {
                echo T_('myPlex authentication completed.') . "<br />\r\n";

                Preference::update('myplex_username', -1, $myplex_username, true, true);
                Preference::update('myplex_authtoken', -1, $myplex_authtoken, true, true);
                Preference::update('plex_public_port', -1, $plex_public_port, true, true);
                AmpConfig::set('plex_public_port', $plex_public_port, true);

                $plex_public_address = Plex_Api::getPublicIp();
                Preference::update('plex_public_address', -1, $plex_public_address, true, true);

                $ret = Plex_Api::registerMyPlex($myplex_authtoken);
                if ($ret['status'] == '201') {
                    Plex_Api::publishDeviceConnection($myplex_authtoken);
                    $myplex_published = true;
                    echo T_('Server registration completed.') . "<br />\r\n";
                } else {
                    $myplex_published = false;
                    echo "<p class='error'>" . T_('Cannot register the server on myPlex.') . "</p>";
                }
                Preference::update('myplex_published', -1, $myplex_published, true, true);
            } else {
                $myplex_authtoken = '';
                $myplex_published = false;
                echo "<p class='error'>" . T_('Cannot authenticate on myPlex.') . "</p>";
            }
        }
    break;

    case 'unauth_myplex':
        Plex_Api::unregisterMyPlex($myplex_authtoken);

        $myplex_username  = '';
        $myplex_authtoken = '';
        $myplex_published = false;
        Preference::update('myplex_username', -1, $myplex_username, true, true);
        Preference::update('myplex_authtoken', -1, $myplex_authtoken, true, true);
        Preference::update('myplex_published', -1, $myplex_published, true, true);
    break;

    case 'save':
        $plex_servername  = $_POST['plex_servername'];
        $plex_local_auth  = $_POST['plex_local_auth'] ?: '0';
        $plex_match_email = $_POST['plex_match_email'] ?: '0';

        Preference::update('plex_servername', -1, $plex_servername, true, true);
        Preference::update('plex_local_auth', -1, $plex_local_auth, true, true);
        Preference::update('plex_match_email', -1, $plex_match_email, true, true);
    break;

    case 'change_uniqid':
        Preference::update('plex_uniqid', -1, uniqid(), true, true);
        echo T_('Server UUID changed.') . "<br />\r\n";
    break;
}
?>
    <p class="info">Configure your Plex server settings bellow.</p>

    <div class="configform">
        <h3>Server Settings</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="plexact" value="save" />
            <div class="field">
                <label for="plex_servername">Server Name:</label>
                <input id="plex_servername" class="field_value" type="text" name="plex_servername" value="<?php echo $plex_servername; ?>" />
            </div>
            <div class="field">
                <label for="plex_local_auth">myPlex authentication required on local network</label>
                <input type="checkbox" id="plex_local_auth" name="plex_local_auth" value="1" <?php if ($plex_local_auth) {
    echo "checked";
} ?>>
            </div>
            <div class="field">
                <label for="plex_match_email">Link myPlex users to Ampache based on e-mail address</label>
                <input type="checkbox" id="plex_match_email" name="plex_match_email" value="1" <?php if ($plex_match_email) {
    echo "checked";
} ?>>
            </div>
            <div class="formbuttons">
                <input type="submit" value="Save" />
            </div>
        </form>
    </div><br />

<?php if (empty($myplex_authtoken)) {
    ?>
    <div class="configform">
        <h3>myPlex authentication / server publish</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="plexact" value="auth_myplex" />
            <div class="field">
                <label for="myplex_username">myPlex Username:</label>
                <input type="text" id="myplex_username" class="field_value" name="myplex_username" value="<?php echo $myplex_username; ?>" />
            </div>
            <div class="field">
                <label for="myplex_password">myPlex Password:</label>
                <input id="myplex_password" type="password" class="field_value" name="myplex_password" />
            </div>
            <div class="field">
                <label for="plex_public_port">Public Server Port (optional):</label>
                <input type="text" id="plex_public_port" class="field_value" name="plex_public_port" value="<?php echo $plex_public_port; ?>" />
            </div>
            <?php if ($plex_local_port != 32400) {
    ?>
            <div style="color: orange;">
                Plex servers should locally listen on port 32400. Current local listing port for your Plex backend is <?php echo $plex_local_port; ?>. Ampache applies a small URI `hack` to work with custom port
                as Plex server, but be aware that this will not work with all clients.
            </div>
            <?php 
} ?>
            <div class="formbuttons">
                <input type="submit" value="Auth/Publish" />
            </div>
        </form>
    </div><br />
<?php 
} else {
    ?>
    <div class="configform">
        <h3>myPlex authentication / server publish</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <label>myPlex user: <b><?php echo $myplex_username; ?></b></label><br />
            <label>Public server address: <b><?php echo $plex_public_address; ?>:<?php echo $plex_public_port; ?></b></label>
            <input type="hidden" name="plexact" value="unauth_myplex" />
            <div class="formbuttons">
                <input type="submit" value="Unregister" />
            </div>
        </form>
    </div><br />
<?php 
} ?>

    <br />
    <div class="configform">
        <h3>Tools</h3><form>
        <div class="formbuttons">
            <input type="button" value="Change Server UUID" onclick="changeUniqid();" />
        </div></form>
    </div><br />
</div>
</div>
</body>
</html>
