<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Search Bar
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

?>
<div id="sb_Subsearch">
        <form name="search" method="post" action="<?php echo $web_path; ?>/search.php" enctype="multipart/form-data" style="Display:inline">
        <input type="text" name="search_string" id="searchString"/>
        <input type="hidden" name="action" value="quick_search" />
        <input type="hidden" name="method" value="fuzzy" />
        <input type="hidden" name="object_type" value="song" />
        <input class="button" type="submit" value="<?php echo _('Search'); ?>" id="searchBtn" />
      	<a href="<?php echo $web_path; ?>/search.php" class="button" id="advSearchBtn"><?php echo _('Advanced Search'); ?></a>
        </form>
</div>

