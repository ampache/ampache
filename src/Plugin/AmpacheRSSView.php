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
use Ampache\Module\Util\Ui;

class AmpacheRSSView implements PluginDisplayHomeInterface
{
    public string $name        = 'RSSView';
    public string $categories  = 'home';
    public string $description = 'RSS View';
    public string $url         = '';
    public string $version     = '000001';
    public string $min_ampache = '370021';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $feed_url;
    private $maxitems;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('RSS View');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('rssview_feed_url', T_('RSS Feed URL'), '', AccessLevelEnum::USER->value, 'string', 'plugins', $this->name)) {
            return false;
        }
        if (!Preference::insert('rssview_max_items', T_('RSS Feed max items'), 5, AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
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
        return (
            Preference::delete('rssview_feed_url') &&
            Preference::delete('rssview_max_items')
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
     * display_home
     * This display the module in home page
     */
    public function display_home(): void
    {
        $xmlstr = file_get_contents($this->feed_url, false, stream_context_create(Core::requests_options()));
        $xml    = ($xmlstr)
            ? simplexml_load_string($xmlstr)
            : false;
        if ($xml && $xml->channel) {
            Ui::show_box_top($xml->channel->title);
            $count = 0;
            echo '<div class="home_plugin"><table class="tabledata striped-rows">';
            foreach ($xml->channel->item as $item) {
                echo '<tr><td>';
                echo '<div>';
                echo '<div style="float: left; font-weight: bold;"><a href="' . $item->link . '" target="_blank">' . $item->title . '</a></div>';
                echo '<div style="float: right;">' . get_datetime((int) strtotime($item->pubDate), 'short', 'short', "m/d/Y H:i") . '</div>';
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
            Ui::show_box_bottom();
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     * @param User $user
     */
    public function load($user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['rssview_feed_url']))) {
            $this->feed_url = trim($data['rssview_feed_url']);
        } else {
            debug_event(self::class, 'No rss feed url, home plugin skipped', 3);

            return false;
        }
        $this->maxitems = (int)($data['rssview_max_items']);

        return true;
    }
}
