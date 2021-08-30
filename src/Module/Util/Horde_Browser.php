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

namespace Ampache\Module\Util;

/**
 *  #####################################################################
 *  #                               Warning                             #
 *  #                               #######                             #
 *  # This external file is Ampache-adapted and probably unsynced with  #
 *  # origin because abandoned by its original authors.                #
 *  #                                                                   #
 *  #####################################################################
 *
 * This provides capability information for the current web client.
 *
 * Browser identification is performed by examining the HTTP_USER_AGENT
 * environment variable provided by the web server.
 *
 * @TODO http://ajaxian.com/archives/parse-user-agent
 *
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 * Copyright 2011 Paul MacIain (local changes for Ampache)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Browser
 */
class Horde_Browser
{
    /**
     * Major version number.
     *
     * @var integer
     */
    private $_majorVersion = 0;

    /**
     * Minor version number.
     *
     * @var integer
     */
    private $_minorVersion = 0;

    /**
     * Browser name.
     *
     * @var string
     */
    private $_browser = '';

    /**
     * Full user agent string.
     *
     * @var string
     */
    private $_agent = '';

    /**
     * Lower-case user agent string.
     *
     * @var string
     */
    private $_lowerAgent = '';

    /**
     * HTTP_ACCEPT string
     *
     * @var string
     */
    private $_accept = '';

    /**
     * Platform the browser is running on.
     *
     * @var string
     */
    private $_platform = '';

    /**
     * Is this a mobile browser?
     *
     * @var boolean
     */
    private $_mobile = false;

    /**
     * Is this a tablet browser?
     *
     * @var boolean
     */
    private $_tablet = false;

    /**
     * Features.
     *
     * @var array
     */
    private $_features = array(
        'frames' => true,
        'html' => true,
        'images' => true,
        'java' => true,
        'javascript' => true,
        'tables' => true
    );

    /**
     * Quirks.
     *
     * @var array
     */
    private $_quirks = array();

    /**
     * Creates a browser instance (Constructor).
     */
    public function __construct()
    {
        $this->match();
    }

    /**
     * Parses the user agent string and initializes the object with all the
     * known features and quirks for the given browser.
     *
     * @param string $userAgent The browser string to parse.
     * @param string $accept The HTTP_ACCEPT settings to use.
     */
    public function match($userAgent = null, $accept = null)
    {
        // Set our agent string.
        if ($userAgent == null) {
            if (filter_has_var(INPUT_SERVER, 'HTTP_USER_AGENT')) {
                $this->_agent = trim($_SERVER['HTTP_USER_AGENT']);
            }
        } else {
            $this->_agent = $userAgent;
        }
        $this->_lowerAgent = strtolower($this->_agent);

        // Set our accept string.
        if ($accept === null) {
            if (filter_has_var(INPUT_SERVER, 'HTTP_ACCEPT')) {
                $this->_accept = strtolower(trim($_SERVER['HTTP_ACCEPT']));
            }
        } else {
            $this->_accept = strtolower($accept);
        }

        // Check for UTF support.
        if (filter_has_var(INPUT_SERVER, 'HTTP_ACCEPT_CHARSET')) {
            $this->setFeature('utf', strpos(strtolower($_SERVER['HTTP_ACCEPT_CHARSET']), 'utf') !== false);
        }

        if (empty($this->_agent)) {
            return;
        }

        $this->setPlatform();

        // Use local scope for frequently accessed variables.
        $agent      = $this->_agent;
        $lowerAgent = $this->_lowerAgent;

        if (strpos($lowerAgent, 'iemobile') !== false || strpos($lowerAgent,
                'mobileexplorer') !== false || strpos($lowerAgent, 'openwave') !== false) {
            $this->setFeature('frames', false);
            $this->setFeature('javascript', false);
            $this->setQuirk('avoid_popup_windows');
            $this->_mobile = true;

            if (preg_match('|iemobile[/ ]([0-9.]+)|', $lowerAgent, $version)) {
                list($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
                if ($this->_majorVersion >= 7) {
                    // Windows Phone, Modern Browser
                    $this->setBrowser('msie');
                    $this->setFeature('javascript');
                    $this->setFeature('xmlhttpreq');
                    $this->setFeature('ajax');
                    $this->setFeature('dom');
                    $this->setFeature('utf');
                    $this->setFeature('rte');
                    $this->setFeature('cite');
                }
            }
        } elseif (strpos($lowerAgent, 'opera mini') !== false || strpos($lowerAgent, 'operamini') !== false) {
            $this->setBrowser('opera');
            $this->setFeature('frames', false);
            $this->setFeature('javascript');
            $this->setQuirk('avoid_popup_windows');
            $this->_mobile = true;
        } elseif (preg_match('|Opera[/ ]([0-9.]+)|', $agent, $version)) {
            $this->setBrowser('opera');
            list($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
            $this->setFeature('javascript');
            $this->setQuirk('no_filename_spaces');

            /* Opera Mobile reports its screen resolution in the user
             * agent strings. */
            if (preg_match('/; (120x160|240x280|240x320|320x320)\)/', $agent)) {
                $this->_mobile = true;
            } elseif (preg_match('|Tablet|', $agent)) {
                $this->_mobile = true;
                $this->_tablet = true;
            }

            if ($this->_majorVersion >= 7) {
                if ($this->_majorVersion >= 8) {
                    $this->setFeature('xmlhttpreq');
                    $this->setFeature('javascript', 1.5);
                }
                if ($this->_majorVersion >= 9) {
                    $this->setFeature('dataurl', 4100);
                    if ($this->_minorVersion >= 5) {
                        $this->setFeature('ajax');
                        $this->setFeature('rte');
                    }
                }
                $this->setFeature('dom');
                $this->setFeature('iframes');
                $this->setFeature('accesskey');
                $this->setFeature('optgroup');
                $this->setQuirk('double_linebreak_textarea');
            }
        } elseif (strpos($lowerAgent, 'elaine/') !== false || strpos($lowerAgent,
                'palmsource') !== false || strpos($lowerAgent, 'digital paths') !== false) {
            $this->setBrowser('palm');
            $this->setFeature('images', false);
            $this->setFeature('frames', false);
            $this->setFeature('javascript', false);
            $this->setQuirk('avoid_popup_windows');
            $this->_mobile = true;
        } elseif ((preg_match('|MSIE ([0-9.]+)|', $agent, $version)) || (preg_match('|Internet Explorer/([0-9.]+)|',
                $agent, $version))) {
            $this->setBrowser('msie');
            $this->setQuirk('cache_ssl_downloads');
            $this->setQuirk('cache_same_url');
            $this->setQuirk('break_disposition_filename');

            if (strpos($version[1], '.') !== false) {
                list($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
            } else {
                $this->_majorVersion = $version[1];
                $this->_minorVersion = 0;
            }

            /* IE (< 7) on Windows does not support alpha transparency
             * in PNG images. */
            if (($this->_majorVersion < 7) && preg_match('/windows/i', $agent)) {
                $this->setQuirk('png_transparency');
            }

            /* Some Handhelds have their screen resolution in the user
             * agent string, which we can use to look for mobile
             * agents. */
            if (preg_match('/; (120x160|240x280|240x320|320x320)\)/', $agent)) {
                $this->_mobile = true;
            }

            $this->setFeature('xmlhttpreq');

            switch ($this->_majorVersion) {
                default:
                case 10:
                case 9:
                case 8:
                case 7:
                    $this->setFeature('javascript', 1.4);
                    $this->setFeature('ajax');
                    $this->setFeature('dom');
                    $this->setFeature('iframes');
                    $this->setFeature('utf');
                    $this->setFeature('rte');
                    $this->setFeature('homepage');
                    $this->setFeature('accesskey');
                    $this->setFeature('optgroup');
                    if ($this->_majorVersion != 7) {
                        $this->setFeature('cite');
                        $this->setFeature('dataurl', ($this->_majorVersion == 8) ? 32768 : true);
                    }
                    break;
                case 6:
                    $this->setFeature('javascript', 1.4);
                    $this->setFeature('dom');
                    $this->setFeature('iframes');
                    $this->setFeature('utf');
                    $this->setFeature('rte');
                    $this->setFeature('homepage');
                    $this->setFeature('accesskey');
                    $this->setFeature('optgroup');
                    $this->setQuirk('scrollbar_in_way');
                    $this->setQuirk('broken_multipart_form');
                    $this->setQuirk('windowed_controls');
                    break;
                case 5:
                    if ($this->getPlatform() == 'mac') {
                        $this->setFeature('javascript', 1.2);
                        $this->setFeature('optgroup');
                        $this->setFeature('xmlhttpreq', false);
                    } else {
                        // MSIE 5 for Windows.
                        $this->setFeature('javascript', 1.4);
                        $this->setFeature('dom');
                        if ($this->_minorVersion >= 5) {
                            $this->setFeature('rte');
                            $this->setQuirk('windowed_controls');
                        }
                    }
                    $this->setFeature('iframes');
                    $this->setFeature('utf');
                    $this->setFeature('homepage');
                    $this->setFeature('accesskey');
                    if ($this->_minorVersion == 5) {
                        $this->setQuirk('break_disposition_header');
                        $this->setQuirk('broken_multipart_form');
                    }
                    break;
                case 4:
                    $this->setFeature('javascript', 1.2);
                    $this->setFeature('accesskey');
                    $this->setFeature('xmlhttpreq', false);
                    if ($this->_minorVersion > 0) {
                        $this->setFeature('utf');
                    }
                    break;
                case 3:
                    $this->setFeature('javascript', 1.1);
                    $this->setQuirk('avoid_popup_windows');
                    $this->setFeature('xmlhttpreq', false);
                    break;
            }
        } elseif (preg_match('|ANTFresco/([0-9]+)|', $agent, $version)) {
            $this->setBrowser('fresco');
            $this->setFeature('javascript', 1.1);
            $this->setQuirk('avoid_popup_windows');
        } elseif (strpos($lowerAgent, 'avantgo') !== false) {
            $this->setBrowser('avantgo');
            $this->_mobile = true;
        } elseif (preg_match('|Konqueror/([0-9]+)\.?([0-9]+)?|', $agent,
                $version) || preg_match('|Safari/([0-9]+)\.?([0-9]+)?|', $agent, $version)) {
            $this->setBrowser('webkit');
            $this->setQuirk('empty_file_input_value');
            $this->setQuirk('no_hidden_overflow_tables');
            $this->setFeature('dataurl');

            if (strpos($agent, 'Mobile') !== false || strpos($agent, 'Android') !== false || strpos($agent,
                    'SAMSUNG-GT') !== false || ((strpos($agent, 'Nokia') !== false || strpos($agent,
                            'Symbian') !== false) && strpos($agent, 'WebKit') !== false) || (strpos($agent,
                        'N900') !== false && strpos($agent, 'Maemo Browser') !== false) || (strpos($agent,
                        'MeeGo') !== false && strpos($agent, 'NokiaN9') !== false)) {
                // WebKit Mobile
                $this->setFeature('frames', false);
                $this->setFeature('javascript');
                $this->setQuirk('avoid_popup_windows');
                $this->_mobile = true;
            }

            $this->_majorVersion = $version[1];
            if (isset($version[2])) {
                $this->_minorVersion = $version[2];
            }

            if (stripos($agent, 'Chrome/') !== false || stripos($agent, 'CriOS/') !== false) {
                // Google Chrome.
                $this->setFeature('ischrome');
                $this->setFeature('rte');
                $this->setFeature('utf');
                $this->setFeature('javascript', 1.4);
                $this->setFeature('ajax');
                $this->setFeature('dom');
                $this->setFeature('iframes');
                $this->setFeature('accesskey');
                $this->setFeature('xmlhttpreq');
                $this->setQuirk('empty_file_input_value', 0);

                if (preg_match('|Chrome/([0-9.]+)|i', $agent, $version_string)) {
                    list($this->_majorVersion, $this->_minorVersion) = explode('.', $version_string[1], 2);
                }
            } elseif (stripos($agent, 'Safari/') !== false && $this->_majorVersion >= 60) {
                // Safari.
                $this->setFeature('issafari');

                // Truly annoying - Safari did not start putting real version
                // numbers until Version 3.
                if (preg_match('|Version/([0-9.]+)|', $agent, $version_string)) {
                    list($this->_majorVersion, $this->_minorVersion) = explode('.', $version_string[1], 2);
                    $this->_minorVersion                             = (int)($this->_minorVersion);
                    $this->setFeature('ajax');
                    $this->setFeature('rte');
                } elseif ($this->_majorVersion >= 412) {
                    $this->_majorVersion = 2;
                    $this->_minorVersion = 0;
                } else {
                    if ($this->_majorVersion >= 312) {
                        $this->_minorVersion = 3;
                    } elseif ($this->_majorVersion >= 124) {
                        $this->_minorVersion = 2;
                    } else {
                        $this->_minorVersion = 0;
                    }
                    $this->_majorVersion = 1;
                }

                $this->setFeature('utf');
                $this->setFeature('javascript', 1.4);
                $this->setFeature('dom');
                $this->setFeature('iframes');
                if ($this->_majorVersion > 1 || $this->_minorVersion > 2) {
                    // As of Safari 1.3
                    $this->setFeature('accesskey');
                    $this->setFeature('xmlhttpreq');
                }
            } else {
                // Konqueror.
                $this->setFeature('javascript', 1.1);
                $this->setFeature('iskonqueror');
                switch ($this->_majorVersion) {
                    case 4:
                    case 3:
                        $this->setFeature('dom');
                        $this->setFeature('iframes');
                        if ($this->_minorVersion >= 5 || $this->_majorVersion == 4) {
                            $this->setFeature('accesskey');
                            $this->setFeature('xmlhttpreq');
                        }
                        break;
                }
            }
        } elseif (preg_match('|Mozilla/([0-9.]+)|', $agent, $version)) {
            $this->setBrowser('mozilla');
            $this->setQuirk('must_cache_forms');

            list($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
            switch ($this->_majorVersion) {
                default:
                case 5:
                    if ($this->getPlatform() == 'win') {
                        $this->setQuirk('break_disposition_filename');
                    }
                    $this->setFeature('javascript', 1.4);
                    $this->setFeature('ajax');
                    $this->setFeature('dom');
                    $this->setFeature('accesskey');
                    $this->setFeature('optgroup');
                    $this->setFeature('xmlhttpreq');
                    $this->setFeature('cite');
                    if (preg_match('|rv:(.*)\)|', $agent, $revision)) {
                        if (version_compare($revision[1], '1', '>=')) {
                            $this->setFeature('iframes');
                        }
                        if (version_compare($revision[1], '1.3', '>=')) {
                            $this->setFeature('rte');
                        }
                        if (version_compare($revision[1], '1.8.1', '>=')) {
                            $this->setFeature('dataurl');
                        }
                        if (version_compare($revision[1], '10.0', '>=')) {
                            $this->setFeature('utf');
                        }
                    }
                    if (stripos($agent, 'mobile') !== false) {
                        $this->_mobile = true;
                    } elseif (stripos($agent, 'tablet') !== false) {
                        $this->_tablet = true;
                        $this->_mobile = true;
                    }
                    break;
                case 4:
                    $this->setFeature('javascript', 1.3);
                    $this->setQuirk('buggy_compression');
                    break;
                case 3:
                case 2:
                case 1:
                case 0:
                    $this->setFeature('javascript', 1);
                    $this->setQuirk('buggy_compression');
                    break;
            }
        } elseif (preg_match('|Lynx/([0-9]+)|', $agent, $version)) {
            $this->setBrowser('lynx');
            $this->setFeature('images', false);
            $this->setFeature('frames', false);
            $this->setFeature('javascript', false);
            $this->setQuirk('avoid_popup_windows');
        } elseif (preg_match('|Links \(([0-9]+)|', $agent, $version)) {
            $this->setBrowser('links');
            $this->setFeature('images', false);
            $this->setFeature('frames', false);
            $this->setFeature('javascript', false);
            $this->setQuirk('avoid_popup_windows');
        } elseif (preg_match('|HotJava/([0-9]+)|', $agent, $version)) {
            $this->setBrowser('hotjava');
            $this->setFeature('javascript', false);
        } elseif (strpos($agent, 'UP/') !== false || strpos($agent, 'UP.B') !== false || strpos($agent,
                'UP.L') !== false) {
            $this->setBrowser('up');
            $this->setFeature('html', false);
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');

            if (strpos($agent, 'GUI') !== false && strpos($agent, 'UP.Link') !== false) {
                /* The device accepts Openwave GUI extensions for WML
                 * 1.3. Non-UP.Link gateways sometimes have problems,
                 * so exclude them. */
                $this->setQuirk('ow_gui_1.3');
            }
            $this->_mobile = true;
        } elseif (strpos($agent, 'Xiino/') !== false) {
            $this->setBrowser('xiino');
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->_mobile = true;
        } elseif (strpos($agent, 'Palmscape/') !== false) {
            $this->setBrowser('palmscape');
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->_mobile = true;
        } elseif (strpos($agent, 'Nokia') !== false) {
            $this->setBrowser('nokia');
            $this->setFeature('html', false);
            $this->setFeature('wml');
            $this->setFeature('xhtml');
            $this->_mobile = true;
        } elseif (strpos($agent, 'Ericsson') !== false) {
            $this->setBrowser('ericsson');
            $this->setFeature('html', false);
            $this->setFeature('wml');
            $this->_mobile = true;
        } elseif (strpos($agent, 'Grundig') !== false) {
            $this->setBrowser('grundig');
            $this->setFeature('xhtml');
            $this->setFeature('wml');
            $this->_mobile = true;
        } elseif (strpos($agent, 'NetFront') !== false) {
            $this->setBrowser('netfront');
            $this->setFeature('xhtml');
            $this->setFeature('wml');
            $this->_mobile = true;
        } elseif (strpos($lowerAgent, 'wap') !== false) {
            $this->setBrowser('wap');
            $this->setFeature('html', false);
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->_mobile = true;
        } elseif (strpos($lowerAgent, 'docomo') !== false || strpos($lowerAgent, 'portalmmm') !== false) {
            $this->setBrowser('imode');
            $this->setFeature('images', false);
            $this->_mobile = true;
        } elseif (preg_match('|BlackBerry.*?/([0-9.]+)|', $agent, $version)) {
            list($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
            $this->setBrowser('blackberry');
            $this->setFeature('html', false);
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->_mobile = true;
            if ($this->_majorVersion >= 5 || ($this->_majorVersion == 4 && $this->_minorVersion >= 6)) {
                $this->setFeature('ajax');
                $this->setFeature('iframes');
                $this->setFeature('javascript', 1.5);
                $this->setFeature('dom');
                $this->setFeature('xmlhttpreq');
            }
        } elseif (strpos($agent, 'MOT-') !== false) {
            $this->setBrowser('motorola');
            $this->setFeature('html', false);
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->_mobile = true;
        } elseif (strpos($lowerAgent, 'j-') !== false) {
            $this->setBrowser('mml');
            $this->_mobile = true;
        }
    }

    /**
     * Matches the platform of the browser.
     *
     * This is a pretty simplistic implementation, but it's intended to let us
     * tell what line breaks to send, so it's good enough for its purpose.
     */
    private function setPlatform()
    {
        if (strpos($this->_lowerAgent, 'wind') !== false) {
            $this->_platform = 'win';
        } elseif (strpos($this->_lowerAgent, 'mac') !== false) {
            $this->_platform = 'mac';
        } else {
            $this->_platform = 'unix';
        }
    }

    /**
     * Returns the currently matched platform.
     *
     * @return string  The user's platform.
     */
    private function getPlatform()
    {
        return $this->_platform;
    }

    /**
     * Sets the current browser.
     *
     * @param string $browser The browser to set as current.
     */
    private function setBrowser($browser)
    {
        $this->_browser = $browser;
    }

    /**
     * Determines if the given browser is the same as the current.
     *
     * @param string $browser The browser to check.
     *
     * @return boolean  Is the given browser the same as the current?
     */
    public function isBrowser($browser)
    {
        return ($this->_browser === $browser);
    }

    /**
     * Sets unique behavior for the current browser.
     *
     * @param string $quirk The behavior to set. Quirks:
     *   - avoid_popup_windows
     *   - break_disposition_header
     *   - break_disposition_filename
     *   - broken_multipart_form
     *   - buggy_compression
     *   - cache_same_url
     *   - cache_ssl_downloads
     *   - double_linebreak_textarea
     *   - empty_file_input_value
     *   - must_cache_forms
     *   - no_filename_spaces
     *   - no_hidden_overflow_tables
     *   - ow_gui_1.3
     *   - png_transparency
     *   - scrollbar_in_way
     *   - scroll_tds
     *   - windowed_controls
     * @param boolean $value Special behavior parameter.
     */
    private function setQuirk($quirk, $value = true)
    {
        if ($value) {
            $this->_quirks[$quirk] = $value;
        } else {
            unset($this->_quirks[$quirk]);
        }
    }

    /**
     * Checks unique behavior for the current browser.
     *
     * @param string $quirk The behavior to check.
     *
     * @return boolean  Does the browser have the behavior set?
     */
    private function hasQuirk($quirk)
    {
        return !empty($this->_quirks[$quirk]);
    }

    /**
     * Sets capabilities for the current browser.
     *
     * @param string $feature The capability to set. Features:
     *   - accesskey
     *   - ajax
     *   - cite
     *   - dataurl
     *   - dom
     *   - frames
     *   - hdml
     *   - html
     *   - homepage
     *   - iframes
     *   - images
     *   - ischrome
     *   - iskonqueror
     *   - issafari
     *   - java
     *   - javascript
     *   - optgroup
     *   - rte
     *   - tables
     *   - utf
     *   - wml
     *   - xmlhttpreq
     * @param boolean $value Special capability parameter.
     */
    public function setFeature($feature, $value = true)
    {
        if ($value) {
            $this->_features[$feature] = $value;
        } else {
            unset($this->_features[$feature]);
        }
    }

    /**
     * Returns the headers for a browser download.
     *
     * @param string $filename The filename of the download.
     * @param string $cType The content-type description of the file.
     * @param boolean $inline True if inline, false if attachment.
     * @param string $cLength The content-length of this file.
     *
     * @return string[]
     */
    public function getDownloadHeaders(
        $filename = 'unknown',
        $cType = null,
        $inline = false,
        $cLength = null
    ): array {
        /* Remove linebreaks from file names. */
        $filename = str_replace(array("\r\n", "\r", "\n"), ' ', $filename);

        /* Some browsers don't like spaces in the filename. */
        if ($this->hasQuirk('no_filename_spaces')) {
            $filename = strtr($filename, ' ', '_');
        }

        /* MSIE doesn't like multiple periods in the file name. Convert all
         * periods (except the last one) to underscores. */
        if ($this->isBrowser('msie')) {
            if (($pos = strrpos($filename, '.'))) {
                $filename = strtr(substr($filename, 0, $pos), '.', '_') . substr($filename, $pos);
            }

            /* Encode the filename so IE downloads it correctly. (Bug #129) */
            $filename = rawurlencode($filename);
        }

        $headers = [];

        /* Content-Type/Content-Disposition Header. */
        if ($inline) {
            if ($cType !== null) {
                $headers['Content-Type'] = trim($cType);
            } elseif ($this->isBrowser('msie')) {
                $headers['Content-Type'] = 'application/x-msdownload';
            } else {
                $headers['Content-Type'] = 'application/octet-stream';
            }
            $headers['Content-Disposition'] = 'inline; filename="' . $filename . '"';
        } else {
            if ($this->isBrowser('msie')) {
                $headers['Content-Type'] = 'application/x-msdownload';
            } elseif ($cType !== null) {
                $headers['Content-Type'] = trim($cType);
            } else {
                $headers['Content-Type'] = 'application/octet-stream';
            }

            if ($this->hasQuirk('break_disposition_header')) {
                $headers['Content-Disposition'] = 'filename="' . $filename . '"';
            } else {
                $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
            }
        }

        /* Content-Length Header. Only send if we are not compressing
         * output. */
        if ($cLength !== null && !in_array('ob_gzhandler', ob_list_handlers())) {
            $headers['Content-Length'] = $cLength;
        }

        /* Overwrite Pragma: and other caching headers for IE. */
        if ($this->hasQuirk('cache_ssl_downloads')) {
            $headers['Expires']       = 0;
            $headers['Cache-Control'] = 'must-revalidate';
            $headers['Pragma']        = 'public';
        }

        return $headers;
    }
}
