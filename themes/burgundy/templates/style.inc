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
		cursor: pointer;
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
/* These three lines make a menu horizontal */
	#content { float: left; clear: both;}
	#sidebar li { float: left; }
	#sidebar li ul { margin: 0.5em 0 0 -1.1em; }
/**
 * Div Definitions
 * These define how the page is layed out, be careful with these as changes to them
 * can cause drastic layout changes

	#maincontrainer
		{
			margin: 0px;
		} 	
	#topbar
		{
			height: 80px;
			background-color: <?php echo conf('bg_color1'); ?>;
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
		position: relative;
		clear: right;
		height: 100%;
		margin-left: 0px; 
		float: left;
		width: 100%;
		list-style: none;
		}

	#sidebar ul
		{
		margin-left:0px;
		margin-top:0px;
		margin-bottom:0px;
		margin-right: 3px;
		padding: 0px;
		list-style-type: none;
		font-family: verdana, arial, Helvetica, sans-serif;
		}

	#sidebar li 
		{ 
	        float: left;
	        margin: 0;
		margin-left:2px;
	        padding: 0 3px 0 0px;
	        display: inline;
		}

	#sidebar a, .navbutton
		{
		padding: 5px 5px;
		color: <?php echo conf('font_color2'); ?>;
		background-color: <?php echo conf('row_color3'); ?>;
		text-decoration: none;
		}

	#sidebar a:hover
		{
		color: <?php echo conf('font_color1'); ?>;
		background-color: <?php echo conf('row_color1'); ?>;
		text-decoration: none;
		}
	#sidebar ul ul {
		width: 100%;
		position: absolute;
		left: 0;
		top: 20px;
		margin: 0;
		padding: 0;
		white-space: nowrap;
		}	
	
	#sidebar ul ul li { 
		padding: 2px;
		margin: 0;
		border: 0;
		}

	#sidebar ul ul a, .subnavbutton
		{
		display: block;
		padding: 3px 3px 3px 3px;
		color: <?php echo conf('font_color2'); ?>;
		background-color: <?php echo conf('row_color2'); ?>;
		text-decoration: none;
		}

	#sidebar ul ul a:hover
		{
		color: <?php echo conf('font_color1'); ?>;
		background-color: <?php echo conf('row_color1'); ?>;
		text-decoration: none;
		}
	#content
		{ 
		float: left;	
		margin-left:0px;
		margin-top: 35px;
		}

 * End Div Definitions
 * This is the end of the main structure def's
 */
-->
</style>
