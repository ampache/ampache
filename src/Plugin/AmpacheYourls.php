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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Core;
use Exception;
use WpOrg\Requests\Requests;

class AmpacheYourls extends AmpachePlugin implements PluginShortenerInterface
{
    public string $name = 'YOURLS';

    public string $categories = 'shortener';

    public string $description = 'URL shorteners on shared links with YOURLS';

    public string $url = 'http://yourls.org';

    public string $version = '000002';

    public string $min_ampache = '360037';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $yourls_domain;

    private $yourls_use_idn;

    private $yourls_api_key;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('URL shorteners on shared links with YOURLS');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('yourls_domain', T_('YOURLS domain name'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('yourls_use_idn', T_('YOURLS use IDN'), '0', AccessLevelEnum::MANAGER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('yourls_api_key', T_('YOURLS API key'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('yourls_domain') &&
            Preference::delete('yourls_use_idn') &&
            Preference::delete('yourls_api_key')
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
     * shortener
     */
    public function shortener(string $url): ?string
    {
        if (empty($this->yourls_domain) || empty($this->yourls_api_key)) {
            debug_event('yourls.plugin', 'YOURLS domain or api key missing', 3);

            return null;
        }

        $shorturl = '';

        $apiurl = 'http://' . $this->yourls_domain . '/yourls-api.php?signature=' . $this->yourls_api_key . '&action=shorturl&format=simple&url=' . urlencode($url);
        try {
            debug_event('yourls.plugin', 'YOURLS api call: ' . $apiurl, 5);
            $request  = Requests::get($apiurl, [], Core::requests_options());
            $shorturl = $request->body;
            if ($this->yourls_use_idn) {
                // WARNING: idn_to_utf8 requires php-idn module.
                // WARNING: http_build_url requires php-pecl-http module.
                $purl = parse_url($shorturl);
                if (
                    is_array($purl) &&
                    array_key_exists('host', $purl)
                ) {
                    $purl['host'] = (string)idn_to_utf8($purl['host']);
                    $shorturl     = http_build_url($purl);
                }
            }
        } catch (Exception $exception) {
            debug_event('yourls.plugin', 'YOURLS api http exception: ' . $exception->getMessage(), 1);

            return null;
        }

        if (!$shorturl) {
            return null;
        }

        return $shorturl;
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
        if (!strlen(trim((string) $data['yourls_domain'])) || !strlen(trim((string) $data['yourls_api_key']))) {
            $data                   = [];
            $data['yourls_domain']  = Preference::get_by_user(-1, 'yourls_domain');
            $data['yourls_api_key'] = Preference::get_by_user(-1, 'yourls_api_key');
        }

        if (strlen(trim((string) $data['yourls_domain'])) !== 0) {
            $this->yourls_domain = trim((string) $data['yourls_domain']);
        } else {
            debug_event('yourls.plugin', 'No YOURLS domain, shortener skipped', 3);

            return false;
        }

        if (strlen(trim((string) $data['yourls_api_key'])) !== 0) {
            $this->yourls_api_key = trim((string) $data['yourls_api_key']);
        } else {
            debug_event('yourls.plugin', 'No YOURLS api key, shortener skipped', 3);

            return false;
        }

        $this->yourls_use_idn = ((int)($data['yourls_use_idn']) == 1);

        return true;
    }
}
