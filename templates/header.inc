<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

$htmllang = str_replace("_","-",conf('lang'));

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="<?php echo $htmllang; ?>">
<head>
<link rel="shortcut icon" href="<?php echo conf('web_path'); ?>/favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo conf('site_charset'); ?>" />
<?php show_template('style'); ?>
<title><?php echo conf('site_title'); ?></title>
</head>
<body>
<script type="text/javascript" language="javascript">
<!-- Begin
function disableField(element) {
        var element_id = document.getElementById(element);
        element_id.disabled=true;
        element_id.value='';
        element_id.checked=false;
}
function enableField(element) {
        var element_id = document.getElementById(element);
        element_id.disabled=false;
        
}
function flipField(element) {
        var element_id = document.getElementById(element);
        if (element_id.disabled == false) {
                element_id.disabled=true;
        }
        else {
                element_id.disabled=false;
        }
}
  var checkflag_song = "false";

  function check_songs() {
        if (checkflag_song == "false") {
                if (document.forms.songs.elements["song[]"].length == undefined) {
                        document.forms.songs.elements["song[]"].checked = true;
                }
                else {
                        for (i = 0; i < document.forms.songs.elements["song[]"].length; i++) {
                                document.forms.songs.elements["song[]"][i].checked = true;
                        }
                }
                checkflag_song = "true";
                return "Unselect All";
        }
        else {
                if (document.forms.songs.elements["song[]"].length == undefined) {
                        document.forms.songs.elements["song[]"].checked = false;
                }
                else {
                        for (i = 0; i < document.forms.songs.elements["song[]"].length; i++) {
                                document.forms.songs.elements["song[]"][i].checked = false;
                        }
                }
                checkflag_song = "false";
                return "Select All";
        }
  }

  var checkflag_results = "false";

  function check_results() {
        if (checkflag_results == "false") {
                if (document.results.elements["results[]"].length == undefined) {
                        document.results.elements["results[]"].checked = true;
                }
                else {
                        for (i = 0; i < document.results.elements["results[]"].length; i++) {
                                document.results.elements["results[]"][i].checked = true;
                        }
                }
                checkflag_results = "true";
                return "Unselect All";
        }
        else {
                if (document.results.elements["results[]"].length == undefined) {
                        document.results.elements["results[]"].checked = false;
                }
                else {
                        for (i = 0; i < document.results.elements["results[]"].length; i++) {
                                document.results.elements["results[]"][i].checked = false;
                        }
                }
                checkflag_results = "false";
                return "Select All";
        }
  }
// End -->
</script>

<div id="pageheader">
<a href="http://www.ampache.org">
        <img class="pageheader" src="<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache.gif" border="0" title="Ampache: For the love of music" alt="Ampache: For the love of music" />
</a>
</div>
<?php
        if( isset( $_REQUEST['amp_error'] ) ) {
                $msg = scrub_in( $_REQUEST['amp_error'] );
                echo( "<font class=\"error\">Error:\t$msg</font><br><br>" );
		if (conf('debug')) { log_event($_SESSION['userdata']['username'],' general_error ',"Error: $msg"); }
        }
show_clear();
?>
