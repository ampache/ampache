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

class AmpachePiwik
{
    public $name           = 'Piwik';
    public $categories     = 'stats';
    public $description    = 'Piwik statistics';
    public $url            = '';
    public $version        = '000001';
    public $min_ampache    = '370034';
    public $max_ampache    = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $site_id;
    private $piwik_url;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Piwik statistics');

        return true;
    }

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {
        // Check and see if it's already installed
        if (Preference::exists('piwik_site_id')) {
            return false;
        }

        Preference::insert('piwik_site_id', T_('Piwik Site ID'), '1', 100, 'string', 'plugins', 'piwik');
        Preference::insert('piwik_url', T_('Piwik URL'), AmpConfig::get('web_path') . '/piwik/', 100, 'string', 'plugins', $this->name);

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('piwik_site_id');
        Preference::delete('piwik_url');

        return true;
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        return true;
    }

    /**
     * display_user_field
     * This display the module in user page
     */
    public function display_on_footer()
    {
        $currentUrl = scrub_out("http" . (filter_has_var(INPUT_SERVER, 'HTTPS') ? 's' : '') . '://' . Core::get_server('HTTP_HOST') . Core::get_server('REQUEST_URI'));

        echo "<!-- Piwik -->\n";
        echo "<script>\n";
        echo "var _paq = _paq || [];\n";
        echo "_paq.push(['trackLink', '" . $currentUrl . "', 'link']);\n";
        echo "_paq.push(['enableLinkTracking']);\n";
        echo "(function() {\n";
        echo "var u='" . scrub_out($this->piwik_url) . "';\n";
        echo "_paq.push(['setTrackerUrl', u+'piwik.php']);\n";
        echo "_paq.push(['setSiteId', " . scrub_out($this->site_id) . "]);\n";
        if (Core::get_global('user')->id > 0) {
            echo "_paq.push(['setUserId', '" . Core::get_global('user')->username . "']);\n";
        }
        echo "var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];\n";
        echo "g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);\n";
        echo "})();\n";
        echo "</script>\n";
        echo "<noscript><p><img src='" . scrub_out($this->piwik_url) . "piwik.php?idsite=" . scrub_out($this->site_id) . "' style='border:0;' alt= '' /></p></noscript>\n";
        echo "<!-- End Piwik Code -->\n";
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim($data['piwik_site_id'])) || !strlen(trim($data['piwik_url']))) {
            $data                  = array();
            $data['piwik_site_id'] = Preference::get_by_user(-1, 'piwik_site_id');
            $data['piwik_url']     = Preference::get_by_user(-1, 'piwik_url');
        }

        $this->site_id = trim($data['piwik_site_id']);
        if (!strlen($this->site_id)) {
            debug_event('piwik.plugin', 'No Piwik Site ID, user field plugin skipped', 3);

            return false;
        }

        $this->piwik_url = trim($data['piwik_url']);
        if (!strlen($this->piwik_url)) {
            debug_event('piwik.plugin', 'No Piwik URL, user field plugin skipped', 3);

            return false;
        }

        return true;
    }
}
