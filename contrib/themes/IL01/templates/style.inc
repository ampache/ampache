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

/*!
 @header Style File
 @discussion this is the css that is included on every page of
 	ampache, mod this to change the look and feel of the site
*/
?>
<style type="text/css">
<!--

/* Main
---------------------------------*/
body {
	background: <?php echo conf('bg_color1'); ?>;
	font-family: <?php echo conf('font') ?>; 
	font-size: <?php echo conf('font_size'); ?>px; 
	color: <?php echo conf('font_color2'); ?>;
	padding: 0; 
	margin: 0;
}

p {
	color: <?php echo conf('font_color2'); ?>; 
	font-family: <?php echo conf('font')?>; 
	font-size: <?php echo conf('font_size'); ?>pt;
	margin: 8px;
	white-space: normal;
}

a {
	color: <?php echo conf('font_color2') ?>; 
	font-family: <?php echo conf('font')?>; 
}

a:visited { 
	color: <?php echo conf('font_color2') ?>; 
	font-family: <?php echo conf('font')?>; 
}

a:hover {
	text-decoration: underline;
}

a:active {
	color: <?php echo conf('font_color2') ?>; 
	font-family: <?php echo conf('font')?>; 
}

/* Page Header 
---------------------------------*/
#pageheader {
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/top_fill.jpg) <?php echo conf('base_color2')?> repeat-x;
}

#pageheader img {
	text-align: left;
	border: 0px;
}

/* Main Menu
---------------------------------*/
#mainmenu {
	width: 100%;
	height: 31px; 
	overflow: hidden;
	margin-left: auto;
	margin-right: auto;
	margin-top: 0;
	padding: 6px 0 0 0;
	float: left;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/bg_menubar.jpg) top repeat-x;
}

#mainmenu li {
	list-style: none; 
	display: inline;
	top: 15px;
	height: 31px;
	margin: 5px;
	padding: 10px 8px 10px 5px;
	text-align: center;
	vertical-align: middle;
    background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/button_sep.jpg) bottom right no-repeat;
	font-size: <?php echo conf('font_size') - 1; ?>pt; 
}

#mainmenu li.active {
	font-weight: bold;
	text-decoration: none;
}

#mainmenu a {
	text-decoration: underline;
}

#mainmenu a:active {
	color: #00a;
}

#mainmenu a:hover {
	color: #000;
	text-decoration: none;
}

/* Admin Menu
---------------------------------*/
#adminmenu {
	width: 100%;
	height: 31px; 
	overflow: hidden;
	margin-left: auto;
	margin-right: auto;
	margin-top: 0;
	padding: 6px 0 0 0;
	float: left;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.jpg) top repeat-x;
}

#adminmenu li {
	white-space: nowrap;
	float: left;
	padding: 0 2em 0 2em;
	border-right: 1px solid #000;
	display: inline;
}

#adminmenu li.active {
	text-decoration: underline;
}

#adminmenu a {
	text-decoration: none;
}

#adminmenu a:hover {
	color: #000;
}

#adminmenu a:active {
	color: #000;
}

/* table styles
---------------------------------*/
table.tabledata {
	margin-left: 12px;
}

.table-header {
	height: 20px;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.jpg) <?php echo conf('base_color2')?> repeat-x;
	vertical-align: top;
	text-align: center;
}

.border {
	background:<?php echo conf('bg_color2'); ?>;
	margin-left: 12px;
}

td {
	padding: 2px 10px 2px 10px;
	color: <?php echo conf('font_color2') ?>; 
	font-family: <?php echo conf('font')?>; 
	font-size: <?php echo conf('font_size'); ?>pt;
	white-space: nowrap;
}

th {
	padding-right: 10px;
	font-weight: bold;
}

tr td a {
	text-decoration:none;
	font-weight: 500;
}

/* alphabet styles
---------------------------------*/
.alphabet {
	font-size: <?php echo conf('font_size'); ?>pt; 
	font-weight: normal;
	margin-left: 10px;
	margin-right: 10px;
	padding: 2px;
	background-color: #FFFFFF;
}

.alphabet a {
	background-color: #FFFFFF;
	text-decoration: none;
	margin: 0px;
}

.alphabet a:hover {
	text-decoration: underline;
}

.alphabet a:active {
	background-color: #DDDDDD;
	text-decoration: underline;
	font-size: <?php echo conf('font_size') + 3; ?>pt;
}

/* text-box styles
---------------------------------*/
.text-box {
	font-color: #F3F3F3;
	padding-left:5px;
	padding-top:5px;
}

.text-box ul {
	padding: 10px;
}

.text-box a {
	color: #FFFFFF;
}

.text-box li {
	background:  url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/vineta.gif) no-repeat 3px 4px;
	list-style: none;
	margin: 0px;
	padding: 0 0 0 13px;
	line-height: 15px;
	font-size: 12px; 
}
/* Various Styles and Headers
---------------------------------*/
.header1 {
	margin-left: 12px;
	margin-bottom: 5px;
	color: #FFFFFF;
	font-family: <?php echo conf('font'); ?>; 
	font-size: <?php echo conf('font_size') + 3; ?>pt;
	font-weight: 500;
}

.header1 a {
	color: #FFFFFF;
	text-decoration: underline;
}

.header2 {
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/y_bg.jpg) no-repeat;
	height: 55px;
	width: 580px;
	margin: 8px;
	padding: 18px 30px 10px 19px;
	color: <?php echo conf('font_color2'); ?>;
	font-family: <?php echo conf('font'); ?>;
	font-size: <?php echo conf('font_size') + 1; ?>pt;
	font-weight: 500;
}

.error { 
	color: <?php echo conf('error_color'); ?>;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/exclamation_20.gif) top no-repeat;
	background-color: #FFFFFF;
	width: 200px;
	padding: 20px 10px 5px 20px; 
	margin: 12px;
}

input {
	color: <?php echo conf('font_color2'); ?>; 
	font-family: <?php echo conf('font'); ?>; 
	font-size: <?php echo conf('font_size'); ?>px; 
	font-weight: bold; 
	background-color: <?php echo conf('base_color2') ?>; 
	padding: 1px;
	margin: 1px;
	vertical-align: middle;
	border: 1px solid <?php echo conf('bg_color1'); ?>;
	cursor: pointer;
}

input.button {
	color: #000000;
	margin: 2px;
	font-size: 10px;
	background-color: #ffffff;
	border: 1px solid;
}

input.button:hover {
	color: #ffffff;
	background-color: <?php echo conf('bg_color1'); ?>;
	border: 1px solid <?php echo conf('bg_color1'); ?>;
}

select {	
	padding: 1px;
	border: 1px solid <?php echo conf('bg_color1'); ?>;
	margin: 1px;
	vertical-align: middle;
	color: <?php echo conf('font_color2'); ?>; 
	font-family: <?php echo conf('font'); ?>; 
	font-size: <?php echo conf('font_size'); ?>px; 
	font-weight: bold; 
	background-color: <?php echo conf('base_color2') ?>; 
}

textarea {
	background-color: <?php echo conf('base_color2'); ?>;
	color: <?php echo conf('font_color2') ?>; 
	font-family: <?php echo conf('font')?>; 
	font-size: <?php echo conf('font_size'); ?>pt; 
}

.button {
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.gif) <?php echo conf('base_color2')?> repeat-x;
	padding: 2px;
	border: 0;
	margin: 2px;
}

ol li a {
	white-space: nowrap;
	text-decoration:none;
	font-weight: 500;
}

.login {
	border-top: 1px solid white;
	border-bottom: 1px solid white;
	border-right: 1px solid white;
	border-left: 1px solid white;
}

.odd {
	background:<?php echo conf('row_color2'); ?>;
	font-size: <?php echo conf('font_size'); ?>pt;
	padding: 10px;
}

.even {
	background:<?php echo conf('row_color3'); ?>;
	font-size: <?php echo conf('font_size'); ?>pt;
	padding: 10px;
}

.headrow {
	background:<?php echo conf('row_color1'); ?>;
	font-size: <?php echo conf('font_size'); ?>pt;
}

.blank {
	background: #fff;
}

.header {
	font-size: <?php echo conf('font_size'); ?>pt; 
}

.fatalerror {
	padding-top: 3px;
	padding-bottom: 3px;
	color: <?php echo conf('error_color'); ?>;
	border-right:4px solid <?php echo conf('error_color'); ?>;
	border-bottom:4px solid <?php echo conf('error_color'); ?>;
	border-left:4px solid <?php echo conf('error_color'); ?>;
	border-top:4px solid <?php echo conf('error_color'); ?>;		
	font-size: <?php echo conf('font_size')+2; ?>pt;
	font-weight: 900;
	text-align: center;
}

.disabled {
	text-decoration: line-through;
}

-->
</style>


