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
	
	There is an unfortunate amount of repeating directives in here in an effort to 
	normalize the varying styles/classes used throughout the site and to make as cross
	browswer compatible as possible.  If someone actually knew what they were doing 
	with css, I'm sure this could be done much more effeciently...
*/
$border2="1px solid Silver";
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
body{
	font-family: Arial,Helvetica,sans-serif;
   	color: <?php echo conf('font_color1'); ?>;
	font-size: <?php echo conf('font_size'); ?>px;
}
p{
    font-family: Arial,Helvetica,sans-serif;
   	color: <?php echo conf('font_color1'); ?>;
	font-size: <?php echo conf('font_size'); ?>px;
}
ol{list-style:decimal;}
ul{list-style:disc;}
a{
	color: <?php echo conf('font_color2'); ?>;
	font-weight:bolder;
}
a:link {text-decoration: none;}
a:visited {text-decoration: none;}
a:hover {text-decoration: underline;}
a:active {text-decoration: underline;}

img{border: thin outset Silver;}
p img{border:none;}/*no border on login page logo.*/
.text-box{
	border: thin outset; 
	display: table-cell;
}
H3{font-size: <?php echo conf('font_size')+2; ?>px;}
TABLE{
	border-collapse: separate;
	empty-cells:show;
}
td{color: <?php echo conf('font_color3'); ?>;padding: 0px 8px 0px 8px;}
th{
	border: <?php echo $border2;?>;
	padding: 0px 8px 0px 8px;
	vertical-align: top;
	text-align: center;
	font-weight: normal;/*override default bolding on th's*/
}
input{
	color: <?php echo conf('font_color3'); ?>;
	font-size: <?php echo conf('font_size'); ?>px;
}
select{ 
	color: <?php echo conf('font_color3'); ?>;
	font-size: <?php echo conf('font_size'); ?>px;
}
textarea{ 
	color: <?php echo conf('font_color3'); ?>;
	font-size: <?php echo conf('font_size'); ?>px;
}
.login{border: 2px ridge Silver;}
.npsong td{border-bottom: <?php echo $border2;?>;border-top: <?php echo $border2;?>;}
.tabledata{border-collapse: collapse;border: <?php echo $border2;?>;}
.table-header{
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif)  <?php echo conf('base_color2')?> repeat-x;
	vertical-align: top;
	text-align: center;
	border: <?php echo $border2;?>;
}
.table-header td{border: <?php echo $border2;?>;}/*override td and add border on all fields.*/

.navitem{/*compatiblity with older version*/
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif)  <?php echo conf('base_color2')?> repeat-x;
	vertical-align: top;
	text-align: center;
	border: <?php echo $border2;?>;
}
.active_navitem{/*compatiblity with older version*/
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('bg_color1')?> repeat-x;
	vertical-align: top;
	text-align: center;
	border: <?php echo $border2;?>;
}
.header1{ 
	color:  <?php echo conf('font_color2'); ?>;
	font-family: <?php echo conf('font'); ?>; 
	font-size: <?php echo conf('font_size') + 3; ?>px; 
	font-weight: 900;
}
.header2{
    	color: <?php echo conf('font_color2'); ?>;
	font-family: <?php echo conf('font'); ?>;
	font-size: <?php echo conf('font_size') + 2; ?>px;
	font-weight: 900;
}

.headrow {
	background:<?php echo conf('row_color3'); ?>;
	font-size: <?php echo conf('font_size'); ?>px;
	border: <?php echo $border2;?>;	
}

.odd {/*odd rows in multi lined tables.*/
	background: <?php echo conf('row_color1'); ?>;
	border:none;
	text-align: left;
}

/*do the borders for them.  we'll just do edges on odds except for first/last rows where we add the top or bottom.*/
.odd td {border:none;}
.odd td:first-child{border-left: <?php echo $border2;?>}
.odd td:last-child{border-right: <?php echo $border2;?>;}
.odd:first-child td{border-top: <?php echo $border2;?>}
.odd:last-child td{border-bottom:<?php echo $border2;?>}
/*special cases*/
.odd td table tr td{border: none;}/*skip embedded tables*/
.odd td table tr td:first-child{border:none;}
.odd td table tr td:last-child{border:none;}
.odd td table td table{border:<?php echo $border2;?>;}/*color swatches on preferences page*/


.even{
        background:<?php echo conf('row_color2'); ?>;
        text-align: left;/*for lists on homepage*/
        border:none;
}
/*specify sides/top & bottom on evens*/
.even td{border-bottom:<?php echo $border2;?>;border-top:<?php echo $border2;?>;}
.even td:first-child{border-bottom:<?php echo $border2;?>;border-left: <?php echo $border2;?>;border-top: <?php echo $border2;?>;}
.even td:last-child{border-bottom:<?php echo $border2;?>;border-top:<?php echo $border2;?>;border-right:<?php echo $border2;?>;}
/*special cases*/
.even td table tr td{border:none;}/*skip embedded tables*/
.even td table tr td:first-child{border:none;}
.even td table tr td:last-child{border:none;}
.even td table td table{border:<?php echo $border2;?>;}/*color swatches on preferences page*/



.border{
	border-collapse: collapse;
	border: <?php echo $border2;?>;
	padding: 0px 0px 0px 0px;
}

/*do some clean up of unwanted borders in border class with embedded even/odd classes */
.border .even table .even td{border:none;}/*random selection on home page*/
.border .even td table tr td{border:none;}/*prev next on artists/albums*/
.border .even td table tr td:first-child{border:none;}/*prev next on artists/albums*/
.border .even td table tr td:last-child{border:none;}/*prev next on artists/albums*/
.border .odd td table td{border:none;}/*skip embedded tables.*/
.border .odd td table td:first-child{border:none;}
.border .odd td table td:last-child{border:none;}
.border .table-header:first-child td font{/*override with defaults because  album header is hardcoded to big*/
	font-size: <?php echo conf('font_size'); ?>px;
	font-weight: bolder;
}

.blank{	background: #fff;}
.header{font-size: <?php echo conf('font_size'); ?>px;}
.error{	color: <?php echo conf('error_color'); ?>;}
.fatalerror{
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
.disabled{text-decoration: line-through;}
.alphabet{
	font-size: <?php echo conf('font_size'); ?>px; 
	font-weight: normal; 
}
/*************** Main Menu *****************/
#mainmenu {
        float: left;
        width: 100%;
        margin: 0;
        padding: 0;
        list-style: none;
	border-top: <?php echo $border2;?>;  
        border-bottom: <?php echo $border2;?>;
        border-right: <?php echo $border2;?>;
        border-left: <?php echo $border2;?>;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu li {
        float: left;
        margin: 0;
        padding: 0 10px 0 10px;
        border-right: <?php echo $border2;?>;
        display: inline;
    }
    #mainmenu li.active {
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2');?> repeat-x;
    }
    #mainmenu a {
        text-decoration: none;
    }
    #mainmenu a:hover {
        color: #000;
        text-decoration: underline;
    }
    #mainmenu a:active {
        color: #00a;
        text-decoration: underline;
    }
/*************** END Main Menu *************/
/*************** Admin Menu *************/
    #adminmenu {
    	float: left;
    	width: 100%;
        margin: 0;
        padding: 0;
        list-style: none;
        border-bottom: <?php echo $border2;?>;
        border-right: <?php echo $border2;?>;
		border-left: <?php echo $border2;?>;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat;
    }
    #adminmenu li {
	float: left;
        margin: 0;
        padding: 0 10px 0 10px;
	border-right: <?php echo $border2;?>;
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
        text-decoration: underline;
    }
    #adminmenu a:active {
        color: #000;
        text-decoration: underline;
    }
/*************** END Main Menu *************/
/*New Menu (3.3.2 alpha 4+)*/
/**
 * Div Definitions
 * These define how the page is layed out, be careful with these as changes to them
 * can cause drastic layout changes
 */
	#maincontainer{
			margin: 0px;
	} 	
	#topbar{
			height: 60px;
			padding-top:10px;
			padding-left:10px;
			background-color: <?php echo conf('bg_color1'); ?>;
	}
	#topbarright{
			float: right;
	}
	#topbarleft{
			float: left;
	}
	#topbarleft img{border:none;}/*ampache logo*/
	#navcontainer ul li{
			float:left; 
			width:100%;
	}
	.subnavbutton{
			background-color: <?php echo conf('row_color1'); ?>;
			text-align:center;
			text-decoration: none;
			color: <?php echo conf('font_color2'); ?>;
	}
	#content{ 
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
		border-top: <?php echo $border2;?>;
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
/*        margin: 0 0 1px 0; */
		margin: 0px;
        display: block;   
		border-bottom:<?php echo $border2;?>;
		border-left: <?php echo $border2;?>;
		border-right: <?php echo $border2;?>;
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
		width: 11em; 
		border: <?php echo $border2;?>;
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
/*****/
/*************** Page Header *********************/
    #pageheader {
        background-color: <?php echo conf('bg_color1'); ?>;   
	}
    #pageheader img{border: none;}
/*************** END Page Header *****************/
-->

</style>
