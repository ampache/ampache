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
body {
	padding-top: 0px;
	margin-top: 0px;
	margin-left: 0px;
	margin-right: 0px;
	font-family: Verdana, Helvetica, sans-serif; 
	font-size: 10px; 
	color: #00ff00;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/background.jpg) #000000 repeat;
}
#maincontainer {
	margin: 0px;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/background.jpg) #000000 repeat;
}
#topbar {
	margin: 0px;
	height: 100px;
	padding-top:10px;
	padding-left:10px;
	padding-bottom: 10px;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/background.jpg) #000000 repeat;
	border: 2px black;
}
#topbarright {
	float: right;
}
#topbarleft {
	float: left;
}
p {
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
	font-size: 10px;
}

a {
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
}
a:visited {
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
}
a:hover {
	text-decoration: underline;
}
a:active {
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
}
.text-box {
	padding-left:5px;
	padding-top:5px;
	margin-bottom:10px;
	background-color: #000000;
	border-right:2px solid #000000;
	border-bottom:2px solid #000000;
	border-left:2px solid #000000;
	border-top:2px solid #000000;
}
td {
	padding: 0px 15px 0px 10px;
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
	font-size: 10px;
}
th {
	padding-right: 10px;
}
input {
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
	font-size: 10px; 
	font-weight: bold; 
	background-color: #716F6B; 
	margin: 2px 2px 2px 2px;
	border-color: #555756;
	cursor: pointer;
}
select {
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
	font-size: 10px;
	background-color: #716F6B; 
	
}
textarea {
	background-color: #716F6B;
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
	font-size: 10px; 
}
.button {
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.gif) #716F6B repeat-x;
	padding: 2px;
	border: 0;
	margin: 2px;
}

tr td a {
	text-decoration:none;
	
}
.login {
	border-top: 1px solid black;
	border-bottom: 1px solid black;
	border-right: 1px solid black;
	border-left: 1px solid black;
}
.table-header {
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.gif) #716F6B repeat-x;
	vertical-align: top;
	text-align: center;
}
/*************** Page Header *********************/
/*************** END Page Header *****************/
.header1 {
	color: #00ff00; 
	font-family: Verdana, Helvetica, sans-serif; 
	font-size: 12px; 
	font-weight: 800;
}
.header2 {
	color: #00ff00;
	font-family: Verdana, Helvetica, sans-serif;
	font-size: 11px;
}
.headrow {
	background:#000000;
}
.odd {
	background:#000000;
	font-size: 10px;
	padding: 0px 10px 0px 10px;
}
.even {
	background:#000000;
	font-size: 10px;
	padding: 0px 10px 0px 10px;
}
.blank {
	background: #fff;
}
.border {
	background:#000000; 
}
.header {
	font-size: 10px; 
}
.error {
	color: #990033; 
}
.fatalerror {
	padding-top: 3px;
	padding-bottom: 3px;
	color: #990033;
	border-right:4px solid #990033;
	border-bottom:4px solid #990033;
	border-left:4px solid #990033;
	border-top:4px solid #990033;		
	font-size: 12px;
	font-weight: 900;
	text-align: center;
}
.disabled {
	text-decoration: line-through;
}
.alphabet {
	font-size: 10px; 
	font-weight: normal; 
}
.smallbutton {
	border:0px;
	padding-left:1px;
	padding-right:1px;
	font-size: 9px;
	cursor: pointer;
}
/* These three lines make a menu vertical */
#content {
	float: none;
}
#sidebar li {
	float: none;
}
#sidebar li ul {
	margin: -1.5em 0 0 9em;
}
.nodisplay {
	 display: none;
}
.display {
}
#mpdpl td {
	padding: 0px 2px 0px 2px;
	text-align: left;
}
#sidebar {
	clear: both;
	height: 100%;
	margin: 0px;
	float: left;
	padding: 0;
	list-style: none;
	line-height: 1.0;
}
#sidebar ul {
	margin: 0px;
	list-style: none;
	padding: 0px;
	font-family: verdana, arial, Helvetica, sans-serif;
	line-height: 1.0;
}
#sidebar li {
	display: block;
	padding: 5px 0px 5px 10px;
	width: 10.5em;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/menu.jpg) #000000 repeat-y;
	border: 1px solid black;
}
#sidebar a, .navbutton {
	display: block;  /*Not sure why this is neccesary, but it is for IE*/
	padding: 0px 0px 0px 20px;
	text-decoration: none;
	}
#sidebar li:hover, #sidebar li.sfhover {
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/menu-active.jpg) #000000 repeat-y;
}
#sidebar li:active {
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/menu-active.jpg) #000000 repeat-y;
}
#sidebar li ul {
	float: left;
	position: absolute;
	width: 9em;
	margin: -1.5em 0 0 10.5em;  /* for vertical menu; comment out otherwise */
	left: -999em;  /* this -999em puts the submenu item way off to the left until it's called back by a hover (below) */
}
#sidebar li:hover ul, #sidebar li.sfhover ul {
	left: auto;  /* this calls the submenu back when the parent li is hovered. */
}
/*star rating styles */
.star-rating {
	list-style:none;
	margin: 0px;
	padding:0px;
	width: 80px;
	height: 15px;
	position: relative;
	background: url(images/ratings/star_rating.gif) top left repeat-x;
}
.star-rating li {
	padding:0px;
	margin:0px;
	/*\*/
	float: right;
	/* */
}
.star-rating li a {
	display:block;
	width:16px;
	height: 15px;
	text-decoration: none;
	text-indent: -9000px;
	z-index: 20;
	position: absolute;
	padding: 0px;
}
.star-rating li a:hover {
	background: url(images/ratings/star_rating.gif) left center;
	z-index: 2;
	left: 0px;
}
li.zero-stars a:hover { 
	background: url(images/ratings/x.gif);
	height: 15px;
	left: 80px;
	display: block;
}
a.zero-stars {
	background: url(images/ratings/x_off.gif);
	height: 15px;
	left: 80px;
	display: block;
}
a.one-stars{
	left: 0px;
}
a.one-stars:hover {
	width:16px;
}
a.two-stars {
	left:16px;
}
a.two-stars:hover {
	width: 32px;
}
a.three-stars {
	left: 32px;
}
a.three-stars:hover {
	width: 48px;
}
a.four-stars {
	left: 48px;
}       
a.four-stars:hover {
	width: 64px;
}
a.five-stars {
	left: 64px;
}
a.five-stars:hover {
	width: 80px;
}
li.current-rating {
	background: url(images/ratings/star_rating.gif) left bottom;
	position: absolute;
	height: 15px;
	display: block;
	text-indent: -9000px;
	z-index: 1;
}
-->
</style>

