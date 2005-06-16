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
$font1="font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;";
?>
<style type="text/css">
<!--
body{
	<?php echo $font1;?>
	color: <?php echo conf('font_color1'); ?>;
}

p{
	<?php echo $font1;?>
	color: <?php echo conf('font_color1'); ?>;
}
ol{list-style:decimal;}
ul{list-style:disc;}
a{<?php echo $font1;?>font-weight: lighter;color:<?php echo conf('font_color2'); ?>;}
a:link {text-decoration: none;}
a:visited {text-decoration: none;}
a:hover {text-decoration: underline;}
a:active {text-decoration: underline;}
.text-box{border: thin outset;}
TABLE{border-collapse: separate;empty-cells:show;<?php echo $font1;?>}
td{color: <?php echo conf('font_color3'); ?>;padding: 0px 8px 0px 8px;}
th{
	border: <?php echo $border2;?>;
	padding: 0px 8px 0px 8px;
	vertical-align: top;
	text-align: center;
	<?php echo $font1;?>
	font-style: normal;/*overrige default bolding on th*/
}
input{
	color: <?php echo conf('font_color3'); ?>;
	font-size: 10px;
	/*height:10px;*/
}	
select{ 
	color: <?php echo conf('font_color3'); ?>;
	font-size: 10px;
	/*height:10px;*/
}
textarea{ 
	color: <?php echo conf('font_color3'); ?>;
	font-size: 10px;
}
.tabledata{border-collapse: collapse;}
.table-header
{
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif)  <?php echo conf('base_color2')?> repeat-x;
	vertical-align: top;
	text-align: center;
	<?php echo $font1;?>
	border: <?php echo $border2;?>;
}
.table-header td{border: <?php echo $border2;?>;}
.table-header th{border: <?php echo $border2;?>;}
.tabledata .table-header th{border: <?php echo $border2;?>;}

.navitem{/*compatiblity with older stylesheets*/
	background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif)  <?php echo conf('base_color2')?> repeat-x;
	vertical-align: top;
	text-align: center;
	border: <?php echo $border2;?>;
}
.active_navitem{/*compatiblity with older stylesheets*/
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
}

/*do the borders for them.  we'll just do edges on odds except for first/last rows. We repeat stuff in a fruitless effort to make this more cross browser compatible*/
.odd td:{border:none;}
.odd td:first-child{border-bottom: none;border-left: <?php echo $border2;?>;border-top: none;	border-right: none;}
.odd td:last-child{border-bottom: none;border-left: none;border-top: none;border-right: <?php echo $border2;?>;}
.odd:first-child td{border-bottom: none;border-left: none;border-top: <?php echo $border2;?>;border-right: none;}/*first row center tds*/
.odd:first-child td:first-child{border-bottom: none;border-left:<?php echo $border2;?>;border-top:<?php echo $border2;?>;border-right: none;}/*first row left td*/
.odd:first-child td:last-child{border-bottom: none;border-left: none;border-top:<?php echo $border2;?>;border-right:<?php echo $border2;?>;}/*first row right td*/
.odd:last-child td{border-bottom:<?php echo $border2;?>;border-left: none;border-top: none;border-right: none;}/*last row center tds*/
.odd:last-child td:first-child{border-bottom:<?php echo $border2;?>;border-left:<?php echo $border2;?>;border-top: none;border-right: none;}/*last row left td*/
.odd:last-child td:last-child{border-bottom:<?php echo $border2;?>;border-left: none;border-top: none;border-right:<?php echo $border2;?>;}/*last row right td*/
.odd td table td{border:none;}
.odd td table td:first-child{border:none;}/*skip embedded tables.  have to specify firs/last stuff (i think)*/
.odd td table td:last-child{border:none;}
.odd td table td table{border:<?php echo $border2;?>;}/*color swatches on preferences page*/
.tabledata .odd td:{border:none;}/*have to repeat some stuff with tabledata for some browsers :(*/
.tabledata .odd td:first-child{border-bottom: none;border-left: <?php echo $border2;?>;border-top: none;	border-right: none;}
.tabledata .odd td:last-child{border-bottom: none;border-left: none;border-top: none;border-right: <?php echo $border2;?>;}
.tabledata .odd:first-child td{border-bottom: none;border-left: none;border-top: <?php echo $border2;?>;border-right: none;}/*first row center tds*/
.tabledata .odd:first-child td:first-child{border-bottom: none;border-left:<?php echo $border2;?>;border-top:<?php echo $border2;?>;border-right: none;}/*first row left td*/
.tabledata .odd:first-child td:last-child{border-bottom: none;border-left: none;border-top:<?php echo $border2;?>;border-right:<?php echo $border2;?>;}/*first row right td*/
.tabledata .odd:last-child td{border-bottom:<?php echo $border2;?>;border-left: none;border-top: none;border-right: none;}/*last row center tds*/
.tabledata .odd:last-child td:first-child{border-bottom:<?php echo $border2;?>;border-left:<?php echo $border2;?>;border-top: none;border-right: none;}/*last row left td*/
.tabledata .odd:last-child td:last-child{border-bottom:<?php echo $border2;?>;border-left: none;border-top: none;border-right:<?php echo $border2;?>;}/*last row right td*/

.even{
	background:<?php echo conf('row_color2'); ?>;/*#E4E8F2;#DADCE2;#DADFE4;#D5E1E2;#E2DDD5;#E7F7FC;*/
	text-align: left;/*for lists on homepage*/
	<?php echo $font1;?>
}

/*specify sides/top & bottom on evens*/
.even td{border-bottom:<?php echo $border2;?>;border-left: none;border-top:<?php echo $border2;?>;border-right:none;}
.even td:first-child{border-bottom:<?php echo $border2;?>;border-left: <?php echo $border2;?>;border-top: <?php echo $border2;?>;border-right: none;}
.even td:last-child{border-bottom:<?php echo $border2;?>;border-left: none;border-top:<?php echo $border2;?>;border-right:<?php echo $border2;?>;}
.even td table tr td{border:none;}/*skip embedded tables*/
.even td table tr td:first-child{border:none;}
.even td table tr td:last-child{border:none;}
.even td table td table{border:<?php echo $border2;?>;}/*color swatches on preferences page*/
.tabledata .even td{border-bottom:<?php echo $border2;?>;border-left: none;border-top:<?php echo $border2;?>;border-right:none;}/*have to repeat some stuff with tabledata for some browsers :(*/
.tabledata .even td:first-child{border-bottom:<?php echo $border2;?>;border-left: <?php echo $border2;?>;border-top: <?php echo $border2;?>;border-right: none;}
.tabledata .even td:last-child{border-bottom:<?php echo $border2;?>;border-left: none;border-top:<?php echo $border2;?>;border-right:<?php echo $border2;?>;}

.border{
	border-collapse: collapse;
	border: <?php echo $border2;?>;
	padding: 0px 0px 0px 0px;
}
/*do some clean up of unwanted borders in border class with embedded even */
/*.border .even td{border:1pt solid Silver;}*//*border inside catalog stats on home page, and album listing*/
.border .even td{border-bottom:<?php echo $border2;?>;border-left: none;border-top:<?php echo $border2;?>;border-right:none;}
.border .even td:first-child{border-bottom:<?php echo $border2;?>;border-left: <?php echo $border2;?>;border-top: <?php echo $border2;?>;border-right: none;}
.border .even td:last-child{border-bottom:<?php echo $border2;?>;border-left: none;border-top:<?php echo $border2;?>;border-right:<?php echo $border2;?>;}
.border .even table .even td{border:none;}/*random selection on home page*/
.border .even td table tr td{border:none;}/*prev next on artists/albums*/
.border .even td table tr td:first-child{border:none;}/*prev next on artists/albums*/
.border .even td table tr td:last-child{border:none;}/*prev next on artists/albums*/
.border .even td table td{border:none;}/*border around 'from genre' on home*/
.border .odd td:{border:none;}/*repeat some stuff from above for border class.  browser compatability*/
.border .odd td:first-child{border-bottom: none;border-left: <?php echo $border2;?>;border-top: none;	border-right: none;}
.border .odd td:last-child{border-bottom: none;border-left: none;border-top: none;border-right: <?php echo $border2;?>;}
.border .odd td table td{border:none;}
.border .odd td table td:first-child{border:none;}/*skip embedded tables.  have to specify firs/last stuff (i think)*/
.border .odd td table td:last-child{border:none;}

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
		border-top: <?php echo $border2;?>;
        background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat;
    }
    #adminmenu li {
		float: left;
        margin: 0;
        padding: 0 20px 0 20px;
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
/*************** END Page Header *****************/
-->
</style>
