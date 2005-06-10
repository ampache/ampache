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
body {
  background: <?php echo conf('bg_color1'); ?>;
  font-family: <?php echo conf('font') ?>;
  font-size: <?php echo conf('font_size'); ?>px;
  color: <?php echo conf('font_color2'); ?>;
  margin: 0;
  padding: 0;
  }

p {
  color: <?php echo conf('font_color2'); ?>;
  font-family: <?php echo conf('font')?>;
  font-size: <?php echo conf('font_size'); ?>px;
  }

a {
  color: <?php echo conf('font_color2') ?>;
  font-family: <?php echo conf('font')?>;
  }

a:visited {
  color: <?php echo conf('font_color2') ?>;
  font-family: <?php echo conf('font')?>;
  }

a:active {
  color: <?php echo conf('font_color2') ?>;
  font-family: <?php echo conf('font')?>;
  }

.text-box {
  padding-left:5px;
  padding-top:5px;
  margin-bottom:10px;
  background-color: <?php echo conf('base_color1'); ?>;
  border-right:2px solid <?php echo conf('bg_color2'); ?>;
  border-bottom:2px solid <?php echo conf('bg_color2'); ?>;
  border-left:2px solid <?php echo conf('bg_color2'); ?>;
  border-top:2px solid <?php echo conf('bg_color2'); ?>;
  }

table.tabledata {
  }

td {
  padding: 0px 8px 0px 8px;
  color: <?php echo conf('font_color2') ?>;
  font-family: <?php echo conf('font')?>;
  font-size: <?php echo conf('font_size'); ?>px;
  }

th {
  padding-right: 10px;
  }

input {
  color: <?php echo conf('font_color2'); ?>;
  font-family: <?php echo conf('font'); ?>;
  font-size: <?php echo conf('font_size'); ?>px;
  font-weight: bold;
  background-color: <?php echo conf('base_color2') ?>;
  margin: 2px 2px 2px 2px;
  }

select {
  color: <?php echo conf('font_color2'); ?>;
  font-family: <?php echo conf('font')?>;
  font-size: <?php echo conf('font_size'); ?>px;
  background-color: <?php echo conf('base_color2'); ?>;
  }

textarea {
  background-color: <?php echo conf('base_color2'); ?>;
  color: <?php echo conf('font_color2') ?>;
  font-family: <?php echo conf('font')?>;
  font-size: <?php echo conf('font_size'); ?>px;
  }

.table-header {
  background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat-x;
  vertical-align: top;
  }
/*************** Main Menu *****************/
#mainmenu {
  width: 100%;
  background: #ddd;
  margin: 0px 0 0px 0;
  padding: 15px 10px 5px;
  list-style: none;
  border-bottom: 1px solid #000;
  }

#mainmenu li {
  margin-left: 5px;
  margin-bottom: 0;
  padding: 2px 10px 5px;
  border: 1px solid #000;
  display: inline;
  background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat;
  font-weight: bold;
  }

#mainmenu li:hover {
  background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2')?> repeat;
  }

#mainmenu li.active {
  background: #fff;
  border-bottom: 2px solid #fff;
  }

#mainmenu a {
  text-decoration: none;
  }

#mainmenu a:hover {
  color: #fff;
  }

#mainmenu a.active {
  color: #00a;
  }
/*************** END Main Menu *************/
/*************** Admin Menu *************/
#adminmenu {
  background: #fff;
  margin: 0px 0 20px 0;
  padding: 15px 10px 5px;
  list-style: none;
  border-bottom: 1px solid #000;
  }

#adminmenu li {
  margin-left: 5px;
  margin-bottom: 0;
  padding: 2px 10px 5px;
  border: 1px solid #000;
  display: inline;
  background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-light-bg.gif) <?php echo conf('base_color2')?> repeat;
  font-weight: bold;
  }

#adminmenu li:hover {
  background: url(<?php echo conf('web_path'); ?><?php echo conf('theme_path'); ?>/images/ampache-dark-bg.gif) <?php echo conf('base_color2')?> repeat;
  }

#adminmenu li.active {
  background: #fff;
  border-bottom: 2px solid #fff;
  }

#adminmenu a {
  text-decoration: none;
  }

#adminmenu a:hover {
  color: #fff;
  }

#adminmenu a.active {
  color: #00a;
  }
/*************** END Admin Menu *************/
/*************** Page Header *********************/
#pageheader {
  display: block;
  background: #ddd;
  }
/*************** END Page Header *****************/

.header1 {
  color: <?php echo conf('font_color2'); ?>;
  font-family: <?php echo conf('font'); ?>;
  font-size: <?php echo conf('font_size') + 6; ?>px;
  font-weight: 900;
  }

.header2 {
  color: <?php echo conf('font_color2'); ?>;
  font-family: <?php echo conf('font'); ?>;
  font-size: <?php echo conf('font_size') + 2; ?>px;
  font-weight: 900;
  }

.headrow {
  background:<?php echo conf('row_color1'); ?>;
  font-size: <?php echo conf('font_size'); ?>px;
  }

.odd {
  background:<?php echo conf('row_color2'); ?>;
  font-size: <?php echo conf('font_size'); ?>px;
  }

.even {
  background:<?php echo conf('row_color3'); ?>;
  font-size: <?php echo conf('font_size'); ?>px;
}

.blank {
  background: #fff;
  }

.border {
  background:<?php echo conf('bg_color2'); ?>;
  }

.header {
  font-size: <?php echo conf('font_size'); ?>px;
  }

.error {
  color: <?php echo conf('error_color'); ?>; 
  }

.fatalerror {
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

.disabled {
  text-decoration: line-through;
  }

.alphabet {
  font-size: <?php echo conf('font_size'); ?>px;
  font-weight: normal;
  }

-->
</style>

