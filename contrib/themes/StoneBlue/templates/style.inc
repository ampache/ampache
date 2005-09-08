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
	.bullets
		{
		list-style-image: url(images/bullet.gif);
		margin:5px;
		padding:10px 25px;
		border:1px solid #8888AA
		}
		
	body	
		{ 
		background: <?php echo conf('bg_color1'); ?>; 
		font-family: <?php echo conf('font') ?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		color: <?php echo conf('font_color1'); ?>;
		}

	p	
		{ 
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		}
	a	
		{ 
		color: <?php echo conf('font_color1') ?>; 
		font-family: <?php echo conf('font')?>;
		text-decoration: none;
		}
	a:visited 
		{ 
		color: <?php echo conf('font_color2') ?>; 
		font-family: <?php echo conf('font')?>; 
		text-decoration: none;
		}
	a:hover
		{
		color: <?php echo conf('font_color2') ?>;
		font-family: <?php echo conf('font')?>;
		text-decoration: underline;
		}
	a:active  
		{ 
		color: <?php echo conf('font_color1') ?>; 
		font-family: <?php echo conf('font')?>; 
		text-decoration: none;
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
		color: <?php echo conf('font_color1') ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>px;
		}
    th
        {
        color: <?php echo conf('font_color1')?>;
        padding-right: 10px;
        }
	input	
		{
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font'); ?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		font-weight: bold; 
		background-color: <?php echo conf('base_color2') ?>; 
	    	border-style: solid;
	      	border-width: 1px;
	        border-color: <?php echo conf('bg_color2'); ?>;
		margin: 2px 2px 2px 2px;
		}
	select	{ 
		color: <?php echo conf('font_color2'); ?>; 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
		background-color: <?php echo conf('base_color2') ?>; 
		}
	textarea 
	{ 
		font-family: <?php echo conf('font')?>; 
		font-size: <?php echo conf('font_size'); ?>px; 
	}
    .table-header
    {
    	text: <?php echo conf('font_color3')?>;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2')?> repeat-x;
        vertical-align: top;
    }
    .nowPlaying
    {
    	color: <?php echo conf('font_color1')?>;
    	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2')?> repeat-x;
    	vertical-align: top;
    	font-weight: bold;
    }
    .nowPlaying2
    {
    	color: <?php echo conf('font_color3')?>;
    	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat-x;
    	vertical-align: top;
    	font-weight: bold;
    }
    .popular
    {
    	color: <?php echo conf('font_color1')?>;
    	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2')?> repeat-x;
    	font-weight: bold;
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
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu li {
        float: left;
        margin: 0;
        padding: 0 10px 0 10px;
        border-right: 1px solid #000;
        display: inline;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu li.active {
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu a {
    	color: <?php echo conf('font_color3')?>;
        text-decoration: none;
        font-weight: bold;
    }
    #mainmenu a:hover {
        color: <?php echo conf('font_color1')?>;
    }
    #mainmenu a:active {
        color: <?php echo conf('font_color2')?>;
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
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2')?> repeat;
    }
    #adminmenu li {
        float: left;
        margin: 0;
        padding: 0 20px 0 20px;
        border-right: 1px solid #000;
        display: inline;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #adminmenu li.active {
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #adminmenu a {
    	color: <?php echo conf('font_color3')?>;
        text-decoration: none;
        font-weight: bold;
    }
    #adminmenu a:hover {
        color: <?php echo conf('font_color1')?>;
    }
    #adminmenu a:active {
        color: <?php echo conf('font_color2')?>;
    }
/*************** END Main Menu *************/
/*************** Page Header *********************/
    #pageheader {
        background: <?php echo conf('bgcolor1');?>;
    }
/*************** END Page Header *****************/
    .navitem
    {
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat-x;
        vertical-align: top;
        text-align: center;
    }
    .header1 
    { 
	color: <?php echo conf('font_color1'); ?>; 
	font-family: <?php echo conf('font'); ?>; 
	font-size: <?php echo conf('font_size') + 4; ?>px; 
	font-weight: 900;
    }
    .header2
    {
    	color: <?php echo conf('font_color3'); ?>;
	font-family: <?php echo conf('font'); ?>;
	font-size: <?php echo conf('font_size') + 2; ?>px;
	font-weight: 900;
    }
    .active_navitem
    {
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('bg_color1')?> repeat-x;
        vertical-align: top;
        text-align: center;
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
-->
</style>
