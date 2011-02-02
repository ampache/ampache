<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*
 * Show Box Top
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
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

