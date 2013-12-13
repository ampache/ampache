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

if (INIT_LOADED != '1') { exit; }

$web_path = AmpConfig::get('web_path');
$htmllang = str_replace("_","-",AmpConfig::get('lang'));
$location = get_location();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl(AmpConfig::get('lang')) ? 'rtl' : 'ltr';?>">

<head>
<link rel="shortcut icon" href="<?php echo $web_path; ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" title="<?php echo scrub_out(AmpConfig::get('site_title')); ?>" href="<?php echo $web_path; ?>/search.php?action=descriptor" />
<?php
if (AmpConfig::get('use_rss')) { ?>
<link rel="alternate" type="application/rss+xml" title="<?php echo T_('Now Playing'); ?>" href="<?php echo $web_path; ?>/rss.php" />
<link rel="alternate" type="application/rss+xml" title="<?php echo T_('Recently Played'); ?>" href="<?php echo $web_path; ?>/rss.php?type=recently_played" />
<?php } ?>
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo AmpConfig::get('site_charset'); ?>" />
<title><?php echo scrub_out(AmpConfig::get('site_title')); ?> - <?php echo $location['title']; ?></title>
<style type="text/css">
    #wrap { position:fixed; left:0; width:100%; top:0; height:100%; }
    .frame_main_full { display: block; width:100%; height: 100%; }
    .frame_footer_hide { width:100%; height:0%; border:1px solid black; display:none; }
    .frame_footer_visible { width:100%; height:100px; border:1px solid black; display:inline; position:fixed; bottom:0; z-index:999999;}
</style>
</head>
<body style="height: 100%;">
<div id="wrap">
    <div id="maindiv" style="width:100%; height: 100%;">
        <iframe id="frame_main" class="frame_main_full" src="<?php
if (isset($_GET['target_link'])) {
    echo $_GET['target_link'];
} else {
    echo $web_path . "/index.php?framed=1";
}
?>"></iframe>
    </div>
    <iframe id="frame_footer" class="frame_footer_hide" src=""></iframe>
</div>
</body>
</html>
