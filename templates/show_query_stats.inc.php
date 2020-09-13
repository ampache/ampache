<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>
<?php if (AmpConfig::get('show_footer_statistics')) { ?>
    <br />
    <span class="query-count">
    <?php echo T_('Queries: '); ?><?php echo Dba::$stats['query']; ?>
    | <?php echo T_('Cache Hits: '); ?><?php echo database_object::$cache_hit;
    $load_time_end = microtime(true);
    $load_time     = number_format(($load_time_end - AmpConfig::get('load_time_begin')), 4); ?>
    | <?php echo T_('Load Time: '); ?><?php echo $load_time; ?>
    </span>
<?php
} ?>
