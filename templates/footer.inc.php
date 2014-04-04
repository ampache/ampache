<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
 */
?>
<div style="clear:both;"></div>
</div> <!-- end id="content"-->
</div> <!-- end id="maincontainer"-->
<div id="footer">
    <a href="https://github.com/ampache-doped/ampache-doped#readme" target="_blank" title="Copyright © 2013 - 2014 Ampache-doped.github.io
Copyright © 2001 - 2013 Ampache.org">Ampache-doped <?php echo AmpConfig::get('version'); ?></a><br />
    <?php echo T_('Queries:'); ?><?php echo Dba::$stats['query']; ?> <?php echo T_('Cache Hits:'); ?><?php echo database_object::$cache_hit; ?>
<?php
    $load_time_end = microtime(true);
    $load_time = number_format(($load_time_end - AmpConfig::get('load_time_begin')), 4);
?>
    | <?php echo T_('Load time:'); ?><?php echo $load_time; ?>
</div>
</body>
</html>
