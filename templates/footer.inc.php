<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
                </div>
                <div style="clear:both;">
                </div>
            </div>
        </div> <!-- end id="maincontainer"-->
        <?php
            $count_temp_playlist = 1;
            if (!isset($_SESSION['login']) || !$_SESSION['login']) {
                if ($GLOBALS['user']->playlist) {
                    $count_temp_playlist = count($GLOBALS['user']->playlist->get_items());
                }
            }
        ?>
        <div id="footer" class="<?php echo (($count_temp_playlist || AmpConfig::get('play_type') == 'localplay') ? '' : 'footer-wild'); ?>">
        <?php if (AmpConfig::get('show_donate')) { ?>
            <a id="donate" href="//ampache.github.io/donate.html" title="Donate" target="_blank"><?php echo ".:: " . T_('Donate') . " ::."; ?></a> |
        <?php } ?>
            <a href="https://github.com/ampache/ampache#readme" target="_blank" title="Copyright © 2001 - 2014 Ampache.org">Ampache <?php echo AmpConfig::get('version'); ?></a>
            <br />
            <?php echo T_('Queries:'); ?><?php echo Dba::$stats['query']; ?> <?php echo T_('Cache Hits:'); ?><?php echo database_object::$cache_hit; ?>
        <?php
            $load_time_end = microtime(true);
            $load_time = number_format(($load_time_end - AmpConfig::get('load_time_begin')), 4);
        ?>
            | <?php echo T_('Load time:'); ?><?php echo $load_time; ?>
        </div>
        <?php if (AmpConfig::get('ajax_load') && (!isset($_SESSION['login']) || !$_SESSION['login'])) { ?>
        <div id="webplayer"></div>
        <?php
            require_once AmpConfig::get('prefix') . '/templates/uberviz.inc.php';
        }
        ?>
    </body>
</html>
