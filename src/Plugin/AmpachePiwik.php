<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Core;

class AmpachePiwik extends AmpachePlugin implements PluginDisplayOnFooterInterface
{
    public string $name        = 'Piwik';

    public string $categories  = 'stats';

    public string $description = 'Piwik statistics';

    public string $url         = '';

    public string $version     = '000001';

    public string $min_ampache = '370034';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $site_id;

    private $piwik_url;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Piwik statistics');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('piwik_site_id', T_('Piwik Site ID'), '1', AccessLevelEnum::ADMIN->value, 'string', 'plugins', 'piwik')) {
            return false;
        }

        return Preference::insert('piwik_url', T_('Piwik URL'), AmpConfig::get_web_path() . '/piwik/', AccessLevelEnum::ADMIN->value, 'string', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('piwik_site_id') &&
            Preference::delete('piwik_url')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        return true;
    }

    /**
     * display_user_field
     * This display the module in user page
     */
    public function display_on_footer(): void
    {
        $currentUrl = scrub_out("http" . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . Core::get_server('HTTP_HOST') . Core::get_server('REQUEST_URI'));
        echo "<!-- Piwik -->\n";
        echo "<script>\n";
        echo "var _paq = _paq || [];\n";
        echo "_paq.push(['trackLink', '" . $currentUrl . "', 'link']);\n";
        echo "_paq.push(['enableLinkTracking']);\n";
        echo "(function() {\n";
        echo "var u='" . scrub_out($this->piwik_url) . "';\n";
        echo "_paq.push(['setTrackerUrl', u+'piwik.php']);\n";
        echo "_paq.push(['setSiteId', " . scrub_out($this->site_id) . "]);\n";
        if (Core::get_global('user')?->getId() > 0) {
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
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim((string) $data['piwik_site_id'])) || !strlen(trim((string) $data['piwik_url']))) {
            $data                  = [];
            $data['piwik_site_id'] = Preference::get_by_user(-1, 'piwik_site_id');
            $data['piwik_url']     = Preference::get_by_user(-1, 'piwik_url');
        }

        $this->site_id = trim((string) $data['piwik_site_id']);
        if ($this->site_id === '') {
            debug_event('piwik.plugin', 'No Piwik Site ID, user field plugin skipped', 3);

            return false;
        }

        $this->piwik_url = trim((string) $data['piwik_url']);
        if ($this->piwik_url === '') {
            debug_event('piwik.plugin', 'No Piwik URL, user field plugin skipped', 3);

            return false;
        }

        return true;
    }
}
