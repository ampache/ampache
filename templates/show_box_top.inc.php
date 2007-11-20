<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
$class = $class ? $class : 'box'; 
?>

<div class="<?php echo $class; ?>">
  <div class="box-inside">
    <div class="box-top">
      <div class="box-left-top"></div>
      <div class="box-right-top"></div>
    </div>
    <?php if ($title) { ?>
	   <h3 class="box-title"><?php echo $title; ?></h3>
	  <?php } ?>
    <div class="box-content clearfix">

