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



# CUSTOMIZE THIS CSS FILE HERE

# the following line changes the font to Comic Sans MS
# if you dont have that font on linux check out http://corefonts.sourceforge.net/
# or on debian i use the package msttcorefonts
# if you dont like your fonts overridden at all comment out the first line and uncomment the second one

$font = "Comic Sans MS, " . conf('font');
#$font = "conf('font')";


# customize the main border here
$border = "outset 5pt";

# this is the thin inside border on the top and left side of the main border
$relief_border = "solid 1pt " . conf('bg_color2');

# this is the border on the active_nav item
$relief_border2 = "solid 1pt " . conf('row_color1');




?>
<style type="text/css">
<!--
	body	
		{ 
		background: <?php echo conf('bg_color1'); ?>; 
		font-family: <?php echo $font ?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		color: <?php echo conf('font_color2'); ?>;
		}

	p	
		{ 
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo $font?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		}
	a
		{ 
		color: <?php echo conf('font_color1') ?>; 
		font-family: <?php echo $font?>; 
		text-decoration: none;
		}
	a:visited 
		{ 
		color: <?php echo conf('font_color3') ?>; 
		font-family: <?php echo $font?>; 
		text-decoration: none;
		}
	a:hover	
		{ 
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo $font?>; 
		text-decoration: underline;
		}
	 table a:hover
		{ 
		color: <?php echo conf('bg_color1') ?>; 
		font-family: <?php echo $font?>; 
		text-decoration: underline;
		}
	a:active  
		{ 
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo $font?>; 
		text-decoration: none;
		}
	.border 
		{ 
		background:<?php echo conf('bg_color2'); ?>; 
		border-collapse: separate;
		border: <?php echo "$border " . conf('row_color1'); ?>;
		border-spacing: 0pt;
		}
	.border tr td:first-child
		{
		# this is the one thats put the border down the left side;
		border-left: <?php echo "$relief_border"; ?>;
		}
	.border tr table tr td:first-child
		{
		# gets rid of funkyness on left side in albums.php;
		border-left: solid 0pt <?php echo conf('bg_color2'); ?>;
		}
	.border .even:first-child td
		{
		# adds line on top in albums.php;
		border-top: <?php echo "$relief_border"; ?>;
		}
	.border .even .even:first-child td
		{
		# removes extra line from random play on index.php;
		border-top: solid 0pt <?php echo conf('bg_color2'); ?>;
		}
	.border .even:first-child tr td 
		{
		# gets rid of double line on top in albums.php;
		border-top: solid 0pt <?php echo conf('bg_color2'); ?>;
		}
	.login
		{ 
		background:<?php echo conf('bg_color2'); ?>; 
		border-collapse: separate;
		border: <?php echo "$border " . conf('row_color1'); ?>;
		border-spacing: 0pt;
		}
	.table-header
		{
		background: <?php echo conf('base_color2')?> ;
		vertical-align: top;
		}
	.table-header td
		{
		border-bottom: <?php echo "$relief_border"; ?>;
		border-top: <?php echo "$relief_border"; ?>;
		text-align: center;
		}
	.table-header  th
		{
		border-bottom: <?php echo "$relief_border"; ?>;
		border-top: <?php echo "$relief_border"; ?>;
		}
	.table-header  th:first-child
		{
		#border-top: <?php echo "$relief_border"; ?>;
		#border-bottom: <?php echo "$relief_border"; ?>;
		border-left: <?php echo "$relief_border"; ?>;
		}
	.tabledata
		{
		background:<?php echo conf('bg_color2'); ?>; 
		border-collapse: separate;
		border: <?php echo "$border " . conf('row_color1'); ?>;
		}
	.tabledata tr td:first-child
		{
		# this line is for the playlist page;
		border-left: solid 1pt <?php echo conf('bg_color2'); ?>;
		}
	.text-box 
		{
		padding-left:5px;
		padding-top:5px;
		margin-bottom:10px;
		background: <?php echo conf('base_color1'); ?>;
		border-collapse: separate;
		border: <?php echo "$border " . conf('base_color1'); ?>;
		}
	.text-box .tabledata
		{
		# these next 2 lines fix the show catalog box;
		border: outset 0pt <?php echo conf('base_color1'); ?>;
		background: <?php echo conf('base_color1'); ?>;
		#border-left: solid 0pt <?php echo conf('bg_color2'); ?>;
		}
	.text-box .tabledata tr td
		{
		# this line removes a red line from the left of the show catalog box;
		border-left: solid 0pt <?php echo conf('bg_color2'); ?>;
		}

/*************** Main Menu *****************/
    #mainmenu {
    	float: left;
	width: 99%;
	margin: 0;
	padding: 0;
	list-style: none;
	border-collapse: separate;
	border: <?php echo "$border " . conf('row_color1'); ?>;
	border-spacing: 0pt;
	background: <?php echo conf('base_color2')?> ;
    }
    #mainmenu li {
    	float: left;
	margin: 0;
        padding: 0 20px 0 20px;
        display: inline;
	background: <?php echo conf('base_color2')?> ;
	border-top: <?php echo "$relief_border"; ?>;
	border-right: <?php echo "$relief_border"; ?>;
	text-align: center;
    }
	#mainmenu  li:first-child
		{
		border-left: <?php echo "$relief_border"; ?>;
		}
    #mainmenu li.active {
		vertical-align: top;
		text-align: center;
		border: <?php echo "$relief_border2"; ?>;
		background:<?php echo conf('bg_color2'); ?>; 
    }
    #mainmenu a {
		color: <?php echo conf('font_color1') ?>; 
		font-family: <?php echo $font?>; 
		text-decoration: none;
    }
    #mainmenu a:hover {
		color: <?php echo conf('bg_color1') ?>; 
		font-family: <?php echo $font?>; 
		text-decoration: underline;
    }
    #mainmenu a:active {
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo $font?>; 
		text-decoration: none;
    }
/*************** END Main Menu *************/
/*************** Admin Menu *************/
    #adminmenu {
	width: 99%;
    	float: left;
        margin: 0;
        padding: 0;
        list-style: none;
	border-collapse: separate;
	border: <?php echo "$border " . conf('row_color1'); ?>;
	border-spacing: 0pt;
	background: <?php echo conf('base_color2')?> ;
    }
    #adminmenu li {
	float: left;
        margin: 0;
        padding: 0 20px 0 20px;
	background: <?php echo conf('base_color2')?> ;
	border-top: <?php echo "$relief_border"; ?>;
	border-right: <?php echo "$relief_border"; ?>;
	text-align: center;
        display: inline;
    }
	#adminmenu  li:first-child
		{
		border-left: <?php echo "$relief_border"; ?>;
		}
    #adminmenu li.active {
	vertical-align: top;
	text-align: center;
	border: <?php echo "$relief_border2"; ?>;
	background:<?php echo conf('bg_color2'); ?>; 
    }
    #adminmenu a {
        text-decoration: none;
    }
    #adminmenu a:hover {
	color: <?php echo conf('bg_color1') ?>; 
	font-family: <?php echo $font?>; 
	text-decoration: underline;
    }
    #adminmenu a:active {
        color: #000;
    }
/*************** END Main Menu *************/
/*************** Page Header *********************/
    #pageheader {
        background: <?php echo conf('bgcolor1');?>;
    }
/*************** END Page Header *****************/

	td	
		{ 
        	padding: 0px 8px 0px 8px;
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo $font?>; 
		font-size: <?php echo conf('font_size'); ?>px;
		}
	th
		{
		padding-right: 10px;
		}
	input	
		{
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo $font; ?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		font-weight: bold; 
		background-color: <?php echo conf('base_color2') ?>; 
		}
	.header1 
		{ 
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo $font; ?>; 
		font-size: <?php echo conf('font_size') + 6; ?>px; 
		font-weight: 900;
		}
	.header2
		{
		color: <?php echo conf('font_color2'); ?>;
		font-family: <?php echo $font; ?>;
		font-size: <?php echo conf('font_size') + 2; ?>px;
		font-weight: 900;
		}
	.navitem
		{
		background: <?php echo conf('base_color2')?>;
		vertical-align: top;
		text-align: center;
		border: <?php echo "$relief_border"; ?>;
		}
	.active_navitem
		{
		background: <?php echo conf('bg_color2')?>;
		vertical-align: top;
		text-align: center;
		border: <?php echo "$relief_border2"; ?>;
		}
	.odd 
		{
		background:<?php echo conf('row_color2'); ?>;
		font-size: <?php echo conf('font_size'); ?>px;
		}
	.even
		{
		background:<?php echo conf('row_color3'); ?>;
		font-size: <?php echo conf('font_size'); ?>px;
		}
 	.error	
		{ 
		color: <?php echo conf('error_color'); ?>; 
		}
	.fatalerror
		{
		padding-top: 3px;
		padding-bottom: 3px;
		color: <?php echo conf('error_color'); ?>;
                border-right:4px solid <?php echo conf('error_color'); ?>;
                border-bottom:4px solid <?php echo conf('error_color'); ?>;
                border-left:4px solid <?php echo conf('error_color'); ?>;
                border-top:4px solid <?php echo conf('error_color'); ?>;		
		font-size: <?php echo conf('font_size')+2; ?>px;
		font-weight: 900;
		text-align: center;
		}
	.alphabet 
		{ 
		font-size: <?php echo conf('font_size'); ?>px; 
		font-weight: normal; 
		}
-->
</style>
