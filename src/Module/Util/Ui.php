<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Api;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Localplay\LocalPlayTypeEnum;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Repository\MetadataFieldRepositoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Search;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

/**
 * A collection of methods related to the user interface
 */
class Ui implements UiInterface
{
    private static $_ticker;
    private static $_icon_cache;
    private static $_image_cache;

    public function __construct(
        private readonly ConfigContainerInterface $configContainer
    ) {
    }

    /**
     * This dumps out some html and an icon for the type of rss that we specify
     *
     * @param array<string, string>|null $params
     */
    public static function getRssLink(
        RssFeedTypeEnum $type,
        ?User $user = null,
        string $title = '',
        ?array $params = null
    ): string {
        $strparams = "";
        if ($params != null && is_array($params)) {
            foreach ($params as $key => $value) {
                $strparams .= "&" . scrub_out($key) . "=" . scrub_out($value);
            }
        }

        $rsstoken = '';
        if ($user !== null) {
            $rsstoken = "&rsstoken=" . $user->getRssToken();
        }

        $string = '<a class="nohtml" href="' . AmpConfig::get(
            'web_path'
        ) . '/rss.php?type=' . $type->value . $rsstoken . $strparams . '" target="_blank">' . Ui::get_material_symbol(
            'rss_feed',
            T_('RSS Feed')
        );
        if (!empty($title)) {
            $string .= ' &nbsp;' . $title;
        }
        $string .= '</a>';

        return $string;
    }

    /**
     * find_template
     *
     * Return the path to the template file wanted. The file can be overwritten
     * by the theme if it's not a php file, or if it is and if option
     * allow_php_themes is set to true.
     * @param string $template
     */
    public static function find_template($template, bool $extern = false): string
    {
        $path      = AmpConfig::get('theme_path') . '/templates/' . $template;
        $realpath  = __DIR__ . '/../../../public/' . $path;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (($extension != 'php' || AmpConfig::get('allow_php_themes')) && file_exists($realpath) && is_file($realpath)) {
            return $path;
        } else {
            if ($extern === true) {
                return '/templates/' . $template;
            }

            return __DIR__ . '/../../../public/templates/' . $template;
        }
    }

    public function showObjectNotFound(): void
    {
        $this->showHeader();
        echo T_('You have requested an object that does not exist');
        $this->showQueryStats();
        $this->showFooter();
    }

    /**
     * Displays the default error page
     */
    public function accessDenied(string $error = 'Access Denied'): void
    {
        // Clear any buffered crap
        ob_end_clean();
        header("HTTP/1.1 403 $error");
        require_once self::find_template('show_denied.inc.php');
    }

    /**
     * Displays an error page when you can't write the config
     */
    public function permissionDenied(string $fileName): void
    {
        // Clear any buffered crap
        ob_end_clean();
        header("HTTP/1.1 403 Permission Denied");
        require_once self::find_template('show_denied_permission.inc.php');
    }

    /**
     * ajax_include
     *
     * Does some trickery with the output buffer to return the output of a
     * template.
     */
    public static function ajax_include(string $template): string
    {
        ob_start();
        require self::find_template('') . $template;
        $output = ob_get_contents();
        ob_end_clean();

        return $output ?: '';
    }

    /**
     * check_iconv
     *
     * Checks to see whether iconv is available.
     */
    public static function check_iconv(): bool
    {
        if (function_exists('iconv') && function_exists('iconv_substr')) {
            return true;
        }

        return false;
    }

    /**
     * check_ticker
     *
     * Stupid little cutesie thing to ratelimit output of long-running
     * operations.
     */
    public static function check_ticker(): bool
    {
        if (defined('SSE_OUTPUT') || defined('API')) {
            return false;
        }
        if (!isset(self::$_ticker) || (time() > self::$_ticker + 1)) {
            self::$_ticker = time();

            return true;
        }

        return false;
    }

    /**
     * clean_utf8
     *
     * Removes characters that aren't valid in XML
     * (which is a subset of valid UTF-8, but close enough for our purposes.)
     * See http://www.w3.org/TR/2006/REC-xml-20060816/#charsets
     * @param string $string
     */
    public static function clean_utf8($string): string
    {
        if ($string) {
            $clean = preg_replace(
                '/[^\x{9}\x{a}\x{d}\x{20}-\x{d7ff}\x{e000}-\x{fffd}\x{10000}-\x{10ffff}]|[\x{7f}-\x{84}\x{86}-\x{9f}\x{fdd0}-\x{fddf}\x{1fffe}-\x{1ffff}\x{2fffe}-\x{2ffff}\x{3fffe}-\x{3ffff}\x{4fffe}-\x{4ffff}\x{5fffe}-\x{5ffff}\x{6fffe}-\x{6ffff}\x{7fffe}-\x{7ffff}\x{8fffe}-\x{8ffff}\x{9fffe}-\x{9ffff}\x{afffe}-\x{affff}\x{bfffe}-\x{bffff}\x{cfffe}-\x{cffff}\x{dfffe}-\x{dffff}\x{efffe}-\x{effff}\x{ffffe}-\x{fffff}\x{10fffe}-\x{10ffff}]/u',
                '',
                $string
            );

            if ($clean) {
                return rtrim((string)$clean);
            }

            debug_event(self::class, 'Charset cleanup failed, something might break', 1);
        }

        return '';
    }

    /**
     * format_bytes
     *
     * Turns a size in bytes into the best human-readable value
     * @param $value
     * @param int $precision
     * @param int $pass
     */
    public static function format_bytes($value, $precision = 2, $pass = 0): string
    {
        if (!$value) {
            return '';
        }
        while (strlen((string)floor($value)) > 3) {
            $value /= 1024;
            $pass++;
        }

        switch ($pass) {
            case 1:
                $unit = 'kB';
                break;
            case 2:
                $unit = 'MB';
                break;
            case 3:
                $unit = 'GB';
                break;
            case 4:
                $unit = 'TB';
                break;
            case 5:
                $unit = 'PB';
                break;
            default:
                $unit = 'B';
                break;
        }

        return ((string)round($value, $precision)) . ' ' . $unit;
    }

    /**
     * unformat_bytes
     *
     * Parses a human-readable size
     * @param string|int $value
     * @return string
     * @noinspection PhpMissingBreakStatementInspection
     */
    public static function unformat_bytes($value): string
    {
        if (preg_match('/^([0-9]+) *([[:alpha:]]+)$/', (string)$value, $matches)) {
            $value = (int)$matches[1];
            $unit  = strtolower(substr($matches[2], 0, 1));
        } else {
            return (string)$value;
        }

        switch ($unit) {
            case 'p':
                $value *= 1024;
                // Intentional break fall-through
            case 't':
                $value *= 1024;
                // Intentional break fall-through
            case 'g':
                $value *= 1024;
                // Intentional break fall-through
            case 'm':
                $value *= 1024;
                // Intentional break fall-through
            case 'k':
                $value *= 1024;
                // Intentional break fall-through
        }

        return (string)$value;
    }

    /**
     * get_icon
     *
     * Returns an <img> or <svg> tag for the specified icon
     */
    public static function get_icon(string $name, ?string $title = null, ?string $id_attrib = null, ?string $class_attrib = null): string
    {
        $title    = $title ?? T_(ucfirst($name));
        $icon_url = self::_find_icon($name);
        $icontype = pathinfo($icon_url, PATHINFO_EXTENSION);
        $tag      = '';
        if ($icontype == 'svg') {
            // load svg file
            $svgicon = simplexml_load_file($icon_url);
            if ($svgicon !== false) {
                if (empty($svgicon->title)) {
                    $svgicon->addChild('title', $title);
                } else {
                    $svgicon->title = $title;
                }
                if (empty($svgicon->desc)) {
                    $svgicon->addChild('desc', $title);
                } else {
                    $svgicon->desc = $title;
                }

                if (!empty($id_attrib)) {
                    $svgicon->addAttribute('id', $id_attrib);
                }
                if (empty($class_attrib)) {
                    $class_attrib = 'icon icon-' . $name;
                }
                $svgicon->addAttribute('class', $class_attrib);

                $tag = explode("\n", (string)$svgicon->asXML(), 2)[1];
            }
        } else {
            // fall back to png
            $tag = '<img src="' . $icon_url . '" ';
            $tag .= 'alt="' . $title . '" ';
            $tag .= 'title="' . $title . '" ';
            if ($id_attrib !== null) {
                $tag .= 'id="' . $id_attrib . '" ';
            }
            if ($class_attrib !== null) {
                $tag .= 'class="' . $class_attrib . '" ';
            }
            $tag .= '/>';
        }

        return $tag;
    }

    /**
     * get_material_symbol
     *
     * Returns an <svg> tag for the specified Material Symbol
     */
    public static function get_material_symbol(string $name, ?string $title = null, ?string $id_attrib = null, ?string $class_attrib = null): string
    {
        $title    = $title ?? T_(ucfirst($name));
        $filepath = __DIR__ . '/../../../public/lib/components/material-symbols/' . $name . '.svg';
        if (!is_file($filepath)) {
            // fall back to error icon if icon is missing
            debug_event(self::class, 'Runtime Error: icon ' . $name . ' not found.', 1);
            $filepath = __DIR__ . '/../../../public/images/icon_error.svg';
        }
        $tag = '';
        // load svg file
        $svgicon = simplexml_load_file($filepath);
        if ($svgicon !== false) {
            if (empty($svgicon->title)) {
                $svgicon->addChild('title', $title);
            } else {
                $svgicon->title = $title;
            }
            if (empty($svgicon->desc)) {
                $svgicon->addChild('desc', $title);
            } else {
                $svgicon->desc = $title;
            }

            if (!empty($id_attrib)) {
                $svgicon->addAttribute('id', $id_attrib);
            }
            if (empty($class_attrib)) {
                $class_attrib = '';
            }
            $svgicon->addAttribute('class', 'material-symbol material-symbol-' . $name . " " . $class_attrib);

            $tag = explode("\n", (string)$svgicon->asXML(), 3)[1];
        }

        return $tag;
    }

    /**
     * _find_icon
     *
     * Does the finding icon thing. match svg first over png
     * @param string $name
     */
    private static function _find_icon($name): string
    {
        if (isset(self::$_icon_cache[$name])) {
            return self::$_icon_cache[$name];
        }

        $path       = AmpConfig::get('theme_path') . '/images/icons/';
        $filesearch = glob(__DIR__ . '/../../../public' . $path . 'icon_' . $name . '.{svg,png}', GLOB_BRACE);

        if (empty($filesearch)) {
            // if the theme is missing an icon, fall back to default images folder
            $path       = 'images/';
            $filesearch = glob(__DIR__ . '/../../../public/' . $path . 'icon_' . $name . '.{svg,png}', GLOB_BRACE);
        }

        if (is_array($filesearch)) {
            $filename = pathinfo($filesearch[0], PATHINFO_BASENAME);
        } else {
            // fall back to error icon if icon is missing
            debug_event(self::class, 'Runtime Error: icon ' . $name . ' not found.', 1);
            $filename = 'images/icon_error.svg';
        }
        $url = AmpConfig::get('web_path') . '/' . $path . $filename;
        // cache the url so you don't need to keep searching
        self::$_icon_cache[$name] = $url;

        return $url;
    }

    /**
     * get_image
     *
     * Returns an <img> or <svg> tag for the specified image
     */
    public static function get_image(string $name, ?string $title = null, ?string $id_attrib = null, ?string $class_attrib = null): string
    {
        $title     = $title ?? ucfirst($name);
        $image_url = self::_find_image($name);
        $imagetype = pathinfo($image_url, PATHINFO_EXTENSION);
        $tag       = '';
        if ($imagetype == 'svg') {
            // load svg file
            $svgimage = simplexml_load_file($image_url);
            if ($svgimage !== false) {
                $svgimage->addAttribute('class', 'image ' . $name);

                if (empty($svgimage->title)) {
                    $svgimage->addChild('title', $title);
                } else {
                    $svgimage->title = $title;
                }
                if (empty($svgimage->desc)) {
                    $svgimage->addChild('desc', $title);
                } else {
                    $svgimage->desc = $title;
                }

                if (!empty($id_attrib)) {
                    $svgimage->addAttribute('id', $id_attrib);
                }

                $class_attrib = ($class_attrib) ?? 'image image-' . $name;
                $svgimage->addAttribute('class', $class_attrib);

                $tag = explode("\n", (string)$svgimage->asXML(), 2)[1];
            }
        } else {
            // fall back to png
            $tag = '<img src="' . $image_url . '" ';
            $tag .= 'alt="' . $title . '" ';
            $tag .= 'title="' . $title . '" ';
            if ($id_attrib !== null) {
                $tag .= 'id="' . $id_attrib . '" ';
            }
            if ($class_attrib !== null) {
                $tag .= 'class="' . $class_attrib . '" ';
            }
            $tag .= '/>';
        }

        return $tag;
    }

    /**
     * _find_image
     *
     * Does the finding image thing. match svg first over png
     * @param string $name
     */
    private static function _find_image($name): string
    {
        if (isset(self::$_image_cache[$name])) {
            return self::$_image_cache[$name];
        }

        $path       = 'themes/' . AmpConfig::get('theme_name') . '/images/';
        $filesearch = glob(__DIR__ . '/../../../public/' . $path . $name . '.{svg,png}', GLOB_BRACE);
        if (empty($filesearch)) {
            $path       = 'images/';
            $filesearch = glob(__DIR__ . '/../../../public/' . $path . $name . '.{svg,png}', GLOB_BRACE);
        }
        if (empty($filesearch)) {
            // if the theme is missing an image. fall back to default images folder
            $filename = $name . '.png';
            $path     = 'images/';
        } else {
            $filename = pathinfo($filesearch[0], PATHINFO_BASENAME);
        }
        $url = AmpConfig::get('web_path') . '/' . $path . $filename;
        // cache the url so you don't need to keep searching
        self::$_image_cache[$name] = $url;

        return $url;
    }

    /**
     * Show the requested template file
     */
    public function show(string $template, array $context = []): void
    {
        extract($context);

        require_once self::find_template($template);
    }

    public function showFooter(): void
    {
        static::show_footer();
    }

    public function showHeader(): void
    {
        require_once self::find_template('header.inc.php');
    }

    /**
     * show_footer
     *
     * Shows the footer template and possibly profiling info.
     *
     * @deprecated use non-static version
     */
    public static function show_footer(): void
    {
        if (!defined("TABLE_RENDERED")) {
            show_table_render();
        }
        $user = Core::get_global('user');
        if ($user instanceof User) {
            $plugins = Plugin::get_plugins(PluginTypeEnum::FOOTER_WIDGET);
            foreach ($plugins as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin !== null && $plugin->load($user)) {
                    $plugin->_plugin->display_on_footer();
                }
            }
        }

        require_once self::find_template('footer.inc.php');
        if (Core::get_request('profiling') !== '') {
            Dba::show_profile();
        }
    }

    public function showBoxTop(string $title = '', string $class = ''): void
    {
        static::show_box_top($title, $class);
    }

    public function showBoxBottom(): void
    {
        static::show_box_bottom();
    }

    /**
     * show_box_top
     *
     * This shows the top of the box.
     * @param string $title
     * @param string $class
     *
     * @deprecated Use non-static version
     */
    public static function show_box_top($title = '', $class = ''): void
    {
        require self::find_template('show_box_top.inc.php');
    }

    /**
     * show_box_bottom
     *
     * This shows the bottom of the box
     *
     * @deprecated Use non-static version
     */
    public static function show_box_bottom(): void
    {
        require self::find_template('show_box_bottom.inc.php');
    }

    /**
     * This shows the query stats
     */
    public function showQueryStats(): void
    {
        require self::find_template('show_query_stats.inc.php');
    }

    public static function show_custom_style(): void
    {
        if (AmpConfig::get('custom_login_background', false)) {
            echo "<style> body { background-position: center; background-size: cover; background-image: url('" . AmpConfig::get('custom_login_background') . "') !important; }</style>";
        }

        if (AmpConfig::get('custom_login_logo', false)) {
            echo "<style>#loginPage #headerlogo, #registerPage #headerlogo { background-image: url('" . AmpConfig::get('custom_login_logo') . "') !important; }</style>";
        }

        $favicon = AmpConfig::get('custom_favicon', false) ?: AmpConfig::get('web_path') . "/favicon.ico";
        echo "<link rel='shortcut icon' href='" . $favicon . "' />\n";
    }

    /**
     * update_text
     *
     * Convenience function that, if the output is going to a browser,
     * blarfs JS to do a fancy update.  Otherwise it just outputs the text.
     * @param string $field
     * @param $value
     */
    public static function update_text($field, $value): void
    {
        if (defined('API')) {
            return;
        }
        if (defined('CLI')) {
            echo $value . "\n";

            return;
        }

        static $update_id = 1;

        if (defined('SSE_OUTPUT')) {
            echo "id: " . $update_id . "\n";
            echo "data: displayNotification('" . json_encode($value) . "', 5000)\n\n";
        } else {
            if (!empty($field)) {
                echo "<script>updateText('" . $field . "', '" . json_encode($value) . "');</script>\n";
            } else {
                echo "<br />" . $value . "<br /><br />\n";
            }
        }

        ob_flush();
        flush();
        $update_id++;
    }

    /**
     * get_logo_url
     *
     * Get the custom logo or logo relating to your theme color
     * @param string $color
     */
    public static function get_logo_url($color = null): string
    {
        if (AmpConfig::get('custom_logo')) {
            return AmpConfig::get('custom_logo');
        }
        if ($color !== null) {
            return AmpConfig::get('web_path') . AmpConfig::get('theme_path') . '/images/ampache-' . $color . '.png';
        }

        return AmpConfig::get('web_path') . AmpConfig::get('theme_path') . '/images/ampache-' . AmpConfig::get('theme_color') . '.png';
    }

    /**
     * @param $type
     */
    public static function is_grid_view($type): bool
    {
        $isgv = true;
        $name = 'browse_' . $type . '_grid_view';
        if (isset($_COOKIE[$name])) {
            $isgv = ($_COOKIE[$name] == 'true');
        }

        return $isgv;
    }

    /**
     * shows a confirmation of an action
     *
     * @param string $title The Title of the message
     * @param string $text The details of the message
     * @param string $next_url Where to go next
     * @param int $cancel T/F show a cancel button that uses return_referer()
     * @param string $form_name
     * @param bool $visible
     */
    public function showConfirmation(
        $title,
        $text,
        $next_url,
        $cancel = 0,
        $form_name = 'confirmation',
        $visible = true
    ): void {
        $webPath = $this->configContainer->getWebPath();

        if (substr_count($next_url, $webPath)) {
            $path = $next_url;
        } else {
            $path = sprintf('%s/%s', $webPath, $next_url);
        }
        $this->show(
            'show_confirmation.inc.php',
            [
                'title' => $title,
                'text' => $text,
                'path' => $path,
                'form_name' => $form_name,
                'cancel' => $cancel
            ]
        );
    }

    /**
     * shows a simple continue button after an action
     */
    public function showContinue(
        string $title,
        string $text,
        string $next_url
    ): void {
        $webPath = $this->configContainer->getWebPath();

        if (substr_count($next_url, $webPath)) {
            $path = $next_url;
        } else {
            $path = sprintf('%s/%s', $webPath, $next_url);
        }

        $this->show(
            'show_continue.inc.php',
            [
                'title' => $title,
                'text' => $text,
                'path' => $path
            ]
        );
    }

    /**
     * This function is used to escape user data that is getting redisplayed
     * onto the page, it htmlentities the mojo
     * This is the inverse of the scrub_in function
     */
    public function scrubOut(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        return htmlentities((string) $string, ENT_QUOTES, AmpConfig::get('site_charset'));
    }

    /**
     * takes the key and then creates the correct type of input for updating it
     */
    public function createPreferenceInput(
        string $name,
        $value
    ): void {
        if (!Preference::has_access($name)) {
            if ($value == '1') {
                echo T_("Enabled");
            } elseif ($value == '0') {
                echo T_("Disabled");
            } else {
                if (preg_match('/_pass$/', $name) || preg_match('/_api_key$/', $name)) {
                    echo "******";
                } else {
                    echo $value;
                }
            }

            return;
        } // if we don't have access to it

        switch ($name) {
            case 'access_control':
            case 'access_list':
            case 'ajax_load':
            case 'album_group':
            case 'album_release_type':
            case 'allow_democratic_playback':
            case 'allow_localplay_playback':
            case 'allow_personal_info_agent':
            case 'allow_personal_info_now':
            case 'allow_personal_info_recent':
            case 'allow_personal_info_time':
            case 'allow_stream_playback':
            case 'allow_upload':
            case 'allow_video':
            case 'api_enable_3':
            case 'api_enable_4':
            case 'api_enable_5':
            case 'api_enable_6':
            case 'api_hide_dupe_searches':
            case 'autoupdate':
            case 'autoupdate_lastversion_new':
            case 'bookmark_latest':
            case 'broadcast_by_default':
            case 'browse_filter':
            case 'browser_notify':
            case 'catalog_check_duplicate':
            case 'catalogfav_gridview':
            case 'condPL':
            case 'cron_cache':
            case 'daap_backend':
            case 'demo_clear_sessions':
            case 'demo_mode':
            case 'demo_use_search':
            case 'direct_link':
            case 'display_menu':
            case 'download':
            case 'force_http_play':
            case 'geolocation':
            case 'hide_genres':
            case 'hide_single_artist':
            case 'home_moment_albums':
            case 'home_moment_videos':
            case 'home_now_playing':
            case 'home_recently_played':
            case 'home_recently_played_all':
            case 'libitem_contextmenu':
            case 'lock_songs':
            case 'mb_overwrite_name':
            case 'no_symlinks':
            case 'notify_email':
            case 'now_playing_per_user':
            case 'perpetual_api_session':
            case 'personalfav_display':
            case 'quarantine':
            case 'ratingmatch_flags':
            case 'ratingmatch_write_tags':
            case 'rio_global_stats':
            case 'rio_track_stats':
            case 'share':
            case 'share_social':
            case 'show_album_artist':
            case 'show_artist':
            case 'show_donate':
            case 'show_header_login':
            case 'show_license':
            case 'show_lyrics':
            case 'show_original_year':
            case 'show_played_times':
            case 'show_playlist_username':
            case 'show_skipped_times':
            case 'show_wrapped':
            case 'show_subtitle':
            case 'sidebar_light':
            case 'song_page_title':
            case 'stream_beautiful_url':
            case 'subsonic_always_download':
            case 'subsonic_backend':
            case 'tadb_overwrite_name':
            case 'topmenu':
            case 'ui_fixed':
            case 'unique_playlist':
            case 'upload':
            case 'upload_allow_edit':
            case 'upload_allow_remove':
            case 'upload_catalog_pattern':
            case 'upload_subdir':
            case 'upload_user_artist':
            case 'upnp_backend':
            case 'use_auth':
            case 'use_original_year':
            case 'use_play2':
            case 'webdav_backend':
            case 'webplayer_aurora':
            case 'webplayer_confirmclose':
            case 'webplayer_flash':
            case 'webplayer_html5':
            case 'webplayer_pausetabs':
            case 'xml_rpc':
                $is_true  = '';
                $is_false = '';
                if ($value == '1') {
                    $is_true = "selected=\"selected\"";
                } else {
                    $is_false = "selected=\"selected\"";
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"1\" $is_true>" . T_('On') . "</option>\n";
                echo "\t<option value=\"0\" $is_false>" . T_('Off') . "</option>\n";
                echo "</select>\n";
                break;
            case 'upload_catalog':
                show_catalog_select('upload_catalog', $value, '', true, 'music');
                break;
            case 'play_type':
                $is_stream     = '';
                $is_localplay  = '';
                $is_democratic = '';
                $is_web_player = '';
                switch ($value) {
                    case 'localplay':
                        $is_localplay = 'selected="selected"';
                        break;
                    case 'democratic':
                        $is_democratic = 'selected="selected"';
                        break;
                    case 'web_player':
                        $is_web_player = 'selected="selected"';
                        break;
                    default:
                        $is_stream = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"\">" . T_('None') . "</option>\n";
                if (AmpConfig::get('allow_stream_playback')) {
                    echo "\t<option value=\"stream\" $is_stream>" . T_('Stream') . "</option>\n";
                }
                if (AmpConfig::get('allow_democratic_playback')) {
                    echo "\t<option value=\"democratic\" $is_democratic>" . T_('Democratic') . "</option>\n";
                }
                if (AmpConfig::get('allow_localplay_playback')) {
                    echo "\t<option value=\"localplay\" $is_localplay>" . T_('Localplay') . "</option>\n";
                }
                echo "\t<option value=\"web_player\" $is_web_player>" . T_('Web Player') . "</option>\n";
                echo "</select>\n";
                break;
            case 'playlist_type':
                $is_m3u        = '';
                $is_simple_m3u = '';
                $is_pls        = '';
                $is_asx        = '';
                $is_ram        = '';
                $is_xspf       = '';
                switch ($value) {
                    case 'simple_m3u':
                        $is_simple_m3u = 'selected="selected"';
                        break;
                    case 'pls':
                        $is_pls = 'selected="selected"';
                        break;
                    case 'asx':
                        $is_asx = 'selected="selected"';
                        break;
                    case 'ram':
                        $is_ram = 'selected="selected"';
                        break;
                    case 'xspf':
                        $is_xspf = 'selected="selected"';
                        break;
                    case 'm3u':
                    default:
                        $is_m3u = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"m3u\" $is_m3u>" . T_('M3U') . "</option>\n";
                echo "\t<option value=\"simple_m3u\" $is_simple_m3u>" . T_('Simple M3U') . "</option>\n";
                echo "\t<option value=\"pls\" $is_pls>" . T_('PLS') . "</option>\n";
                echo "\t<option value=\"asx\" $is_asx>" . T_('Asx') . "</option>\n";
                echo "\t<option value=\"ram\" $is_ram>" . T_('RAM') . "</option>\n";
                echo "\t<option value=\"xspf\" $is_xspf>" . T_('XSPF') . "</option>\n";
                echo "</select>\n";
                break;
            case 'lang':
                $languages = get_languages();
                echo '<select name="' . $name . '">' . "\n";
                foreach ($languages as $lang => $tongue) {
                    $selected = ($lang == $value) ? 'selected="selected"' : '';
                    echo "\t<option value=\"$lang\" " . $selected . ">$tongue</option>\n";
                } // end foreach
                echo "</select>\n";
                break;
            case 'localplay_controller':
                $controllers = array_keys(LocalPlayTypeEnum::TYPE_MAPPING);
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"\">" . T_('None') . "</option>\n";
                foreach ($controllers as $controller) {
                    if (!LocalPlay::is_enabled($controller)) {
                        continue;
                    }
                    $is_selected = '';
                    if ($value == $controller) {
                        $is_selected = 'selected="selected"';
                    }
                    echo "\t<option value=\"" . $controller . "\" $is_selected>" . ucfirst($controller) . "</option>\n";
                } // end foreach
                echo "</select>\n";
                break;
            case 'api_force_version':
                $is_0 = '';
                $is_3 = '';
                $is_4 = '';
                $is_5 = '';
                $is_6 = '';
                if (!in_array($value, Api::API_VERSIONS)) {
                    $is_0 = 'selected="selected"';
                } elseif ($value == 3) {
                    $is_3 = 'selected="selected"';
                } elseif ($value == 4) {
                    $is_4 = 'selected="selected"';
                } elseif ($value == 5) {
                    $is_5 = 'selected="selected"';
                } elseif ($value == 6) {
                    $is_6 = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "<option value=\"0\" $is_0>" . T_('Off') . "</option>\n";
                echo "<option value=\"3\" $is_3>" . T_('Allow API3 Only') . "</option>\n";
                echo "<option value=\"4\" $is_4>" . T_('Allow API4 Only') . "</option>\n";
                echo "<option value=\"5\" $is_5>" . T_('Allow API5 Only') . "</option>\n";
                echo "<option value=\"6\" $is_6>" . T_('Allow API6 Only') . "</option>\n";
                echo "</select>\n";
                break;
            case 'jp_volume':
                $is_0  = '';
                $is_1  = '';
                $is_2  = '';
                $is_3  = '';
                $is_4  = '';
                $is_5  = '';
                $is_6  = '';
                $is_7  = '';
                $is_8  = '';
                $is_9  = '';
                $is_10 = '';
                if ($value == 0.0) {
                    $is_0 = 'selected="selected"';
                } elseif ($value == 0.1) {
                    $is_1 = 'selected="selected"';
                } elseif ($value == 0.2) {
                    $is_2 = 'selected="selected"';
                } elseif ($value == 0.3) {
                    $is_3 = 'selected="selected"';
                } elseif ($value == 0.4) {
                    $is_4 = 'selected="selected"';
                } elseif ($value == 0.5) {
                    $is_5 = 'selected="selected"';
                } elseif ($value == 0.6) {
                    $is_6 = 'selected="selected"';
                } elseif ($value == 0.7) {
                    $is_7 = 'selected="selected"';
                } elseif ($value == 0.8) {
                    $is_8 = 'selected="selected"';
                } elseif ($value == 0.9) {
                    $is_9 = 'selected="selected"';
                } elseif ($value == 1.0) {
                    $is_10 = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "<option value=0.00 $is_0>0%</option>\n";
                echo "<option value=0.10 $is_1>10%</option>\n";
                echo "<option value=0.20 $is_2>20%</option>\n";
                echo "<option value=0.30 $is_3>30%</option>\n";
                echo "<option value=0.40 $is_4>40%</option>\n";
                echo "<option value=0.50 $is_5>50%</option>\n";
                echo "<option value=0.60 $is_6>60%</option>\n";
                echo "<option value=0.70 $is_7>70%</option>\n";
                echo "<option value=0.80 $is_8>80%</option>\n";
                echo "<option value=0.90 $is_9>90%</option>\n";
                echo "<option value=1.00 $is_10>100%</option>\n";
                echo "</select>\n";
                break;
            case 'ratingmatch_stars':
                $is_0 = '';
                $is_1 = '';
                $is_2 = '';
                $is_3 = '';
                $is_4 = '';
                $is_5 = '';
                if ($value == 0) {
                    $is_0 = 'selected="selected"';
                } elseif ($value == 1) {
                    $is_1 = 'selected="selected"';
                } elseif ($value == 2) {
                    $is_2 = 'selected="selected"';
                } elseif ($value == 3) {
                    $is_3 = 'selected="selected"';
                } elseif ($value == 4) {
                    $is_4 = 'selected="selected"';
                } elseif ($value == 5) {
                    $is_5 = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "<option value=\"0\" $is_0>" . T_('Disabled') . "</option>\n";
                echo "<option value=\"1\" $is_1>" . T_('1 Star') . "</option>\n";
                echo "<option value=\"2\" $is_2>" . T_('2 Stars') . "</option>\n";
                echo "<option value=\"3\" $is_3>" . T_('3 Stars') . "</option>\n";
                echo "<option value=\"4\" $is_4>" . T_('4 Stars') . "</option>\n";
                echo "<option value=\"5\" $is_5>" . T_('5 Stars') . "</option>\n";
                echo "</select>\n";
                break;
            case 'localplay_level':
            case 'upload_access_level':
                $is_user            = '';
                $is_content_manager = '';
                $is_catalog_manager = '';
                $is_admin           = '';
                if ($value == '25') {
                    $is_user = 'selected="selected"';
                } elseif ($value == '50') {
                    $is_content_manager = 'selected="selected"';
                } elseif ($value == '75') {
                    $is_catalog_manager = 'selected="selected"';
                } elseif ($value == '100') {
                    $is_admin = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "<option value=\"0\">" . T_('Disabled') . "</option>\n";
                echo "<option value=\"25\" $is_user>" . T_('User') . "</option>\n";
                echo "<option value=\"50\" $is_content_manager>" . T_('Content Manager') . "</option>\n";
                echo "<option value=\"75\" $is_catalog_manager>" . T_('Catalog Manager') . "</option>\n";
                echo "<option value=\"100\" $is_admin>" . T_('Admin') . "</option>\n";
                echo "</select>\n";
                break;
            case 'webplayer_removeplayed':
                $is_one   = '';
                $is_two   = '';
                $is_three = '';
                $is_five  = '';
                $is_ten   = '';
                $is_all   = '';
                if ($value == '1') {
                    $is_one = 'selected="selected"';
                } elseif ($value == '2') {
                    $is_two = 'selected="selected"';
                } elseif ($value == '3') {
                    $is_three = 'selected="selected"';
                } elseif ($value == '5') {
                    $is_five = 'selected="selected"';
                } elseif ($value == '10') {
                    $is_ten = 'selected="selected"';
                } elseif ($value == '999') {
                    $is_all = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "<option value=\"0\">" . T_('Disabled') . "</option>\n";
                echo "<option value=\"1\" $is_one>" . T_('Keep last played track') . "</option>\n";
                /* HINT: Keep (2|3|4|5|10) previous tracks */
                echo "<option value=\"2\" $is_two>" . sprintf(T_('Keep %s previous tracks'), '2') . "</option>\n";
                echo "<option value=\"3\" $is_three>" . sprintf(T_('Keep %s previous tracks'), '3') . "</option>\n";
                echo "<option value=\"5\" $is_five>" . sprintf(T_('Keep %s previous tracks'), '5') . "</option>\n";
                echo "<option value=\"10\" $is_ten>" . sprintf(T_('Keep %s previous tracks'), '10') . "</option>\n";
                echo "<option value=\"999\" $is_all>" . T_('Remove all previous tracks') . "</option>\n";
                echo "</select>\n";
                break;
            case 'theme_name':
                $themes = get_themes();
                echo "<select name=\"$name\">\n";
                foreach ($themes as $theme) {
                    $is_selected = "";
                    if ($value == $theme['path']) {
                        $is_selected = "selected=\"selected\"";
                    }
                    echo "\t<option value=\"" . $theme['path'] . "\" $is_selected>" . $theme['name'] . "</option>\n";
                } // foreach themes
                echo "</select>\n";
                break;
            case 'theme_color':
                // This include a two-step configuration (first change theme and save, then change theme color and save)
                $theme_cfg = get_theme(AmpConfig::get('theme_name'));
                if ($theme_cfg !== null) {
                    echo "<select name=\"$name\">\n";
                    foreach ($theme_cfg['colors'] as $color) {
                        $is_selected = "";
                        if ($value == strtolower((string) $color)) {
                            $is_selected = "selected=\"selected\"";
                        }
                        echo "\t<option value=\"" . strtolower((string) $color) . "\" $is_selected>" . $color . "</option>\n";
                    } // foreach themes
                    echo "</select>\n";
                }
                break;
            case 'playlist_method':
                $is_send       = '';
                $is_send_clear = '';
                $is_clear      = '';
                $is_default    = '';
                if ($value == 'send') {
                    $is_send = 'selected="selected"';
                } elseif ($value == 'send_clear') {
                    $is_send_clear = 'selected="selected"';
                } elseif ($value == 'clear') {
                    $is_clear = 'selected="selected"';
                } elseif ($value == 'default') {
                    $is_default = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"send\"$is_send>" . T_('Send on Add') . "</option>\n";
                echo "\t<option value=\"send_clear\"$is_send_clear>" . T_('Send and Clear on Add') . "</option>\n";
                echo "\t<option value=\"clear\"$is_clear>" . T_('Clear on Send') . "</option>\n";
                echo "\t<option value=\"default\"$is_default>" . T_('Default') . "</option>\n";
                echo "</select>\n";
                break;
            case 'transcode':
                $is_never   = '';
                $is_default = '';
                $is_always  = '';
                if ($value == 'never') {
                    $is_never = 'selected="selected"';
                } elseif ($value == 'default') {
                    $is_default = 'selected="selected"';
                } elseif ($value == 'always') {
                    $is_always = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"never\"$is_never>" . T_('Never') . "</option>\n";
                echo "\t<option value=\"default\"$is_default>" . T_('Default') . "</option>\n";
                echo "\t<option value=\"always\"$is_always>" . T_('Always') . "</option>\n";
                echo "</select>\n";
                break;
            case 'album_sort':
                $is_sort_year_asc  = '';
                $is_sort_year_desc = '';
                $is_sort_name_asc  = '';
                $is_sort_name_desc = '';
                $is_sort_default   = '';
                if ($value == 'year_asc') {
                    $is_sort_year_asc = 'selected="selected"';
                } elseif ($value == 'year_desc') {
                    $is_sort_year_desc = 'selected="selected"';
                } elseif ($value == 'name_asc') {
                    $is_sort_name_asc = 'selected="selected"';
                } elseif ($value == 'name_desc') {
                    $is_sort_name_desc = 'selected="selected"';
                } else {
                    $is_sort_default = 'selected="selected"';
                }

                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"default\" $is_sort_default>" . T_('Default') . "</option>\n";
                echo "\t<option value=\"year_asc\" $is_sort_year_asc>" . T_('Year ascending') . "</option>\n";
                echo "\t<option value=\"year_desc\" $is_sort_year_desc>" . T_('Year descending') . "</option>\n";
                echo "\t<option value=\"name_asc\" $is_sort_name_asc>" . T_('Name ascending') . "</option>\n";
                echo "\t<option value=\"name_desc\" $is_sort_name_desc>" . T_('Name descending') . "</option>\n";
                echo "</select>\n";
                break;
            case 'disabled_custom_metadata_fields':
                $ids             = explode(',', $value);
                $options         = array();
                foreach ($this->getMetadataFieldRepository()->getPropertyList() as $propertyId => $propertyName) {
                    $selected  = in_array($propertyId, $ids) ? ' selected="selected"' : '';
                    $options[] = '<option value="' . $propertyId . '"' . $selected . '>' . $propertyName . '</option>';
                }
                echo '<select multiple size="5" name="' . $name . '[]">' . implode("\n", $options) . '</select>';
                break;
            case 'personalfav_playlist':
            case 'personalfav_smartlist':
                $ids       = explode(',', $value);
                $options   = array();
                $playlists = ($name == 'personalfav_smartlist') ? Search::get_search_array() : Playlist::get_playlist_array();
                if (!empty($playlists)) {
                    foreach ($playlists as $list_id => $list_name) {
                        $selected  = in_array($list_id, $ids) ? ' selected="selected"' : '';
                        $options[] = '<option value="' . $list_id . '"' . $selected . '>' . scrub_out($list_name) . '</option>';
                    }
                    echo '<select multiple size="5" name="' . $name . '[]">' . implode("\n", $options) . '</select>';
                }
                break;
            case 'lastfm_grant_link':
            case 'librefm_grant_link':
                // construct links for granting access Ampache application to Last.fm and Libre.fm
                $plugin_name = ucfirst(str_replace('_grant_link', '', $name));
                $plugin      = new Plugin($plugin_name);
                $url         = $plugin->_plugin->url;
                $api_key     = rawurlencode(AmpConfig::get('lastfm_api_key'));
                $callback    = rawurlencode(AmpConfig::get('web_path') . '/preferences.php?tab=plugins&action=grant&plugin=' . $plugin_name);
                /* HINT: Plugin Name */
                echo "<a href=\"$url/api/auth/?api_key=$api_key&cb=$callback\" target=\"_blank\">" . Ui::get_material_symbol('extension', sprintf(T_("Click to grant %s access to Ampache"), $plugin_name)) . '</a>';
                break;
            default:
                if (preg_match('/_pass$/', $name)) {
                    echo '<input type="password" name="' . $name . '" value="******" />';
                } else {
                    echo '<input type="text" name="' . $name . '" value="' . strip_tags($value) . '" />';
                }
                break;
        }
    }

    /**
     * This shows the preference box for the preferences pages.
     *
     * @var array<string, mixed> $preferences
     */
    public function showPreferenceBox(array $preferences): void
    {
        $this->show(
            'show_preference_box.inc.php',
            [
                'preferences' => $preferences,
                'ui' => $this
            ]
        );
    }


    /**
     * This function takes a boolean value and then prints out a friendly text
     * message.
     */
    public static function printBool(bool $value): string
    {
        if ($value) {
            $string = '<span class="item_on">' . T_('On') . '</span>';
        } else {
            $string = '<span class="item_off">' . T_('Off') . '</span>';
        }

        return $string;
    }

    /**
     * @todo inject dependency
     */
    private function getMetadataFieldRepository(): MetadataFieldRepositoryInterface
    {
        global $dic;

        return $dic->get(MetadataFieldRepositoryInterface::class);
    }
}
