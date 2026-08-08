<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// footer.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Gui\Partial\VisualizerView;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

?>
                <?php echo Ui::material_symbol_sprite(); ?>
                </div>
                <div style="clear:both;">
                </div>
            </div>
        </div> <!-- end id="maincontainer"-->
        <?php
            $has_temp_playlist = false;
if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    if (!empty(Core::get_global('user')) && Core::get_global('user')->playlist) {
        $has_temp_playlist = Core::get_global('user')->playlist->has_items();
    }
} ?>
        <div id="footer" class="<?php echo(($has_temp_playlist || AmpConfig::get('play_type') == 'localplay') ? '' : 'footer-wild'); ?>">
        <?php if (AmpConfig::get('show_donate')) { ?>
            <a id="donate" href="//ampache.org/donate.html" title="<?php echo T_('Donate'); ?>" target="_blank"><?php echo T_('Donate'); ?></a> |
        <?php } ?>
        <?php if (AmpConfig::get('custom_text_footer')) {
            echo AmpConfig::get('custom_text_footer');
        } else { ?>
            <a id="ampache_link" href="https://github.com/ampache/ampache#readme" target="_blank" title="<?php echo T_('Copyright'); ?> © Ampache.org, 2001-2026"><?php echo T_('Ampache') . ' ' . AmpConfig::get('version'); ?></a>
        <?php } ?>
        </div>
        <?php if (!isset($_SESSION['login']) || !$_SESSION['login']) { ?>
        <div id="webplayer-minimize">
          <a href="javascript:TogglePlayerVisibility();"><?php echo Ui::get_material_symbol('dock_to_bottom', T_('Show/Hide Player')); ?></a>
        </div>
        <div id="webplayer"></div>
        <?php echo (new VisualizerView())->render();
        } ?>
        <div id="mobile-nav-backdrop" onclick="CloseMobileNav();"></div>
        <script>
            // Off-canvas sidebar drawer for small screens (<=768px). The
            // temp-playlist is a plain slideDown dropdown (ToggleRightbarVisibility),
            // so it needs no class here.
            function ToggleMobileSidebar() {
                document.body.classList.toggle('sidebar-open');
            }
            function CloseMobileNav() {
                document.body.classList.remove('sidebar-open');
            }
            // Rightbar submenus (add-to-playlist / random items) are click-toggled at
            // every screen size: click the trigger to open/close, click elsewhere or
            // pick an item to close. (Hover-only menus vanish on mouse-out and are
            // unreliable on touch.) Delegated on document because the rightbar is
            // re-rendered by AJAX. Guarded because pages without a rightbar (the login
            // form) render this footer without ever loading jQuery.
            if (typeof jQuery !== 'undefined') {
                $(document).on('click', '#rightbar li', function (e) {
                    if (!$(this).children('.submenu').length) return;
                    if ($(e.target).closest('.submenu').length) return; // taps inside = selections
                    var wasOpen = $(this).hasClass('submenu-open');
                    $('#rightbar li.submenu-open').removeClass('submenu-open');
                    if (!wasOpen) $(this).addClass('submenu-open');
                });
                $(document).on('click', '#rightbar .submenu a', function () {
                    $('#rightbar li.submenu-open').removeClass('submenu-open');
                });
                $(document).on('click', function (e) {
                    if (!$(e.target).closest('#rightbar').length) {
                        $('#rightbar li.submenu-open').removeClass('submenu-open');
                    }
                });
            }
        </script>
        <?php echo Ui::material_symbol_sprite(); ?>
    </body>
</html>
