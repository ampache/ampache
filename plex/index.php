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

define('NO_SESSION','1');
require_once '../lib/init.php';

if (!AmpConfig::get('plex_backend')) {
    echo "Disabled.";
    exit;
}

$action = strtolower($_GET['action']);

$headers = apache_request_headers();
$client = $headers['User-Agent'];
$clientPlatform = $headers['X-Plex-Client-Platform'];
$version = $headers['X-Plex-Version'];
$language = $headers['X-Plex-Language'];
$clientFeatures = $headers['X-Plex-Client-Capabilities'];

// User probably get here with a browser, we show specific content
if (empty($version) && empty($action)) {
?>
<html>
<head>
<title>Ampache/Plex Configuration</title>
<style>
body {
    background-color: #000000;
}

#main {
    text-align: center;
    width: 100%;
}

#content {
    margin-top: 10%;
    display: inline-block;
    text-align: center;
}

.info {
    font-weight: bold;
    color: #8d8d8d;
}

.error {
    font-weight: bold;
    color: #801010;
}

.configform {
    display: inline-block;
    text-align: center;
   
    border-color: #777777;
    width: auto;
    margin-top: 20px;
}

.field {
    margin: 20px;
    color: #FFFFFF;
}

.field_label {
    display: inline-block;
    width: 200px;
    margin-right: 15px;
    text-align: left;
}

.field_value {
    display: inline-block;
}

.formbuttons {
    text-align: right;
    margin-right: 20px;
}
</style>
</head>
<body>
<div id="main">
<div id="content">
<img src="/images/plex-icon-256.png" />
<?php
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $authadmin = false;
    if(!empty($username) && !empty($password)) {
        $auth = Auth::login($username, $password);
        if ($auth['success']) {
            $GLOBALS['user'] = User::get_from_username($username);
            if (Access::check('interface', '100')) {
                $authadmin = true;
                
                $plexact = $_POST['plexact'];
                if (empty($plexact)) {
?>
<p class="info">Configure your Plex server settings bellow.</p>
<div class="configform">
    <form action="/" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="plexact" value="save" />
        <input type="hidden" name="username" value="<?php echo $username; ?>" />
        <input type="hidden" name="password" value="<?php echo $password; ?>" />
        <div class="field">
            <div class="field_label">myPlex Username (optional):</div>
            <div class="field_value"><input type="text" name="myplex_username" /></div>
        </div>
        <div class="field">
            <div class="field_label">myPlex Password (optional):</div>
            <div class="field_value"><input type="password" name="myplex_password" /></div>
        </div>
        <div class="field">
            <div class="field_label">Server Name:</div>
            <div class="field_value"><input type="text" name="plex_servername" /></div>
        </div>
        <div class="field">
            <div class="field_label">Server UUID:</div>
            <div class="field_value"><input type="text" name="plex_uuid" /></div>
        </div>
        <div class="formbuttons">
            <input type="submit" value="Save" />
        </div>
    </form>
</div>
<?php            
                } elseif ($plexact == "save") {
                    $myplex_username = $_POST['myplex_username'];
                    $myplex_password = $_POST['myplex_password'];
                    $plex_servername = $_POST['plex_servername'];
                    $plex_uuid = $_POST['plex_uuid'];
                    
                    if (!empty($myplex_username)) {
                        // Register the server on myPlex and get auth token
                        $authtoken = Plex_Api::validateMyPlex($myplex_username, $myplex_password, $plex_uuid);
                        echo "Authentication token: " . $authtoken . "<br />\r\n";
                        Plex_Api::registerMyPlex($plex_uuid, $authtoken);
                    }
                }
            }
        }
    }
    
    if (!$authadmin) {
?>
<p class="error">Ampache authentication required.</p>
<div class="configform">
    <form action="/" method="POST" enctype="multipart/form-data">
        <div class="field">
            <div class="field_label">Username:</div>
            <div class="field_value"><input type="text" name="username" /></div>
        </div>
        <div class="field">
            <div class="field_label">Password:</div>
            <div class="field_value"><input type="password" name="password" /></div>
        </div>
        <div class="formbuttons">
            <input type="submit" value="Log-on" />
        </div>
    </form>
</div>
<?php    
    }
?>
</div>
</div>
</body>
</html>
<?php
    
    exit;
}

// Get the list of possible methods for the Plex API
$methods = get_class_methods('plex_api');
// Define list of internal functions that should be skipped
$internal_functions = array('setHeader', 'root', 'apiOutput', 'createError', 'validateMyPlex');

$params = array_filter(explode('/', $action), 'strlen');
if (count($params) > 0) {
    // Recurse through them and see if we're calling one of them
    for ($i = count($params); $i > 0; $i--) {
        $act = implode('_', array_slice($params, 0, $i));
        foreach ($methods as $method) {
            if (in_array($method, $internal_functions)) { continue; }

            // If the method is the same as the action being called
            // Then let's call this function!
            if ($act == $method) {
                Plex_Api::setHeader('xml');
                call_user_func(array('plex_api', $method), array_slice($params, $i, count($params) - $i));
                // We only allow a single function to be called, and we assume it's cleaned up!
                exit();
            }

        } // end foreach methods in API
    }
} else {
    Plex_Api::setHeader('xml');
    Plex_Api::root();
    exit();
}


Plex_Api::createError(404);
