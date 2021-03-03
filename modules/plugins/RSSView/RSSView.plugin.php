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

class AmpacheRSSView
{
    public $name           = 'RSSView';
    public $categories     = 'home';
    public $description    = 'RSS View';
    public $url            = '';
    public $version        = '000001';
    public $min_ampache    = '370021';
    public $max_ampache    = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $feed_url;
    private $maxitems;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('RSS View');

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
        if (Preference::exists('rssview_feed_url')) {
            return false;
        }

        Preference::insert('rssview_feed_url', T_('RSS Feed URL'), '', 25, 'string', 'plugins', $this->name);
        Preference::insert('rssview_max_items', T_('RSS Feed max items'), 5, 25, 'integer', 'plugins', $this->name);

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('rssview_feed_url');
        Preference::delete('rssview_max_items');

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
     * display_home
     * This display the module in home page
     */
    public function display_home()
    {
        $xmlstr      = file_get_contents($this->feed_url, false, stream_context_create(Core::requests_options()));
        $xml         = simplexml_load_string($xmlstr);
        $time_format = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
        if ($xml->channel) {
            UI::show_box_top($xml->channel->title);
            $count = 0;
            echo '<div class="home_plugin"><table class="tabledata">';
            foreach ($xml->channel->item as $item) {
                echo '<tr class="' . ((($count % 2) == 0) ? 'even' : 'odd') . '"><td>';
                echo '<div>';
                echo '<div style="float: left; font-weight: bold;"><a href="' . $item->link . '" target="_blank">' . $item->title . '</a></div>';
                echo '<div style="float: right;">' . get_datetime($time_format, strtotime($item->pubDate)) . '</div>';
                echo '</div><br />';
                echo '<div style="margin-left: 30px;">';
                if (isset($item->image)) {
                    echo '<div style="float: left; margin-right: 20px;"><img src="' . $item->image . '" style="width: auto; max-height: 48px;" /></div>';
                }
                echo '<div>' . $item->description . '</div>';
                echo '</div>';
                echo '</td></tr>';

                $count++;
                if ($count >= $this->maxitems) {
                    break;
                }
            }
            echo '</table></div>';
            UI::show_box_bottom();
        }
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

        if (strlen(trim($data['rssview_feed_url']))) {
            $this->feed_url = trim($data['rssview_feed_url']);
        } else {
            debug_event(self::class, 'No rss feed url, home plugin skipped', 3);

            return false;
        }
        $this->maxitems = (int) ($data['rssview_max_items']);

        return true;
    }
}
