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

class AmpacheFlattr
{
    public $name           = 'Flattr';
    public $categories     = 'user';
    public $description    = 'Flattr donation button on user page';
    public $url            = '';
    public $version        = '000001';
    public $min_ampache    = '370034';
    public $max_ampache    = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $user;
    private $user_id;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Flattr donation button on user page');

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
        if (Preference::exists('flattr_user_id')) {
            return false;
        }

        Preference::insert('flattr_user_id', T_('Flattr User ID'), '', 25, 'string', 'plugins', $this->name);

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('flattr_user_id');

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
     * @param library_item|null $libitem
     */
    public function display_user_field(library_item $libitem = null)
    {
        $name = ($libitem != null) ? $libitem->get_fullname() : (T_('User') . " `" . $this->user->fullname . "` " . T_('on') . " " . AmpConfig::get('site_title'));
        $link = ($libitem != null && $libitem->link) ? $libitem->link : $this->user->link;

        echo "<a class='nohtml' href='https://flattr.com/submit/auto?user_id=" . scrub_out($this->user_id) . "&url=" . rawurlencode($link) . "&category=audio&title=" . rawurlencode($name) . "' target='_blank'><img src='//button.flattr.com/flattr-badge-large.png' alt='" . T_('Flattr this') . "' title='" . T_('Flattr this') . "'></a>";
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
