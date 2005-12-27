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

<script type="text/javascript" language="javascript">
<!-- Begin Suckerfish hover menu JS
// function needed for IE.  attaches mouseover/out events to give/remove css class .sfhover (fake hover)
sfHover = function(navlist) {
var sfEls = document.getElementById("navlist").getElementsByTagName("LI");
for (var i=0; i <sfEls.length; i++) {
    sfEls[i].onmouseover=function() {
        this.className+=" sfhover";
    }
    sfEls[i].onmouseout=function() {
        this.className=this.className.replace(new RegExp("sfhover\\b"), "");
    }
} // end for
} // end function for sfHover
if (window.attachEvent) window.attachEvent("onload", sfHover);
// End Suckerfish hover menu JS-->
</script>

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

	.npsong
		{
		background-color: #FFFF66;
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
		background: <?php echo conf('base_color2'); ?>;
		}
/**
 * Div Definitions
 * These define how the page is layed out, be careful with these as changes to them
 * can cause drastic layout changes
 */
	#maincontainer
		{
			margin: 0px;
		} 	
	#topbar
		{
			height: 80px;
			padding-top:10px;
			padding-left:10px;
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
	#navcontainer ul li
		{
			float:left; 
			width:100%;
		}

	.subnavbutton 
		{
			background-color: <?php echo conf('row_color1'); ?>;
			text-align:center;
			text-decoration: none;
			color: <?php echo conf('font_color2'); ?>;
		}

	#content
		{ 
		margin-left:0px;
		}
/**
 * Experimental for menus (Thx Sigger)
 * TO DO: Fill in 1px border around menus & submenu items 
 * Make padding appply to the li, not just an a.  Moving paddng: to li throws off the dropdown menu alignment.
 */
    #sidebar {
	clear: both;
	height: 100%;
        margin: 0;
        float: left;
 /*       width: 110px;  /* this controls the width of the sidebar.  horizontal menu needs more width */
        padding: 0;
        list-style: none;
        border: 1px solid #000;
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
/*        margin: 0 0 1px 0; */
	margin: 0;
        display: block;   
	border-bottom: 1px solid white;
	border-left: 1px solid white;
	border-right: 1px solid white;
	padding: 5px 0px 5px 10px;   
	width: 10em;
	background-color: <?php echo conf('row_color2'); ?>;
    }
    #sidebar a, .navbutton {
	display: block;  /*Not sure why this is neccesary, but it is for IE*/
	text-decoration: none;
	}
    #sidebar li:hover, #sidebar li.sfhover {
        color: <?php echo conf('font_color2'); ?>;
	background-color: <?php echo conf('row_color3'); ?>;
    }
    #sidebar li:active {
        background-color: <?php echo conf('row_color1'); ?>;
    }
    #sidebar li ul {
	float: left;
	position: absolute;	
	width: 12em; 
	margin: -1.5em 0 0 10em;  
/* -2em 0 0 10em for vertical menu puts the submenu back up and to the right of the hovered menu item by "the right amount"*/
	left: -999em;  /* this puts the submenu item way off to the left until it's called back by a hover (below) */
    }
    #sidebar li:hover ul, #sidebar li.sfhover ul {
	left: auto;  /* this calls the submenu back when the parent li is hovered. */
    }
/**
 * End Div Definitions
 * This is the end of the main structure def's
 */
-->
</style>
