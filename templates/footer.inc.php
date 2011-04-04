<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Footer
 *
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
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */
?>
<div style="clear:both;"></div>
<?php if (isset($_SESSION['userdata']['password'])) {?>
	<span class="fatalerror"><?php echo _('Using Old Password Encryption, Please Reset your Password'); ?></span>
<?php } ?>
</div> <!-- end id="content"-->
</div> <!-- end id="maincontainer"-->
<div id="footer">
	<a href="http://www.ampache.org/index.php">Ampache v.<?php echo Config::get('version'); ?></a><br />
	Copyright (c) 2001 - 2011 Ampache.org
	<?php echo _('Queries:'); ?><?php echo Dba::$stats['query']; ?> <?php echo _('Cache Hits:'); ?><?php echo database_object::$cache_hit; ?>
</div>
</body>
</html>
