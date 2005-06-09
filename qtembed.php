<?php
/*

 Copyright (c) 2004 Ampache.org
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

/*

 This script generates the HTML needed for 
 embeded QuickTime player.

*/

require('modules/init.php');


/* Just pass on all the parameters */
$web_path = conf('web_path');
$play_url = "$web_path/play/?".$_SERVER["QUERY_STRING"];

?>



<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<title>QT Embedded Player</title>

<style type="text/css">
<!--
	BODY {
	  margin: 0pt;
	  padding: 0pt;
	}
-->
</style>

</head>



<body>

<OBJECT CLASSID="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
	WIDTH="100%"
	HEIGHT="16"
	NAME="movie" 
	CODEBASE="http://www.apple.com/qtactivex/qtplugin.cab">
	<PARAM name="SRC" VALUE="<?=$play_url?>">
	<PARAM name="AUTOPLAY" VALUE="true">
	<PARAM name="CONTROLLER" VALUE="true">
	<PARAM name="QTNEXT1" VALUE="<javascript:top.next_song();>">
	<EMBED
		NAME="movie" 
 		SRC="<?=$play_url?>"
 		WIDTH="100%"
 		HEIGHT="16"
 		AUTOPLAY="true"
 		CONTROLLER="true"
 		QTNEXT1="<javascript:top.next_song();>"
 		PLUGINSPAGE="http://www.apple.com/quicktime/download/"
 	>
 </EMBED>
</OBJECT>

</body>
</html>
