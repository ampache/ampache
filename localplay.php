<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*!
        @header Song Document
        @discussion Actually play files from albums, artists or just given
        a bunch of id's.
        Special thanx goes to Mike Payson and Jon Disnard for the means
        to do this.
*/

require('modules/init.php');
/* If we are running a demo, quick while you still can! */
if (conf('demo_mode')) {
        exit();
}

$web_path = conf('web_path');

if($user->prefs['play_type'] != 'local_play') {
    show_template('header');
    echo "<span align=\"center\" class=\"fatalerror\">Localplay Currently Disabled</span>";
    show_footer();
    exit;
}

switch($_REQUEST['submit'])
{
    case ' X ':
        $action = "stop";
        break;
    case ' > ':
        $action = "play";
        break;
    case ' = ': 
        $action = "pause";
        break;
    case '|< ':
        $action = "prev";
        break;
    case ' >|':
        $action = "next";
        break;
    case (substr_count($_REQUEST['submit'],"+") == '1'):
    	$amount = trim(substr($_REQUEST['submit'],2,strlen($_REQUEST['submit']-2)));
	$action = "volplus";
	break;
    case (substr_count($_REQUEST['submit'],"-") == '1'):
    	$amount = trim(substr($_REQUEST['submit'],2,strlen($_REQUEST['submit']-2)));
	$action = "volminus";
	break; 
    case 'clear':
        $action = "clear";
        break;
    case 'start':
        $action = "start";
        break;
    case 'kill':
        $action = "kill";
        break;
    default:
        echo _("Unknown action requested") . ": '$_REQUEST[submit]'<br />";
        exit;
}
$systr = conf('localplay_'.$action);
$systr = str_replace("%AMOUNT%",$amount,$systr);
if (conf('debug')) { log_event($user->username,'localplay',"Exec: $systr"); }
@exec($systr, $output);
$web_path = conf('web_path');
if($output)
    print_r($output);
else
    header("Location: $web_path");

?>
