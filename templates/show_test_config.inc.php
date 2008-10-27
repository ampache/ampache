<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 this program is free software; you can redistribute it and/or
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ampache -- Config Debug Page</title>
<link rel="stylesheet" href="templates/install.css" type="text/css" media="screen" />
<style type="text/css">
body { 
	text-align:left; 
} 
#content { 
	padding-left: 10px; 
} 
</style>
</head>
<body bgcolor="#f0f0f0">
<div id="header">
<h1><?php echo _('Ampache Debug'); ?></h1>
<p>Ampache.cfg.php error detected</p>
</div>
<div id="content">
<h3 style="color:red;">Ampache.cfg.php Parse Error</h3>
<p>You've been redirected to this page because your <strong>/config/ampache.cfg.php</strong> was not parsable. 
If you are upgrading from 3.3.x please see the directions below.</p>

<h3>Migrating from 3.3.x to 3.4.x</h3>
<p>Ampache 3.4 uses a different config parser that is over 10x faster then the previous version. Unfortunately the new parser is
unable to read the old config files. From inside the Ampache root directory you must run <strong>php bin/migrate_config.inc</strong> from the command line to create your
new config file.</p>

<p>The following settings will not be migrated by the <strong>migrate_config.inc</strong> script due to major changes between versions. The default
values from the ampache.cfg.php.dist file will be used.</p>

<strong>auth_methods</strong> (<i>mysql</i>)<br />
This defines which auth methods vauth will attempt to use and in which order, if auto_create isn't enabled.
The user must exist locally as well<br />
<br />
<strong>tag_order</strong> (<i>id3v2,id3v1,vorbiscomment,quicktime,ape,asf</i>)<br />
This determines the tag order for all cataloged music. If none of the listed tags are found then ampache will default to 
the first tag format that was found. <br />
<br />
<strong>album_art_order</strong> (<i>db,id3,folder,lastfm,amazon</i>)<br />
Simply arrange the following in the order you would like ampache to search if you want to disable one of the search
method simply comment it out valid values are<br />
<br />
<strong>amazon_base_urls</strong> (<i>http://webservices.amazon.com</i>)<br />
An array of Amazon sites to search. NOTE: This will search each of these sites in turn so don't expect it
to be lightning fast! It is strongly recommended that only one of these is selected at any<br />
<br />
<strong>downsample_cmd</strong><br />
This variable no longer exists, all downsampling/transcoding is handled by the transcode_*  please see config file for details.
<br />
</div>
<div id="bottom">
<p><strong>Ampache Debug.</strong><br />
Pour l'Amour de la Musique.</p>
</div>
</body>
</html>
