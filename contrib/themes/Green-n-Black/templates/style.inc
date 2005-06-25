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
	body	
		{ 
		padding: 5px 0px 5px 0px;
		background: <?php echo conf('bg_color1'); ?>;
		font-family: <?php echo conf('font') ?>; 
		font-size: <?php echo conf('font_size'); ?>pt;
		color: <?php echo conf('font_color2'); ?>;
		white-space: nowrap;
		margin: 2px;
		}

	p	
		{ 
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>pt;
		margin: 8px;
		white-space: normal;
		}

	a	
		{ 
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo conf('font')?>; 
		}
	a:visited 
		{ 
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo conf('font')?>; 
		}
	a:hover {
        	text-decoration: underline;
		}
	a:active  
		{ 
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo conf('font')?>; 
		}
	.text-box 
		{
		padding-left:5px;
		padding-top:5px;
		margin-bottom:10px;
		background-color: <?php echo conf('base_color1'); ?>;
		border-right:2px solid <?php echo conf('bg_color2'); ?>;
		border-bottom:2px solid <?php echo conf('bg_color2'); ?>;
		border-left:2px solid <?php echo conf('bg_color2'); ?>;
		border-top:2px solid <?php echo conf('bg_color2'); ?>;
		}
	table.tabledata
		{
		border: 0;
		}

	td
		{
		padding: 0px 10px 0px 10px;
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>pt;
		white-space: nowrap;
		}
	th
		{
		padding-right: 10px;
		}
	input
		{
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font'); ?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		font-weight: bold; 
		background-color: <?php echo conf('base_color2') ?>; 
		margin: 2px 2px 2px 2px;
		border-color: #555756;
		cursor: pointer;
		}
	select
		{
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>pt; 
		background-color: <?php echo conf('base_color2'); ?>; 
		
		}
	textarea
		{
		background-color: <?php echo conf('base_color2'); ?>;
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>pt; 
		}
	.button
		{
		background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.gif) <?php echo conf('base_color2')?> repeat-x;
		padding: 2px;
		border: 0;
		margin: 2px;
		}

	ol li a
		{
		white-space: nowrap;
		text-decoration:none;
		font-weight: 500;
		}
	tr td a
		{
		text-decoration:none;
		font-weight: 500;
		}
	.login
		{
		border-top: 1px solid white;
		border-bottom: 1px solid white;
		border-right: 1px solid white;
		border-left: 1px solid white;
		}
	.table-header
		{
		background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.gif) <?php echo conf('base_color2')?> repeat-x;
		vertical-align: top;
		text-align: center;
		}
/*************** Main Menu *****************/
    #mainmenu {
float:left;
	width: 1000px;
	overflow: hidden;
	margin-left: auto;
	margin-right: auto;
	margin-top: 0;
	margin-bottom: 0;
	padding: 0px;
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu li {
	float:left;
	white-space: nowrap;
	overflow: hidden;
	margin: 0 0 0 0;
        padding: 0 1.5em 0 1.5em;
	list-style: none outside;
	font-weight: 600;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/tablebg.gif) <?php echo conf('base_color2');?> repeat-x;
	font-size: <?php echo conf('font_size') - 1; ?>pt; 
    }
    #mainmenu li.active {
	white-space: nowrap;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/active.tablebg.gif) <?php echo conf('base_color2');?> repeat-x;

    }
    #mainmenu a {
        text-decoration: none;
    }
    #mainmenu a:active {
        color: #00a;
    }
    #mainmenu a:hover {
        color: #000;
    }

/*************** END Main Menu *************/
/*************** Admin Menu *************/
    #adminmenu {
float:left;
	width: 1000px;
	overflow: hidden;
	margin-left: auto;
	margin-right: auto;
	margin-top: 0;
	margin-bottom: 0;
	padding: 0px;
        list-style: none;
        border: 1px solid #000;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat;
    }
    #adminmenu li {
	white-space: nowrap;
	float: left;
        padding: 0 2em 0 2em;
	border-right: 1px solid #000;
        display: inline;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #adminmenu li.active {
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2');?> repeat-x;
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
/*************** END Main Menu *************/
/*************** Page Header *********************/
	#pageheader {
		background: <?php echo conf('bg_color1');?>;
		}

	#pageheader img {
		display: block;
		margin-left: auto;
		margin-right: auto;
		margin-bottom: 10px;
		}



/*************** END Page Header *****************/
	.header1
		{
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font'); ?>; 
		font-size: <?php echo conf('font_size') + 6; ?>pt; 
		font-weight: 800;
		}
	.header2
		{
		color: <?php echo conf('font_color2'); ?>;
		font-family: <?php echo conf('font'); ?>;
		font-size: <?php echo conf('font_size') + 2; ?>pt;
		font-weight: 800;
		}
	.headrow 
		{
		background:<?php echo conf('row_color1'); ?>;
		font-size: <?php echo conf('font_size'); ?>pt;
		}
	.odd 
		{
		background:<?php echo conf('row_color2'); ?>;
		font-size: <?php echo conf('font_size'); ?>pt;
		padding: 0px 10px 0px 10px;
		}
	.even
		{
		background:<?php echo conf('row_color3'); ?>;
		font-size: <?php echo conf('font_size'); ?>pt;
		padding: 0px 10px 0px 10px;
		}
	.blank
		{
		background: #fff;
		}
	.border 
		{ 
		background:<?php echo conf('bg_color2'); ?>; 
		}
	.header 
		{ 
		font-size: <?php echo conf('font_size'); ?>pt; 
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
		font-size: <?php echo conf('font_size')+2; ?>pt;
		font-weight: 900;
		text-align: center;
		}
	.disabled
		{
		text-decoration: line-through;
		}
	.alphabet 
		{ 
		font-size: <?php echo conf('font_size'); ?>pt; 
		font-weight: normal; 
		}

-->
</style>


