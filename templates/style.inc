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
		padding-top: 0px;
		margin-top: 0px;
		margin-left: 0px;
		margin-right: 0px;
		background: <?php echo conf('bg_color1'); ?>; 
		font-family: <?php echo conf('font') ?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		color: <?php echo conf('font_color2'); ?>;
		}

	p	
		{ 
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
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
    }

	td	
		{ 
        padding: 0px 8px 0px 8px;
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>px;
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
		}
	select	{ 
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		background-color: <?php echo conf('base_color2'); ?>; 
		}
	textarea 
		{ 
		background-color: <?php echo conf('base_color2'); ?>;
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		}
    .table-header
    {
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat-x;
        vertical-align: top;
    }
/*************** Main Menu *****************/
    #mainmenu {
    	float: left;
	width: 100%;
	margin: 0;
	padding: 0;
	list-style: none;
	border-top: 1px solid #000;
	border-bottom: 1px solid #000;
	border-right: 1px solid #000;
	border-left: 1px solid #000;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu li {
    	float: left;
	margin: 0;
        padding: 0 10px 0 10px;
	border-right: 1px solid #000;
        display: inline;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu li.active {
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu a {
        text-decoration: none;
    }
    #mainmenu a:hover {
        color: #000;
    }
    #mainmenu a:active {
        color: #00a;
    }
/*************** END Main Menu *************/
/*************** Admin Menu *************/
    #adminmenu {
    	float: left;
    	width: 100%;
        margin: 0;
        padding: 0;
        list-style: none;
        border-bottom: 1px solid #000;
        border-right: 1px solid #000;
	border-left: 1px solid #000;
	border-top: 1px solid #000;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat;
    }
    #adminmenu li {
	float: left;
        margin: 0;
        padding: 0 20px 0 20px;
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
        background: #8B8B8B;
    }
/*************** END Page Header *****************/
    .header1 
    { 
	color: <?php echo conf('font_color2'); ?>; 
	font-family: <?php echo conf('font'); ?>; 
	font-size: <?php echo conf('font_size') + 6; ?>px; 
	font-weight: 900;
    }
    .header2
    {
    	color: <?php echo conf('font_color2'); ?>;
	font-family: <?php echo conf('font'); ?>;
	font-size: <?php echo conf('font_size') + 2; ?>px;
	font-weight: 900;
    }
	.headrow 
		{
		background:<?php echo conf('row_color1'); ?>;
		font-size: <?php echo conf('font_size'); ?>px;
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
	.disabled
		{
		text-decoration: line-through;
		}
	.alphabet 
		{ 
		font-size: <?php echo conf('font_size'); ?>px; 
		font-weight: normal; 
		}
	.smallbutton
		{
		border:0px;
		padding-left:1px;
		padding-right:1px;
		font-size: <?php echo conf('font_size') - 1; ?>px;
		cursor: pointer;
		}
	.sidebar 
		{
		margin-left:0px;
		margin-top:0px;
		margin-right:0px;
		padding-right:0px;
		padding-top: 0px;
		padding-left: 0px;
		width:160px;
		background: <?php echo conf('base_color2'); ?>;
		}
/**
 * Div Definitions
 * These define how the page is layed out, be careful with these as changes to them
 * can cause drastic layout changes
 */
	#maincontrainer
		{
			margin: 0px;
		} 	
	#topbar
		{
			height: 80px;
			background-color: #888888;
		}
	#topbarright
		{
			float: right;
		}
	#topbarleft
		{
			float: left;
		}
	#sidebar
		{ 
			clear: both;
			height: 100%;
			margin-left: 0px; 
			margin-top:0px;
			float: left;
			width: 170px;
		}

	#sidebar ul
		{
		margin-left:0px;
		margin-top:0px;
		margin-bottom:0px;
		margin-right: 10px;
		padding: 0px;
		list-style-type: none;
		font-family: verdana, arial, Helvetica, sans-serif;
		}

	#sidebar li { 
		margin: 0 0 1px 0;  
		padding-top:0px;
		padding-bottom:0px;
	}

	#sidebar a, .navbutton
		{
		display: block;
		padding: 5px 10px;
		color: #000;
		background-color: #666;
		text-decoration: none;
		}

	#sidebar a:hover
		{
		color: #000;
		background-color: #ccc;
		text-decoration: none;
		}
		
	#sidebar ul ul li { margin: 0 0 1px 0; }

	#sidebar ul ul a, .subnavbutton
		{
		display: block;
		padding: 5px 5px 5px 30px;
		width: 125px;
		color: #000;
		background-color: #ccc;
		text-decoration: none;
		}

	#sidebar ul ul a:hover
		{
		color: #000;
		background-color: #ddd;
		text-decoration: none;
		}
	#content
		{ 
		float: left;	
		margin-left:0px;
		}
/**
 * End Div Definitions
 * This is the end of the main structure def's
 */
-->
</style>
