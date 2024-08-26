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
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Module\System\Core;
use Ampache\Module\User\Activity\UserActivityRendererInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\UserActivityRepositoryInterface;

class AmpacheFriendsTimeline implements PluginDisplayHomeInterface
{
    public string $name        = 'Friends Timeline';
    public string $categories  = 'home';
    public string $description = 'Friends Timeline on homepage';
    public string $url         = '';
    public string $version     = '000002';
    public string $min_ampache = '370040';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $maxitems;
    private int $order = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_("Friend's Timeline on homepage");
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('ftl_max_items', T_('Friends timeline max items'), 5, AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('ftl_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
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
            Preference::delete('ftl_max_items') &&
            Preference::delete('ftl_order')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version == 0) {
            return false;
        }

        if ($from_version < (int)$this->version) {
            Preference::insert('ftl_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name);
        }

        return true;
    }

    /**
     * display_home
     * This display the module in home page
     */
    public function display_home(): void
    {
        if (AmpConfig::get('sociable')) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
            if ($user_id) {
                $divString = ($this->order > 0)
                    ? '<div class="ftl" style="order: '. $this->order . '>'
                    : '<div class="ftl">';
                echo $divString;
                $activities = $this->getUseractivityRepository()->getFriendsActivities(
                    $user_id,
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
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->maxitems = (int)($data['ftl_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 10;
        }

        $this->order = (int)($data['ftl_order'] ?? 0);

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
