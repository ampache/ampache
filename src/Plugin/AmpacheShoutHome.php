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
use Ampache\Module\Shout\ShoutRendererInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\Ui;
use Ampache\Repository\ShoutRepositoryInterface;

class AmpacheShoutHome extends AmpachePlugin implements PluginDisplayHomeInterface
{
    public string $name        = 'Shout Home';

    public string $categories  = 'home';

    public string $description = 'Shoutbox on homepage';

    public string $url         = '';

    public string $version     = '000002';

    public string $min_ampache = '370021';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $maxitems;

    private int $order = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Shoutbox on homepage');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('shouthome_max_items', T_('Shoutbox on homepage max items'), 5, AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('shouthome_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('shouthome_max_items') &&
            Preference::delete('shouthome_order')
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
            Preference::insert('shouthome_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name);
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
            $divString = ($this->order > 0)
                ? '<div id="shout_objects" style="order: ' . $this->order . '"></br>'
                : '<div id="shout_objects"></br>';
            echo $divString;
            $shouts = iterator_to_array(
                self::getShoutRepository()->getTop((int) $this->maxitems)
            );
            $shoutRenderer = $this->getShoutRenderer();

            if ($shouts !== []) {
                require_once Ui::find_template('show_shoutbox.inc.php');
            }

            echo "</div>\n";
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

        $this->maxitems = (int)($data['shouthome_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 5;
        }

        $this->order = (int)($data['shouthome_order'] ?? 0);

        return true;
    }

    /**
     * @todo find a better solution...
     */
    private function getShoutRepository(): ShoutRepositoryInterface
    {
        global $dic;

        return $dic->get(ShoutRepositoryInterface::class);
    }

    /**
     * @todo find a better solution...
     */
    private function getShoutRenderer(): ShoutRendererInterface
    {
        global $dic;

        return $dic->get(ShoutRendererInterface::class);
    }
}
