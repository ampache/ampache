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

// mini.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Ui;
use Ampache\Plugin\PluginDisplayHomeInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\User;

$web_path   = AmpConfig::get_web_path();
$site_lang  = AmpConfig::get('lang', 'en_US');
$site_title = scrub_out(AmpConfig::get('site_title'));
$htmllang   = str_replace("_", "-", $site_lang);
$user       = Core::get_global('user');
$logo_url   = Ui::get_logo_url();

$_SESSION['login'] = false;

$t_logout = T_('Log out'); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>" dir="<?php echo is_rtl($site_lang) ? 'rtl' : 'ltr'; ?>">
    <head>
        <!-- Propelled by Ampache | ampache.org -->
        <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=<?php echo AmpConfig::get('site_charset', 'UTF-8'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $site_title; ?></title>

        <?php require_once Ui::find_template('stylesheets.inc.php'); ?>
        <?php require_once Ui::find_template('scripts.inc.php'); ?>

        <?php $iframed = true;
require_once Ui::find_template('show_html5_player_headers.inc.php'); ?>
<style>
    /* The mini player is theme independent; it borrows the theme colours but none of the shell
       geometry, so it never inherits the desktop only min-width that forces mobile browsers to zoom. */
    body#mini-page {
        min-width: 0;
        margin: 0;
        padding: 0 0 130px;
        overflow-x: hidden;
    }

    #mini-header {
        position: sticky;
        top: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        box-sizing: border-box;
        /* Opaque so content scrolls behind it instead of showing through. Themes set their own
           background on body, so inherit picks up the right colour in light and dark. */
        background-color: inherit;
        border-bottom: 1px solid rgba(128, 128, 128, 0.35);
    }

    #mini-header #mini-logo img {
        display: block;
        height: 36px;
        width: auto;
    }

    #mini-header #mini-title {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 16px;
    }

    #mini-header a.mini-action {
        display: inline-flex;
        align-items: center;
        flex: 0 0 auto;
        padding: 4px;
        text-decoration: none;
    }

    #mini-content {
        box-sizing: border-box;
        padding: 10px;
    }

    /* The plugin widgets are built for the fixed width desktop content box; let them fill the page. */
    #mini-content .box,
    #mini-content .home_plugin,
    #mini-content #home_plugin > div {
        max-width: 100%;
        box-sizing: border-box;
    }

    #mini-content table.tabledata {
        display: block;
        overflow-x: auto;
    }

    /* This page is a dead end, so plain links do nothing. Render them as text in both states, no
       hover colour and no pointer, or they advertise themselves as clickable. The play, rating and
       flag buttons are javascript: hrefs and keep their normal styling. */
    #mini-content a:not([href^="javascript"]),
    #mini-content a:not([href^="javascript"]):hover,
    #mini-content a:not([href^="javascript"]):focus,
    #mini-content a:not([href^="javascript"]):active {
        color: inherit;
        text-decoration: none;
        cursor: default;
    }

    /* Same for the player's own album, artist, title, lyrics and shout buttons, which are
       javascript:NavigateTo(...) hrefs. Dimmed and click through disabled so they read as inert
       next to the play controls, which stay live. */
    #mini-page a[href*="NavigateTo"] {
        opacity: 0.35;
        pointer-events: none;
        cursor: default;
    }

    /* Loading indicator sits at the right of the header, padded clear of the logout button so it
       never lands on top of it. It's display:none until an ajax call runs. */
    #mini-page #ajax-loading {
        position: fixed;
        top: 14px;
        right: 60px;
        left: auto;
        transform: none;
        z-index: 10006;
        text-align: right;
    }

    /* Keep the now playing art inside the player bar. On short viewports the skin's art is taller
       than the 100px bar, so it pokes out the top and sits over the page content. */
    #mini-page #webplayer .jp-jplayer,
    #mini-page #webplayer .playing_art {
        max-height: 80px;
        max-width: 80px;
        overflow: hidden;
    }

    #mini-page #webplayer .jp-jplayer img,
    #mini-page #webplayer .playing_art img {
        max-height: 80px;
        max-width: 80px;
        width: auto;
        height: auto;
    }

    @media (max-width: 768px) {
        body#mini-page {
            padding-bottom: 110px;
        }

        #mini-header {
            padding: 6px 8px;
        }

        #mini-header #mini-logo img {
            height: 28px;
        }

        #mini-content {
            padding: 5px;
        }

    }

    @media (max-width: 600px) {
        #mini-page #jp_container_1.jp-audio .jp-jplayer,
        #mini-page #webplayer .playing_art {
            display: none;
        }

        #mini-page #jp_container_1 .jp-interface {
            padding-left: 8px;
        }
    }
</style>
    </head>
    <body id="mini-page">
        <div id="rfc3514" style="display:none;">0x0</div>
        <div id="reloader" style="display:none;"></div>
        <div id="notification" class="notification-out"><?php echo Ui::get_material_symbol('info', T_('Information')); ?><span id="notification-content"></span></div>
        <div id="ajax-loading"><?php echo T_('Loading'); ?> . . .</div>

        <div id="mini-header">
            <span id="mini-logo">
                <img src="<?php echo $logo_url; ?>" title="<?php echo $site_title; ?>" alt="<?php echo $site_title; ?>">
            </span>
            <span id="mini-title"><?php echo $site_title; ?></span>
            <a class="mini-action nohtml" target="_top" href="<?php echo $web_path; ?>/logout.php?session=<?php echo Session::get(); ?>" title="<?php echo $t_logout; ?>"><?php echo Ui::get_material_symbol('logout', $t_logout); ?></a>
        </div>

        <div id="mini-content">
            <div id="home_plugin" style="display:flex;flex-direction:column;">
<?php if ($user instanceof User) {
    foreach (Plugin::get_plugins(PluginTypeEnum::HOMEPAGE_WIDGET) as $plugin_name) {
        $plugin = new Plugin($plugin_name);
        if ($plugin->_plugin instanceof PluginDisplayHomeInterface && $plugin->load($user)) {
            $plugin->_plugin->display_home();
        }
    }
} ?>
            </div> <!-- Close home_plugin Div -->
        </div> <!-- Close mini-content Div -->

        <div id="util_div" style="display:none;"></div>
        <iframe name="util_iframe" id="util_iframe" style="display:none;" src="<?php echo $web_path; ?>/util.php"></iframe>

        <div id="webplayer-minimize">
          <a href="javascript:TogglePlayerVisibility();"><?php echo Ui::get_material_symbol('dock_to_bottom', T_('Show/Hide Player')); ?></a>
        </div>
        <div id="webplayer"></div>
        <?php require_once Ui::find_template('uberviz.inc.php'); ?>
        <script>
            // The mini player is a dead end: tiles play, they don't navigate. src/js/ajax.js delegates
            // link clicks on <body> and turns any in-site href into a hash page load, so catch them
            // first on #mini-content (a descendant fires before body) and stop them there. Play and
            // add buttons carry an onclick and are left alone, matching the ajax.js skip rules.
            $('#mini-content').on('click', 'a', function (e) {
                var link = $(this).attr('href');
                if (typeof $(this).attr('onclick') !== 'undefined' || $(this).hasClass('nohtml')) {
                    return;
                }
                if (typeof link === 'undefined' || link === '' || link === '#' || /^(?:javascript|data|vbscript):/i.test(link)) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
            });

            // The player builds its own album, album disk, artist, title, lyrics and shout links as
            // javascript:NavigateTo(...) hrefs, so the handler above skips them by design and they
            // would still send you off the page. NavigateTo is only reachable from those links here,
            // so neutering it locks the lot. Runs on ready because main.js is a deferred module that
            // publishes NavigateTo onto window after this inline script has already been parsed.
            $(function () {
                window.NavigateTo = function () {
                    return false;
                };
            });
        </script>
    </body>
</html>
