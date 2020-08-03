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
                if (Core::get_global('user')->playlist) {
                    $count_temp_playlist = count(Core::get_global('user')->playlist->get_items());
                }
            } ?>
        <div id="footer" class="<?php echo(($count_temp_playlist || AmpConfig::get('play_type') == 'localplay') ? '' : 'footer-wild'); ?>">
        <?php if (AmpConfig::get('show_donate')) { ?>
            <a id="donate" href="//ampache.github.io/donate.html" title="<?php echo T_('Donate'); ?>" target="_blank"><?php echo T_('Donate'); ?></a> |
        <?php
        } ?>
        <?php
        if (AmpConfig::get('custom_text_footer')) {
            echo AmpConfig::get('custom_text_footer');
        } else { ?>
            <a id="ampache_link" href="https://github.com/ampache/ampache#readme" target="_blank" title="<?php echo T_('Copyright'); ?> © 2001 - 2020 Ampache.org"><?php echo T_('Ampache') . ' ' . AmpConfig::get('version'); ?></a>
        <?php
        } ?>
        </div>
        <?php if (AmpConfig::get('ajax_load') && (!isset($_SESSION['login']) || !$_SESSION['login'])) { ?>
        <div id="webplayer-minimize">
          <a href="javascript:TogglePlayerVisibility();"><?php echo UI::get_icon('minimize', T_('Show/Hide Player')); ?></a>
        </div>
        <div id="webplayer"></div>
        <?php
            require_once AmpConfig::get('prefix') . UI::find_template('uberviz.inc.php');
        } ?>
    </body>
</html>
