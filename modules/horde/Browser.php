<?php
/**
 *  #####################################################################
 *  #                               Warning                             #
 *  #                               #######                             #
 *  # This external file is Ampache-adapted and probably unsynced with  #
 *  # origin because abandonned by its original authors.                #
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
    protected $_majorVersion = 0;

    /**
     * Minor version number.
     *
     * @var integer
     */
    protected $_minorVersion = 0;

    /**
     * Browser name.
     *
     * @var string
     */
    protected $_browser = '';

    /**
     * Full user agent string.
     *
     * @var string
     */
    protected $_agent = '';

    /**
     * Lower-case user agent string.
     *
     * @var string
     */
    protected $_lowerAgent = '';

    /**
     * HTTP_ACCEPT string
     *
     * @var string
     */
    protected $_accept = '';

    /**
     * Platform the browser is running on.
     *
     * @var string
     */
    protected $_platform = '';

    /**
     * Known robots.
     *
     * @var array
     */
    protected $_robotAgents = array(
        /* The most common ones. */
        'Googlebot',
        'msnbot',
        'bingbot',
        'Slurp',
        'Yahoo',
        /* The rest alphabetically. */
        'appie',
        'Arachnoidea',
        'ArchitextSpider',
        'Ask Jeeves',
        'B-l-i-t-z-Bot',
        'Baiduspider',
        'BecomeBot',
        'cfetch',
        'ConveraCrawler',
        'ExtractorPro',
        'FAST-WebCrawler',
        'FDSE robot',
        'fido',
        'findlinks',
        'Francis',
        'geckobot',
        'Gigabot',
        'Girafabot',
        'grub-client',
        'Gulliver',
        'HTTrack',
        'ia_archiver',
        'iaskspider',
        'iCCrawler',
        'InfoSeek',
        'kinjabot',
        'KIT-Fireball',
        'larbin',
        'LEIA',
        'lmspider',
        'lwp-trivial',
        'Lycos_Spider',
        'Mediapartners-Google',
        'MSRBOT',
        'MuscatFerret',
        'NaverBot',
        'OmniExplorer_Bot',
        'polybot',
        'Pompos',
        'RufusBot',
        'Scooter',
        'Seekbot',
        'sogou spider',
        'sproose',
        'Teoma',
        'TheSuBot',
        'TurnitinBot',
        'Twiceler',
        'Ultraseek',
        'Vagabondo/Kliksafe',
        'ViolaBot',
        'voyager',
        'W3C-checklink',
        'webbandit',
        'www.almaden.ibm.com/cs/crawler',
        'yacy',
        'ZyBorg',
    );

    /**
     * Regexp for matching those robot strings.
     *
     * @var string
     */
    protected $_robotAgentRegexp = null;

    /**
     * List of mobile user agents.
     *
     * Browsers like Mobile Safari (iPhone, iPod Touch) are much more
     * full featured than OpenWave style browsers. This makes it dicey
     * in some cases to treat all "mobile" browsers the same way.
     *
     * @TODO This list is not used in isMobile yet nor does it provide
     * the same results as isMobile(). It is here for reference and
     * future work.
     */
    protected $_mobileAgents = array(
        'Blackberry',
        'Blazer',
        'Handspring',
        'iPhone',
        'iPod',
        'Kyocera',
        'LG',
        'Motorola',
        'Nokia',
        'Palm',
        'PlayStation Portable',
        'Samsung',
        'Smartphone',
        'SonyEricsson',
        'Symbian',
        'WAP',
        'Windows CE',
    );

    /**
     * List of televison user agents.
     *
     * @TODO This list is not yet used anywhere. It is here for future
     * media-type differentiation.
     */
    protected $_tvAgents = array(
        'Nintendo Wii',
        'Playstation 3',
        'WebTV',
    );

    /**
     * Is this a mobile browser?
     *
     * @var boolean
     */
    protected $_mobile = false;

    /**
     * Is this a tablet browser?
     *
     * @var boolean
     */
    protected $_tablet = false;

    /**
     * Features.
     *
     * @var array
     */
    protected $_features = array(
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
    protected $_quirks = array();

    /**
     * List of viewable image MIME subtypes.
     * This list of viewable images works for IE and Netscape/Mozilla.
     *
     * @var array
     */
    protected $_images = array('jpeg', 'gif', 'png', 'pjpeg', 'x-png', 'bmp');

    /**
     * Creates a browser instance (Constructor).
     *
     * @param string $userAgent  The browser string to parse.
     * @param string $accept     The HTTP_ACCEPT settings to use.
     */
    public function __construct($userAgent = null, $accept = null)
    {
        $this->match($userAgent, $accept);
    }

    /**
     * Parses the user agent string and inititializes the object with all the
     * known features and quirks for the given browser.
     *
     * @param string $userAgent  The browser string to parse.
     * @param string $accept     The HTTP_ACCEPT settings to use.
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

        $this->_setPlatform();

        // Use local scope for frequently accessed variables.
        $agent      = $this->_agent;
        $lowerAgent = $this->_lowerAgent;

        if (strpos($lowerAgent, 'iemobile') !== false ||
            strpos($lowerAgent, 'mobileexplorer') !== false ||
            strpos($lowerAgent, 'openwave') !== false) {
            $this->setFeature('frames', false);
            $this->setFeature('javascript', false);
            $this->setQuirk('avoid_popup_windows');
            $this->setMobile(true);

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
        } elseif (strpos($lowerAgent, 'opera mini') !== false ||
                  strpos($lowerAgent, 'operamini') !== false) {
            $this->setBrowser('opera');
            $this->setFeature('frames', false);
            $this->setFeature('javascript');
            $this->setQuirk('avoid_popup_windows');
            $this->setMobile(true);
        } elseif (preg_match('|Opera[/ ]([0-9.]+)|', $agent, $version)) {
            $this->setBrowser('opera');
            list($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
            $this->setFeature('javascript');
            $this->setQuirk('no_filename_spaces');

            /* Opera Mobile reports its screen resolution in the user
             * agent strings. */
            if (preg_match('/; (120x160|240x280|240x320|320x320)\)/', $agent)) {
                $this->setMobile(true);
            } elseif (preg_match('|Tablet|', $agent)) {
                $this->setMobile(true);
                $this->setTablet(true);
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
        } elseif (strpos($lowerAgent, 'elaine/') !== false ||
                  strpos($lowerAgent, 'palmsource') !== false ||
                  strpos($lowerAgent, 'digital paths') !== false) {
            $this->setBrowser('palm');
            $this->setFeature('images', false);
            $this->setFeature('frames', false);
            $this->setFeature('javascript', false);
            $this->setQuirk('avoid_popup_windows');
            $this->setMobile(true);
        } elseif ((preg_match('|MSIE ([0-9.]+)|', $agent, $version)) ||
                  (preg_match('|Internet Explorer/([0-9.]+)|', $agent, $version))) {
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
            if (($this->_majorVersion < 7) &&
                preg_match('/windows/i', $agent)) {
                $this->setQuirk('png_transparency');
            }

            /* Some Handhelds have their screen resolution in the user
             * agent string, which we can use to look for mobile
             * agents. */
            if (preg_match('/; (120x160|240x280|240x320|320x320)\)/', $agent)) {
                $this->setMobile(true);
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
            $this->setMobile(true);
        } elseif (preg_match('|Konqueror/([0-9]+)\.?([0-9]+)?|', $agent, $version) ||
                  preg_match('|Safari/([0-9]+)\.?([0-9]+)?|', $agent, $version)) {
            $this->setBrowser('webkit');
            $this->setQuirk('empty_file_input_value');
            $this->setQuirk('no_hidden_overflow_tables');
            $this->setFeature('dataurl');

            if (strpos($agent, 'Mobile') !== false ||
                strpos($agent, 'Android') !== false ||
                strpos($agent, 'SAMSUNG-GT') !== false ||
                ((strpos($agent, 'Nokia') !== false ||
                 strpos($agent, 'Symbian') !== false) &&
                 strpos($agent, 'WebKit') !== false) ||
                (strpos($agent, 'N900') !== false &&
                 strpos($agent, 'Maemo Browser') !== false) ||
                (strpos($agent, 'MeeGo') !== false &&
                strpos($agent, 'NokiaN9') !== false)) {
                // WebKit Mobile
                $this->setFeature('frames', false);
                $this->setFeature('javascript');
                $this->setQuirk('avoid_popup_windows');
                $this->setMobile(true);
            }

            $this->_majorVersion = $version[1];
            if (isset($version[2])) {
                $this->_minorVersion = $version[2];
            }

            if (stripos($agent, 'Chrome/') !== false ||
                stripos($agent, 'CriOS/') !== false) {
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
            } elseif (stripos($agent, 'Safari/') !== false &&
                $this->_majorVersion >= 60) {
                // Safari.
                $this->setFeature('issafari');

                // Truly annoying - Safari did not start putting real version
                // numbers until Version 3.
                if (preg_match('|Version/([0-9.]+)|', $agent, $version_string)) {
                    list($this->_majorVersion, $this->_minorVersion) = explode('.', $version_string[1], 2);
                    $this->_minorVersion                             = (int) ($this->_minorVersion);
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
                    if ($this->_minorVersion >= 5 ||
                        $this->_majorVersion == 4) {
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
                    $this->setMobile(true);
                } elseif (stripos($agent, 'tablet') !== false) {
                    $this->setTablet(true);
                    $this->setMobile(true);
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
        } elseif (strpos($agent, 'UP/') !== false ||
                  strpos($agent, 'UP.B') !== false ||
                  strpos($agent, 'UP.L') !== false) {
            $this->setBrowser('up');
            $this->setFeature('html', false);
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');

            if (strpos($agent, 'GUI') !== false &&
                strpos($agent, 'UP.Link') !== false) {
                /* The device accepts Openwave GUI extensions for WML
                 * 1.3. Non-UP.Link gateways sometimes have problems,
                 * so exclude them. */
                $this->setQuirk('ow_gui_1.3');
            }
            $this->setMobile(true);
        } elseif (strpos($agent, 'Xiino/') !== false) {
            $this->setBrowser('xiino');
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->setMobile(true);
        } elseif (strpos($agent, 'Palmscape/') !== false) {
            $this->setBrowser('palmscape');
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->setMobile(true);
        } elseif (strpos($agent, 'Nokia') !== false) {
            $this->setBrowser('nokia');
            $this->setFeature('html', false);
            $this->setFeature('wml');
            $this->setFeature('xhtml');
            $this->setMobile(true);
        } elseif (strpos($agent, 'Ericsson') !== false) {
            $this->setBrowser('ericsson');
            $this->setFeature('html', false);
            $this->setFeature('wml');
            $this->setMobile(true);
        } elseif (strpos($agent, 'Grundig') !== false) {
            $this->setBrowser('grundig');
            $this->setFeature('xhtml');
            $this->setFeature('wml');
            $this->setMobile(true);
        } elseif (strpos($agent, 'NetFront') !== false) {
            $this->setBrowser('netfront');
            $this->setFeature('xhtml');
            $this->setFeature('wml');
            $this->setMobile(true);
        } elseif (strpos($lowerAgent, 'wap') !== false) {
            $this->setBrowser('wap');
            $this->setFeature('html', false);
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->setMobile(true);
        } elseif (strpos($lowerAgent, 'docomo') !== false ||
                  strpos($lowerAgent, 'portalmmm') !== false) {
            $this->setBrowser('imode');
            $this->setFeature('images', false);
            $this->setMobile(true);
        } elseif (preg_match('|BlackBerry.*?/([0-9.]+)|', $agent, $version)) {
            list($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
            $this->setBrowser('blackberry');
            $this->setFeature('html', false);
            $this->setFeature('javascript', false);
            $this->setFeature('hdml');
            $this->setFeature('wml');
            $this->setMobile(true);
            if ($this->_majorVersion >= 5 ||
                ($this->_majorVersion == 4 && $this->_minorVersion >= 6)) {
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
            $this->setMobile(true);
        } elseif (strpos($lowerAgent, 'j-') !== false) {
            $this->setBrowser('mml');
            $this->setMobile(true);
        }
    }

    /**
     * Matches the platform of the browser.
     *
     * This is a pretty simplistic implementation, but it's intended to let us
     * tell what line breaks to send, so it's good enough for its purpose.
     */
    protected function _setPlatform()
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
    public function getPlatform()
    {
        return $this->_platform;
    }

    /**
     * Sets the current browser.
     *
     * @param string $browser  The browser to set as current.
     */
    public function setBrowser($browser)
    {
        $this->_browser = $browser;
    }

    /**
     * Determines if the given browser is the same as the current.
     *
     * @param string $browser  The browser to check.
     *
     * @return boolean  Is the given browser the same as the current?
     */
    public function isBrowser($browser)
    {
        return ($this->_browser === $browser);
    }

    /**
     * Set this browser as a mobile device.
     *
     * @param boolean $mobile  True if the browser is a mobile device.
     */
    public function setMobile($mobile)
    {
        $this->_mobile = (bool) $mobile;
    }

    /**
     * Is the current browser to be a mobile device?
     *
     * @return boolean  True if we do, false if we don't.
     */
    public function isMobile()
    {
        return $this->_mobile;
    }

    /**
     * Set this browser as a tablet device.
     *
     * @since 2.1.0
     *
     * @param boolean $tablet  True if the browser is a tablet device.
     */
    public function setTablet($tablet)
    {
        $this->_tablet = (bool) $tablet;
    }

    /**
     * Is the current browser a tablet device? This is not 100% reliable, as
     * most browsers do not differentiate between smartphone and tablet
     * versions.
     *
     * @since 2.1.0
     *
     * @return boolean  True if we do, false if we don't.
     */
    public function isTablet()
    {
        return $this->_tablet;
    }

    /**
     * Is the browser a robot?
     *
     * @return boolean  True if browser is a known robot.
     */
    public function isRobot()
    {
        if ($this->_robotAgentRegexp === null) {
            $regex = array();
            foreach ($this->_robotAgents as $r) {
                $regex[] = preg_quote($r, '/');
            }
            $this->_robotAgentRegexp = '/' . implode('|', $regex) . '/';
        }

        return (bool)preg_match($this->_robotAgentRegexp, $this->_agent);
    }

    /**
     * Returns the current browser.
     *
     * @return string  The current browser.
     */
    public function getBrowser()
    {
        return $this->_browser;
    }

    /**
     * Returns the current browser's major version.
     *
     * @return integer  The current browser's major version.
     */
    public function getMajor()
    {
        return $this->_majorVersion;
    }

    /**
     * Returns the current browser's minor version.
     *
     * @return integer  The current browser's minor version.
     */
    public function getMinor()
    {
        return $this->_minorVersion;
    }

    /**
     * Returns the current browser's version.
     *
     * @return string  The current browser's version.
     */
    public function getVersion()
    {
        return $this->_majorVersion . '.' . $this->_minorVersion;
    }

    /**
     * Returns the full browser agent string.
     *
     * @return string  The browser agent string.
     */
    public function getAgentString()
    {
        return $this->_agent;
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
    public function setQuirk($quirk, $value = true)
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
     * @param string $quirk  The behavior to check.
     *
     * @return boolean  Does the browser have the behavior set?
     */
    public function hasQuirk($quirk)
    {
        return !empty($this->_quirks[$quirk]);
    }

    /**
     * Returns unique behavior for the current browser.
     *
     * @param string $quirk  The behavior to retrieve.
     *
     * @return string  The value for the requested behavior.
     */
    public function getQuirk($quirk)
    {
        return (isset($this->_quirks[$quirk])) ? $this->_quirks[$quirk] : null;
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
     * Checks the current browser capabilities.
     *
     * @param string $feature  The capability to check.
     *
     * @return boolean  Does the browser have the capability set?
     */
    public function hasFeature($feature)
    {
        return !empty($this->_features[$feature]);
    }

    /**
     * Returns the current browser capability.
     *
     * @param string $feature  The capability to retrieve.
     *
     * @return string  The value of the requested capability.
     */
    public function getFeature($feature)
    {
        return (isset($this->_features[$feature])) ? $this->_features[$feature] : null;
    }

    /**
     * Determines if we are using a secure (SSL) connection.
     *
     * @return boolean  True if using SSL, false if not.
     */
    public function usingSSLConnection()
    {
        return ((filter_has_var(INPUT_SERVER, 'HTTPS') &&
                 (Core::get_server('HTTPS') == 'on')) ||
                getenv('SSL_PROTOCOL_VERSION'));
    }

    /**
     * Returns the server protocol in use on the current server.
     *
     * @return string  The HTTP server protocol version.
     */
    public function getHTTPProtocol()
    {
        return (filter_has_var(INPUT_SERVER, 'SERVER_PROTOCOL') && ($pos = strrpos($_SERVER['SERVER_PROTOCOL'], '/'))) ? substr($_SERVER['SERVER_PROTOCOL'], $pos + 1) : null;
    }

    /**
     * DEPRECIATED FUNCTION, will be removed
     *
     * @return string  The client IP address.
     */
    public function getIPAddress()
    {
        return Core::get_user_ip();
    }

    /**
     * Determines if files can be uploaded to the system.
     *
     * @return integer  If uploads allowed, returns the maximum size of the
     *                  upload in bytes.  Returns 0 if uploads are not
     *                  allowed.
     */
    public static function allowFileUploads()
    {
        if (!ini_get('file_uploads') ||
            (($dir = ini_get('upload_tmp_dir')) &&
             !is_writable($dir))) {
            return 0;
        }

        $filesize = ini_get('upload_max_filesize');
        switch (strtolower(substr($filesize, -1, 1))) {
            case 'k':
                $filesize = (int) (floatval($filesize) * 1024);
                break;
            case 'm':
                $filesize = (int) (floatval($filesize) * 1024 * 1024);
                break;
            case 'g':
                $filesize = (int) (floatval($filesize) * 1024 * 1024 * 1024);
                break;
            default:
                $filesize = (int) ($filesize);
                break;
        }

        $postsize = ini_get('post_max_size');
        switch (strtolower(substr($postsize, -1, 1))) {
            case 'k':
                $postsize = (int) (floatval($postsize) * 1024);
                break;
            case 'm':
                $postsize = (int) (floatval($postsize) * 1024 * 1024);
                break;
            case 'g':
                $postsize = (int) (floatval($postsize) * 1024 * 1024 * 1024);
                break;
            default:
                $postsize = (int) ($postsize);
                break;
        }

        return min($filesize, $postsize);
    }

    /**
     * Determines if the file was uploaded or not.  If not, will return the
     * appropriate error message.
     *
     * @param string $field  The name of the field containing the uploaded
     *                       file.
     * @param string $name   The file description string to use in the error
     *                       message.  Default: 'file'.
     *
     * @throws Horde_Browser_Exception
     */
    public function wasFileUploaded($field, $name = null)
    {
        if ($name === null) {
            $name = 'file';
        }

        if (!($uploadSize = self::allowFileUploads())) {
            throw new Horde_Browser_Exception(Horde_Browser_Translation::t("File uploads not supported."));
        }

        /* Get any index on the field name. */
        $index = Horde_Array::getArrayParts($field, $base, $keys);

        if ($index) {
            /* Index present, fetch the error var to check. */
            $keys_path = array_merge(array($base, 'error'), $keys);
            $error     = Horde_Array::getElement($_FILES, $keys_path);

            /* Index present, fetch the tmp_name var to check. */
            $keys_path = array_merge(array($base, 'tmp_name'), $keys);
            $tmp_name  = Horde_Array::getElement($_FILES, $keys_path);
        } else {
            /* No index, simple set up of vars to check. */
            if (!isset($_FILES[$field])) {
                throw new Horde_Browser_Exception(Horde_Browser_Translation::t("No file uploaded"), UPLOAD_ERR_NO_FILE);
            }
            $error    = $_FILES[$field]['error'];
            $tmp_name = $_FILES[$field]['tmp_name'];
        }

        if (empty($_FILES) || ($error == UPLOAD_ERR_NO_FILE)) {
            throw new Horde_Browser_Exception(sprintf(Horde_Browser_Translation::t("There was a problem with the file upload: No %s was uploaded."), $name), UPLOAD_ERR_NO_FILE);
        } elseif (($error == UPLOAD_ERR_OK) && is_uploaded_file($tmp_name)) {
            if (!filesize($tmp_name)) {
                throw new Horde_Browser_Exception(Horde_Browser_Translation::t("The uploaded file appears to be empty. It may not exist on your computer."), UPLOAD_ERR_NO_FILE);
            }
            // SUCCESS
        } elseif (($error == UPLOAD_ERR_INI_SIZE) ||
                  ($error == UPLOAD_ERR_FORM_SIZE)) {
            throw new Horde_Browser_Exception(sprintf(Horde_Browser_Translation::t("There was a problem with the file upload: The %s was larger than the maximum allowed size (%d bytes)."), $name, $uploadSize), $error);
        } elseif ($error == UPLOAD_ERR_PARTIAL) {
            throw new Horde_Browser_Exception(sprintf(Horde_Browser_Translation::t("There was a problem with the file upload: The %s was only partially uploaded."), $name), $error);
        }
    }

    /**
     * Returns the headers for a browser download.
     *
     * @param string $filename  The filename of the download.
     * @param string $cType     The content-type description of the file.
     * @param boolean $inline   True if inline, false if attachment.
     * @param string $cLength   The content-length of this file.
     */
    public function downloadHeaders($filename = 'unknown', $cType = null, $inline = false, $cLength = null)
    {
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

        /* Content-Type/Content-Disposition Header. */
        if ($inline) {
            if ($cType !== null) {
                header('Content-Type: ' . trim($cType));
            } elseif ($this->isBrowser('msie')) {
                header('Content-Type: application/x-msdownload');
            } else {
                header('Content-Type: application/octet-stream');
            }
            header('Content-Disposition: inline; filename="' . $filename . '"');
        } else {
            if ($this->isBrowser('msie')) {
                header('Content-Type: application/x-msdownload');
            } elseif ($cType !== null) {
                header('Content-Type: ' . trim($cType));
            } else {
                header('Content-Type: application/octet-stream');
            }

            if ($this->hasQuirk('break_disposition_header')) {
                header('Content-Disposition: filename="' . $filename . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }
        }

        /* Content-Length Header. Only send if we are not compressing
         * output. */
        if ($cLength !== null &&
            !in_array('ob_gzhandler', ob_list_handlers())) {
            header('Content-Length: ' . $cLength);
        }

        /* Overwrite Pragma: and other caching headers for IE. */
        if ($this->hasQuirk('cache_ssl_downloads')) {
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
        }
    }

    /**
     * Determines if a browser can display a given MIME type.
     *
     * @param string $mimetype  The MIME type to check.
     *
     * @return boolean  True if the browser can display the MIME type.
     */
    public function isViewable($mimetype)
    {
        $mimetype             = strtolower($mimetype);
        list($type, $subtype) = explode('/', $mimetype);

        if (!empty($this->_accept)) {
            $wildcard_match = false;

            if (strpos($this->_accept, $mimetype) !== false) {
                return true;
            }

            if (strpos($this->_accept, '*/*') !== false) {
                $wildcard_match = true;
                if ($type != 'image') {
                    return true;
                }
            }

            /* image/jpeg and image/pjpeg *appear* to be the same entity, but
             * Mozilla doesn't seem to want to accept the latter.  For our
             * purposes, we will treat them the same. */
            if ($this->isBrowser('mozilla') &&
                ($mimetype == 'image/pjpeg') &&
                (strpos($this->_accept, 'image/jpeg') !== false)) {
                return true;
            }

            if (!$wildcard_match) {
                return false;
            }
        }

        if (!$this->hasFeature('images') || ($type != 'image')) {
            return false;
        }

        return in_array($subtype, $this->_images);
    }
}
