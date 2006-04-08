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
		background: <?php echo conf('bg_color1'); ?>; 
		font-family: Verdana, Helvetica, sans-serif; 
		font-size: 12px; 
		color: #333;
		}

	p	
		{ 
		color: #333; 
		font-family: Verdana, Helvetica, sans-serif; 
		font-size: 12px; 
		}
	a	
		{ 
		color: #333; 
		font-family: Verdana, Helvetica, sans-serif; 
		}
	a:visited 
		{ 
		color: #333; 
		font-family: Verdana, Helvetica, sans-serif; 
		}
	a:active  
		{ 
		color: #333; 
		font-family: Verdana, Helvetica, sans-serif; 
		}
	.text-box 
		{
		padding-left:5px;
		padding-top:5px;
		margin-bottom:10px;
		background-color: #f4f4f4;
		}
    table.tabledata
    {
    }

	td	
		{ 
        	padding: 0px 8px 0px 8px;
		color: #333>; 
		font-family: Verdana, Helvetica, sans-serif; 
		font-size: 12px;
		}
    th
        {
        padding-right: 10px;
        }
	input	
		{
		color: #333; 
		font-family: Verdana, Helvetica, sans-serif; 
		font-size: 12px; 
		background-color: #f4f4f4; 
		}
	.button {
		color: #fff;
		font-family: Verdana, Helvetica, sans-serif;
		font-size: 12px;
		background-color: #adbdcf;
		}
	select	{ 
		color: #333; 
		font-family: Verdana, Helvetica, sans-serif; 
		font-size: 12px; 
		background-color: #f4f4f4; 
		}
	textarea 
	{ 
		color: #333; 
		font-family: Verdana, Helvetica, sans-serif; 
		font-size: 12px; 
	}
    .table-header
    {
        background: transparent url(<?php echo conf('web_path'); ?>/<?php echo conf('theme_path'); ?>/images/bside.side.png) repeat-x top center;
        vertical-align: top;
    }
    .navitem
    {
        margin: 0;
	padding:5px;
	color: #333;
        background: transparent url(<?php echo conf('web_path'); ?>/<?php echo conf('theme_path'); ?>/images/sidetitle.png) repeat-x top center;
	text-align: center;
	text-decoration: none;
    }
    .navitem a
    {
    	color: #333;
    	text-decoration: none;
	font-family: verdana, helvetica, sans-serif;
    }
    .navitem a:hover
    {
    	color: #1d479a;
	text-decoration: underline;
    }
    .header1 
    { 
	color: #333; 
	font-family: Verdana, Helvetica, sans-serif; 
	font-size: 18px; 
	font-weight: 900;
    }
    .header2
    {
    	color: #333;
	font-family: Verdana, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: 900;
    }
    .active_navitem
    {
        margin: 0;
	padding:5px;
	background: transparent url(<?php echo conf('web_path'); ?>/<?php echo conf('theme_path'); ?>/images/bside.side.png) repeat-x top center;
        text-align: center;
    }
    .active_navitem a
    {
    	color: #333;
	text-decoration: none;
    }
    .active_navitem a:hover
    {
    	color: #1d479a;
	text-decoration: none;
	text-decoration: underline;
    }
	.headrow 
		{
		background:<?php echo conf('row_color1'); ?>;
		font-size: 12px;
		}
	.odd 
		{
		background: #fff;
		font-size: 12px;
		}
	.even
		{
		background: #edf1f8;
		font-size: 12px;
		}
    .blank
        {
        background: #fff;
        }
	.border 
		{ 
		background: #fff; 
		padding: 5px;
		}
	.header 
		{ 
		font-size: 12px; 
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
		font-size: 14px;
		font-weight: 900;
		text-align: center;
		}
	.disabled
		{
		text-decoration: line-through;
		}
	.alphabet 
		{ 
		font-size: 12px; 
		font-weight: normal; 
		}
        #topbar
                {
                        height: 130px;
                        padding-top:10px;
                        padding-left:10px;
                        background-color: <?php echo conf('bg_color1'); ?>;
                }
	
-->
</style>
