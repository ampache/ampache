<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class AmpacheYourls
{
    public $name        = 'YOURLS';
    public $categories  = 'shortener';
    public $description = 'Url shorteners on shared links with YOURLS';
    public $url         = 'http://yourls.org';
    public $version     = '000002';
    public $min_ampache = '360037';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $yourls_domain;
    private $yourls_use_idn;
    private $yourls_api_key;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {

        // Check and see if it's already installed (they've just hit refresh, those dorks)
        if (Preference::exists('yourls_domain')) {
            return false;
        }

        Preference::insert('yourls_domain', 'YOURLS domain name', '', '75', 'string', 'plugins', $this->name);
        Preference::insert('yourls_use_idn', 'YOURLS use IDN', '0', '75', 'boolean', 'plugins', $this->name);
        Preference::insert('yourls_api_key', 'YOURLS api key', '', '75', 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('yourls_domain');
        Preference::delete('yourls_use_idn');
        Preference::delete('yourls_api_key');
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        return true;
    } // upgrade

    public function shortener($url)
    {
        if (empty($this->yourls_domain) || empty($this->yourls_api_key)) {
            debug_event($this->name, 'YOURLS domain or api key missing', '3');
            return false;
        }
        
        $shorturl = '';
    
        $apiurl = 'http://' . $this->yourls_domain . '/yourls-api.php?signature=' . $this->yourls_api_key . '&action=shorturl&format=simple&url=' . urlencode($url);
        try {
            debug_event($this->name, 'YOURLS api call: ' . $apiurl, '5');
            $request  = Requests::get($apiurl, array(), Core::requests_options());
            $shorturl = $request->body;
            if ($this->yourls_use_idn) {
                // WARNING: idn_to_utf8 requires php-idn module.
                // WARNING: http_build_url requires php-pecl-http module.
                $purl         = parse_url($shorturl);
                $purl['host'] = idn_to_utf8($purl['host']);
                $shorturl     = http_build_url($purl);
            }
        } catch (Exception $e) {
            debug_event($this->name, 'YOURLS api http exception: ' . $e->getMessage(), '1');
            return false;
        }
        
        return $shorturl;
    }
    
    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['yourls_domain']))) {
            $this->yourls_domain = trim($data['yourls_domain']);
        } else {
            debug_event($this->name, 'No YOURLS domain, shortener skipped', '3');
            return false;
        }
        if (strlen(trim($data['yourls_api_key']))) {
            $this->yourls_api_key = trim($data['yourls_api_key']);
        } else {
            debug_event($this->name, 'No YOURLS api key, shortener skipped', '3');
            return false;
        }
        
        $this->yourls_use_idn = (intval($data['yourls_use_idn']) == 1);

        return true;
    } // load
} // end AmpacheYourls
;
