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
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\Ui;

class AmpacheHomeDashboard implements PluginDisplayHomeInterface
{
    public string $name        = 'Home Dashboard';
    public string $categories  = 'home';
    public string $description = 'Show Album dashboard sections on the homepage';
    public string $url         = '';
    public string $version     = '000002';
    public string $min_ampache = '370021';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private User $user;
    private int $maxitems;
    private bool $random;
    private bool $newest;
    private bool $recent;
    private bool $trending;
    private bool $popular;
    private int $order = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Show Album dashboard sections on the homepage');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('homedash_max_items', T_('Home Dashboard max items'), 6, AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('homedash_random', T_('Random'), '1', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('homedash_newest', T_('Newest'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('homedash_recent', T_('Recent'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('homedash_trending', T_('Trending'), '1', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('homedash_popular', T_('Popular'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('homedash_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
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
            Preference::delete('homedash_max_items') &&
            Preference::delete('homedash_newest') &&
            Preference::delete('homedash_random') &&
            Preference::delete('homedash_recent') &&
            Preference::delete('homedash_trending') &&
            Preference::delete('homedash_popular') &&
            Preference::delete('homedash_order')
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
            Preference::insert('homedash_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name);
        }

        return true;
    }

    /**
     * display_home
     * This display the module in home page
     */
    public function display_home(): void
    {
        if (
            !$this->newest &&
            !$this->random &&
            !$this->recent &&
            !$this->trending &&
            !$this->popular
        ) {
            return;
        }

        $divString = ($this->order > 0)
            ? '<div class="homedash" style="order: '. $this->order . '>'
            : '<div class="homedash">';
        echo $divString;

        $threshold   = AmpConfig::get('stats_threshold', 7);
        $limit       = $this->maxitems;
        $object_type = (AmpConfig::get('album_group'))
        ? 'album'
        : 'album_disk';

        $object_ids = ($this->random)
            ? self::getAlbumRepository()->getRandom($this->user->getId(), $limit)
            : [];
        if (!empty($object_ids)) {
            Ui::show_box_top(T_('Random'));
            $browse = new Browse();
            $browse->set_type($object_type);
            $browse->set_use_filters(false);
            $browse->set_show_header(false);
            $browse->set_grid_view(false, false);
            $browse->set_mashup(true);
            $browse->show_objects($object_ids);
            Ui::show_box_bottom();
        }

        $object_ids = ($this->newest)
            ? Stats::get_newest($object_type, $limit, 0, 0, $this->user)
            : [];
        if (!empty($object_ids)) {
            Ui::show_box_top(T_('Newest'));
            $browse = new Browse();
            $browse->set_type($object_type);
            $browse->set_use_filters(false);
            $browse->set_show_header(false);
            $browse->set_grid_view(false, false);
            $browse->set_mashup(true);
            $browse->show_objects($object_ids);
            Ui::show_box_bottom();
        }

        $object_ids = ($this->recent)
            ? Stats::get_recent($object_type, $limit)
            : [];
        if (!empty($object_ids)) {
            Ui::show_box_top(T_('Recent'));
            $browse = new Browse();
            $browse->set_type($object_type);
            $browse->set_use_filters(false);
            $browse->set_show_header(false);
            $browse->set_grid_view(false, false);
            $browse->set_mashup(true);
            $browse->show_objects($object_ids);
            Ui::show_box_bottom();
        }

        $object_ids = ($this->trending)
            ? Stats::get_top($object_type, $limit, $threshold)
            : [];
        if (!empty($object_ids)) {
            Ui::show_box_top(T_('Trending'));
            $browse = new Browse();
            $browse->set_type($object_type);
            $browse->set_use_filters(false);
            $browse->set_show_header(false);
            $browse->set_grid_view(false, false);
            $browse->set_mashup(true);
            $browse->show_objects($object_ids);
            Ui::show_box_bottom();
        }

        $object_ids = ($this->popular)
            ? Stats::get_top($object_type, 100, $threshold, 0, $this->user)
            : [];
        if (!empty($object_ids)) {
            shuffle($object_ids);
            $object_ids = array_slice($object_ids, 0, $limit);
            Ui::show_box_top(T_('Popular'));
            $browse     = new Browse();
            $browse->set_type($object_type);
            $browse->set_use_filters(false);
            $browse->set_show_header(false);
            $browse->set_grid_view(false, false);
            $browse->set_mashup(true);
            $browse->show_objects($object_ids);
            Ui::show_box_bottom();
        }

        echo '</div>';
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $this->user = $user;
        $user->set_preferences();
        $data = $user->prefs;

        $this->maxitems = (int)($data['homedash_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 12;
        }

        $this->random   = ($data['homedash_random'] == '1');
        $this->newest   = ($data['homedash_newest'] == '1');
        $this->recent   = ($data['homedash_recent'] == '1');
        $this->trending = ($data['homedash_trending'] == '1');
        $this->popular  = ($data['homedash_popular'] == '1');
        $this->order    = (int)($data['homedash_order'] ?? 0);

        return true;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }
}
