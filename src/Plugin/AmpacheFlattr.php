<?php

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
declare(strict_types=0);

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

class AmpacheFlattr implements AmpachePluginInterface
{
    public string $name        = 'Flattr';
    public string $categories  = 'user';
    public string $description = 'Flattr donation button on user page';
    public string $url         = '';
    public string $version     = '000001';
    public string $min_ampache = '370034';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $user;
    private $user_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Flattr donation button on user page');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::exists('flattr_user_id') && !Preference::insert('flattr_user_id', T_('Flattr User ID'), '', 25, 'string', 'plugins', $this->name)) {
            return false;
        }

        return true;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return Preference::delete('flattr_user_id');
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
     * @param library_item|null $libitem
     */
    public function display_user_field(library_item $libitem = null): void
    {
        $name = ($libitem != null) ? $libitem->get_fullname() : (T_('User') . " `" . $this->user->fullname . "` " . T_('on') . " " . AmpConfig::get('site_title'));
        $link = ($libitem != null && $libitem->get_link()) ? $libitem->get_link() : $this->user->get_link();

        echo "<a class='nohtml' href='https://flattr.com/submit/auto?user_id=" . scrub_out($this->user_id) . "&url=" . rawurlencode($link) . "&category=audio&title=" . rawurlencode($name) . "' target='_blank'><img src='//button.flattr.com/flattr-badge-large.png' alt='" . T_('Flattr this') . "' title='" . T_('Flattr this') . "'></a>";
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     * @param User $user
     */
    public function load($user): bool
    {
        $this->user = $user;
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim($data['flattr_user_id']))) {
            $data                   = array();
            $data['flattr_user_id'] = Preference::get_by_user(-1, 'flattr_user_id');
        }

        $this->user_id = trim($data['flattr_user_id']);
        if (!strlen($this->user_id)) {
            debug_event('flattr.plugin', 'No Flattr User ID, user field plugin skipped', 3);

            return false;
        }

        return true;
    }
}
