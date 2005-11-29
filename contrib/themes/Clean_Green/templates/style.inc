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
$border2="1pt solid Silver";
?>
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
	font-weigth:bolder;
}
a:link {text-decoration: none;}
a:visited {text-decoration: none;}
a:hover {text-decoration: underline;}
a:active {text-decoration: underline;}

img{border: 2px outset silver;}
p img{border: none;}/*no border for ampache logo */
.text-box{border: thin outset;}
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
.table-header td{border: <?php echo $border2;?>;}/*override td and add border on all fields.

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
	border: <?php echo $border;?>;	
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
/*************** Page Header *********************/
    #pageheader {
        background: <?php echo conf('bgcolor1');?>;
    	
    }
    #pageheader img{border: none;}
/*************** END Page Header *****************/
-->
</style>
