<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Localplay\LocalPlayTypeEnum;
use Ampache\Repository\Model\Metadata\Repository\MetadataField;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Plugin;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Preference;

/**
 * A collection of methods related to the user interface
 */
class Ui implements UiInterface
{
    private static $_classes;
    private static $_ticker;
    private static $_icon_cache;
    private static $_image_cache;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * find_template
     *
     * Return the path to the template file wanted. The file can be overwritten
     * by the theme if it's not a php file, or if it is and if option
     * allow_php_themes is set to true.
     * @param string $template
     * @return string
     */
    public static function find_template($template, bool $extern = false)
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

    public function accessDenied(string $error = 'Access Denied'): void
    {
        // Clear any buffered crap
        ob_end_clean();
        header("HTTP/1.1 403 $error");
        require_once self::find_template('show_denied.inc.php');
    }

    /**
     * ajax_include
     *
     * Does some trickery with the output buffer to return the output of a
     * template.
     * @param string $template
     * @return string
     */
    public static function ajax_include($template)
    {
        ob_start();
        require self::find_template('') . $template;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * check_iconv
     *
     * Checks to see whether iconv is available;
     * @return boolean
     */
    public static function check_iconv()
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
     * @return boolean
     */
    public static function check_ticker()
    {
        if (!isset(self::$_ticker) || (time() > self::$_ticker + 1)) {
            self::$_ticker = time();

            return true;
        }

        return false;
    }

    /**
     * clean_utf8
     *
     * Removes characters that aren't valid in XML (which is a subset of valid
     * UTF-8, but close enough for our purposes.)
     * See http://www.w3.org/TR/2006/REC-xml-20060816/#charsets
     * @param string $string
     * @return string
     */
    public static function clean_utf8($string)
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
     * @param integer $precision
     * @return string
     */
    public static function format_bytes($value, $precision = 2)
    {
        $pass = 0;
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
     * @param $value
     * @return string
     */
    public static function unformat_bytes($value)
    {
        if (preg_match('/^([0-9]+) *([[:alpha:]]+)$/', (string)$value, $matches)) {
            $value = $matches[1];
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
     * @param string $name
     * @param string $title
     * @param string $id_attrib
     * @param string $class_attrib
     * @return string
     */
    public static function get_icon($name, $title = null, $id_attrib = null, $class_attrib = null)
    {
        if (is_array($name)) {
            $hover_name = $name[1];
            $name       = $name[0];
        }

        $title    = $title ?: T_(ucfirst($name));
        $icon_url = self::_find_icon($name);
        $icontype = pathinfo($icon_url, 4);
        if (isset($hover_name)) {
            $hover_url = self::_find_icon($hover_name);
        }
        if ($icontype == 'svg') {
            // load svg file
            $svgicon = simplexml_load_file($icon_url);

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

            $class_attrib = ($class_attrib) ?: 'icon icon-' . $name;
            $svgicon->addAttribute('class', $class_attrib);

            $tag = explode("\n", $svgicon->asXML(), 2)[1];
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
            if (isset($hover_name) && isset($hover_url)) {
                $tag .= 'onmouseover="this.src=\'' . $hover_url . '\'; return true;"';
                $tag .= 'onmouseout="this.src=\'' . $icon_url . '\'; return true;" ';
            }
            $tag .= '/>';
        }

        return $tag;
    }

    /**
     * _find_icon
     *
     * Does the finding icon thing. match svg first over png
     * @param string $name
     * @return string
     */
    private static function _find_icon($name)
    {
        if (isset(self::$_icon_cache[$name])) {
            return self::$_icon_cache[$name];
        }

        $path       = AmpConfig::get('theme_path') . '/images/icons/';
        $filesearch = glob(__DIR__ . '/../../../public/' . $path . 'icon_' . $name . '.{svg,png}', GLOB_BRACE);
        if (empty($filesearch)) {
            // if the theme is missing an icon. fall back to default images folder
            $filename = 'icon_' . $name . '.png';
            $path     = '/images/';
        } else {
            $filename = pathinfo($filesearch[0], 2);
        }
        $url = AmpConfig::get('web_path') . $path . $filename;
        // cache the url so you don't need to keep searching
        self::$_icon_cache[$name] = $url;

        return $url;
    }

    /**
     * get_image
     *
     * Returns an <img> or <svg> tag for the specified image
     * @param string $name
     * @param string $title
     * @param string $id_attrib
     * @param string $class_attrib
     * @return string
     */
    public static function get_image($name, $title = null, $id_attrib = null, $class_attrib = null)
    {
        if (is_array($name)) {
            $hover_name = $name[1];
            $name       = $name[0];
        }

        $title = $title ?: ucfirst($name);

        $image_url = self::_find_image($name);
        $imagetype = pathinfo($image_url, 4);
        if (isset($hover_name)) {
            $hover_url = self::_find_image($hover_name);
        }
        if ($imagetype == 'svg') {
            // load svg file
            $svgimage = simplexml_load_file($image_url);

            $svgimage->addAttribute('class', 'image');

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

            $class_attrib = ($class_attrib) ?: 'image image-' . $name;
            $svgimage->addAttribute('class', $class_attrib);

            $tag = explode("\n", $svgimage->asXML(), 2)[1];
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
            if (isset($hover_name) && isset($hover_url)) {
                $tag .= 'onmouseover="this.src=\'' . $hover_url . '\'; return true;"';
                $tag .= 'onmouseout="this.src=\'' . $image_url . '\'; return true;" ';
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
     * @return string
     */
    private static function _find_image($name)
    {
        if (isset(self::$_image_cache[$name])) {
            return self::$_image_cache[$name];
        }

        $path       = AmpConfig::get('theme_path') . '/images/';
        $filesearch = glob(__DIR__ . '/../../../public/' . $path . $name . '.{svg,png}', GLOB_BRACE);
        if (empty($filesearch)) {
            // if the theme is missing an image. fall back to default images folder
            $filename = $name . '.png';
            $path     = '/images/';
        } else {
            $filename = pathinfo($filesearch[0], 2);
        }
        $url = AmpConfig::get('web_path') . $path . $filename;
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
    public static function show_footer()
    {
        if (!defined("TABLE_RENDERED")) {
            show_table_render();
        }

        $plugins = Plugin::get_plugins('display_on_footer');
        foreach ($plugins as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load(Core::get_global('user'))) {
                $plugin->_plugin->display_on_footer();
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
    public static function show_box_top($title = '', $class = '')
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
    public static function show_box_bottom()
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

    public static function show_custom_style()
    {
        if (AmpConfig::get('custom_login_background')) {
            echo "<style> body { background-position: center; background-size: cover; background-image: url('" . AmpConfig::get('custom_login_background') . "') !important; }</style>";
        }

        if (AmpConfig::get('custom_login_logo')) {
            echo "<style>#loginPage #headerlogo, #registerPage #headerlogo { background-image: url('" . AmpConfig::get('custom_login_logo') . "') !important; }</style>";
        }

        $favicon = AmpConfig::get('custom_favicon') ?: AmpConfig::get('web_path') . "/favicon.ico";
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
    public static function update_text($field, $value)
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
     * @return string
     */
    public static function get_logo_url($color = null)
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
     * @return boolean
     */
    public static function is_grid_view($type)
    {
        $isgv = true;
        $name = 'browse_' . $type . '_grid_view';
        if (filter_has_var(INPUT_COOKIE, $name)) {
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
     * @param integer $cancel T/F show a cancel button that uses return_referer()
     * @param string $form_name
     * @param boolean $visible
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

        require Ui::find_template('show_confirmation.inc.php');
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

        return htmlentities((string) $string, ENT_NOQUOTES, AmpConfig::get('site_charset'));
    }

    /**
     * takes the key and then creates the correct type of input for updating it
     */
    public function createPreferenceInput(
        string $name,
        $value
    ) {
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
            case 'display_menu':
            case 'download':
            case 'quarantine':
            case 'upload':
            case 'access_list':
            case 'lock_songs':
            case 'xml_rpc':
            case 'force_http_play':
            case 'no_symlinks':
            case 'use_auth':
            case 'access_control':
            case 'allow_stream_playback':
            case 'allow_democratic_playback':
            case 'allow_localplay_playback':
            case 'demo_mode':
            case 'condPL':
            case 'rio_track_stats':
            case 'rio_global_stats':
            case 'direct_link':
            case 'ajax_load':
            case 'now_playing_per_user':
            case 'show_played_times':
            case 'use_original_year':
            case 'show_skipped_times':
            case 'show_license':
            case 'song_page_title':
            case 'subsonic_backend':
            case 'webplayer_flash':
            case 'webplayer_html5':
            case 'allow_personal_info_now':
            case 'allow_personal_info_recent':
            case 'allow_personal_info_time':
            case 'allow_personal_info_agent':
            case 'ui_fixed':
            case 'autoupdate':
            case 'autoupdate_lastversion_new':
            case 'webplayer_confirmclose':
            case 'webplayer_pausetabs':
            case 'stream_beautiful_url':
            case 'share':
            case 'share_social':
            case 'broadcast_by_default':
            case 'album_group':
            case 'topmenu':
            case 'demo_clear_sessions':
            case 'show_donate':
            case 'allow_upload':
            case 'upload_subdir':
            case 'upload_user_artist':
            case 'upload_allow_edit':
            case 'daap_backend':
            case 'upnp_backend':
            case 'album_release_type':
            case 'home_moment_albums':
            case 'home_moment_videos':
            case 'home_recently_played':
            case 'home_now_playing':
            case 'browser_notify':
            case 'allow_video':
            case 'geolocation':
            case 'webplayer_aurora':
            case 'upload_allow_remove':
            case 'webdav_backend':
            case 'notify_email':
            case 'libitem_contextmenu':
            case 'upload_catalog_pattern':
            case 'catalogfav_gridview':
            case 'personalfav_display':
            case 'ratingmatch_flags':
            case 'catalog_check_duplicate':
            case 'browse_filter':
            case 'sidebar_light':
            case 'cron_cache':
            case 'show_lyrics':
            case 'unique_playlist':
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
                show_catalog_select('upload_catalog', $value, '', true);
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
                $var_name    = $value . "_type";
                ${$var_name} = "selected=\"selected\"";
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"m3u\" $m3u_type>" . T_('M3U') . "</option>\n";
                echo "\t<option value=\"simple_m3u\" $simple_m3u_type>" . T_('Simple M3U') . "</option>\n";
                echo "\t<option value=\"pls\" $pls_type>" . T_('PLS') . "</option>\n";
                echo "\t<option value=\"asx\" $asx_type>" . T_('Asx') . "</option>\n";
                echo "\t<option value=\"ram\" $ram_type>" . T_('RAM') . "</option>\n";
                echo "\t<option value=\"xspf\" $xspf_type>" . T_('XSPF') . "</option>\n";
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
                } elseif ($value == 4) {
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
                $is_user    = '';
                $is_admin   = '';
                $is_manager = '';
                if ($value == '25') {
                    $is_user = 'selected="selected"';
                } elseif ($value == '100') {
                    $is_admin = 'selected="selected"';
                } elseif ($value == '50') {
                    $is_manager = 'selected="selected"';
                }
                echo "<select name=\"$name\">\n";
                echo "<option value=\"0\">" . T_('Disabled') . "</option>\n";
                echo "<option value=\"25\" $is_user>" . T_('User') . "</option>\n";
                echo "<option value=\"50\" $is_manager>" . T_('Manager') . "</option>\n";
                echo "<option value=\"100\" $is_admin>" . T_('Admin') . "</option>\n";
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
                ${$value} = ' selected="selected"';
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"send\"$send>" . T_('Send on Add') . "</option>\n";
                echo "\t<option value=\"send_clear\"$send_clear>" . T_('Send and Clear on Add') . "</option>\n";
                echo "\t<option value=\"clear\"$clear>" . T_('Clear on Send') . "</option>\n";
                echo "\t<option value=\"default\"$default>" . T_('Default') . "</option>\n";
                echo "</select>\n";
                break;
            case 'transcode':
                ${$value} = ' selected="selected"';
                echo "<select name=\"$name\">\n";
                echo "\t<option value=\"never\"$never>" . T_('Never') . "</option>\n";
                echo "\t<option value=\"default\"$default>" . T_('Default') . "</option>\n";
                echo "\t<option value=\"always\"$always>" . T_('Always') . "</option>\n";
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
                $fieldRepository = new MetadataField();
                foreach ($fieldRepository->findAll() as $field) {
                    $selected  = in_array($field->getId(), $ids) ? ' selected="selected"' : '';
                    $options[] = '<option value="' . $field->getId() . '"' . $selected . '>' . $field->getName() . '</option>';
                }
                echo '<select multiple size="5" name="' . $name . '[]">' . implode("\n", $options) . '</select>';
                break;
            case 'personalfav_playlist':
            case 'personalfav_smartlist':
                $ids       = explode(',', $value);
                $options   = array();
                $playlists = ($name == 'personalfav_smartlist') ? Playlist::get_details('search') : Playlist::get_details();
                if (!empty($playlists)) {
                    foreach ($playlists as $list_id => $list_name) {
                        $selected  = in_array($list_id, $ids) ? ' selected="selected"' : '';
                        $options[] = '<option value="' . $list_id . '"' . $selected . '>' . $list_name . '</option>';
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
                echo "<a href='$url/api/auth/?api_key=$api_key&cb=$callback'>" . Ui::get_icon('plugin', sprintf(T_("Click to grant %s access to Ampache"), $plugin_name)) . '</a>';
                break;
            default:
                if (preg_match('/_pass$/', $name)) {
                    echo '<input type="password" name="' . $name . '" value="******" />';
                } else {
                    echo '<input type="text" name="' . $name . '" value="' . $value . '" />';
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
}
