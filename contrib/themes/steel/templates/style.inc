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

table.login
{
  padding: 5px 5px 5px 5px;
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
  border-style: solid;
  border-width: 1px;
  border-color: <?php echo conf('bg_color2'); ?>;
  margin: 3px 3px 3px 3px;
}
select	{ 
  color: <?php echo conf('font_color2'); ?>; 
  font-family: <?php echo conf('font')?>; 
  font-size: <?php echo conf('font_size'); ?>px; 
  background-color: <?php echo conf('base_color2') ?>; 
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
  background: #607080;
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
  font-weight: bold; 
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

/*************** Main Menu *****************/

#mainmenu {
  float: left;
  width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
  border: 1px solid <?php echo conf('bg_color2'); ?>;
  background: #283848;
}

#mainmenu li {
  float: left;
  margin: 0;
  padding: 0 10px 0 10px;
  border-right: 1px solid <?php echo conf('bg_color2'); ?>;
  display: inline;
  background: #283848;
}

#mainmenu li.active {
  background: #405060;
}

#mainmenu a {
  text-decoration: none;
}

#mainmenu a:hover {
  text-decoration: underline;
}

#mainmenu a:active {
}

/*************** END Main Menu *************/

/*************** Admin Menu *************/

#adminmenu {
  float: left;
  width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
  border: 1px solid <?php echo conf('bg_color2'); ?>;
  background: #283848;
}

#adminmenu li {
  float: left;
  margin: 0;
  padding: 0 10px 0 10px;
  border-right: 1px solid <?php echo conf('bg_color2'); ?>;
  display: inline;
  background: #283848;
}

#adminmenu li.active {
  background: #405060;
}

#adminmenu a {
  text-decoration: none;
}

#adminmenu a:hover {
  text-decoration: underline;
}

#adminmenu a:active {
}

/*************** END Main Menu *************/

/*************** Page Header *********************/

#pageheader {
}

/*************** END Page Header *****************/

-->
</style>
