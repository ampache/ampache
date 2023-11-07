<?php
/*
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
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Module\System\Core;
use Ampache\Module\User\Activity\UserActivityRendererInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\UserActivityRepositoryInterface;

class AmpacheFriendsTimeline
{
    public $name        = 'Friends Timeline';
    public $categories  = 'home';
    public $description = 'Friends Timeline on homepage';
    public $url         = '';
    public $version     = '000001';
    public $min_ampache = '370040';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $maxitems;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_("Friend's Timeline on homepage");

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
        if (Preference::exists('ftl_max_items')) {
            return false;
        }

        Preference::insert('ftl_max_items', T_('Friends timeline max items'), 5, 25, 'integer', 'plugins', $this->name);

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('ftl_max_items');

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
        if (AmpConfig::get('sociable')) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? false;
            if ($user_id) {
                echo '<div class="home_plugin">';
                $activities = $this->getUseractivityRepository()->getFriendsActivities(
                    (int) $user_id,
                    (int) $this->maxitems
                );
                if (!empty($activities)) {
                    Ui::show_box_top(T_('Friends Timeline'));
                    Useractivity::build_cache($activities);

                    $activityRenderer = $this->getUserActivityRenderer();

                    foreach ($activities as $activity_id) {
                        echo $activityRenderer->show(
                            new Useractivity($activity_id)
                        );
                    }
                    Ui::show_box_bottom();
                }
                echo '</div>';
            }
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     * @param User $user
     * @return bool
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->maxitems = (int)($data['ftl_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 10;
        }

        return true;
    }

    /**
     * @deprecated
     */
    private function getUseractivityRepository(): UserActivityRepositoryInterface
    {
        global $dic;

        return $dic->get(UserActivityRepositoryInterface::class);
    }

    /**
     * @deprecated
     */
    private function getUserActivityRenderer(): UserActivityRendererInterface
    {
        global $dic;

        return $dic->get(UserActivityRendererInterface::class);
    }
}
