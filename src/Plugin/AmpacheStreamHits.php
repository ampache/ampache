<?php

declare(strict_types=0);

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

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\Graph;

class AmpacheStreamHits implements AmpachePluginInterface
{
    public string $name        = 'Stream Hits';
    public string $categories  = 'stream_control';
    public string $description = 'Control hits per user';
    public string $url         = '';
    public string $version     = '000001';
    public string $min_ampache = '370024';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $user_id;
    private $hits_days;
    private $hits_max;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Control hits per user');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('stream_control_hits_max', T_('Stream control maximal hits'), -1, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }
        if (Preference::insert('stream_control_hits_days', T_('Stream control hits history (days)'), 30, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name)) {
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
            Preference::delete('stream_control_hits_max') &&
            Preference::delete('stream_control_hits_days')
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
     * Check stream control
     * @param array $media_ids
     */
    public function stream_control($media_ids): bool
    {
        // No check if unlimited hits (= -1)
        if ($this->hits_max < 0) {
            return true;
        }
        // if using free software only you can't use this plugin
        if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../../vendor/szymach/c-pchart/src/Chart/')) {
            $next_total    = count($media_ids);
            $graph         = new Graph();
            $end_date      = time();
            $start_date    = $end_date - ($this->hits_days * 86400);
            $current_total = $graph->get_total_hits($this->user_id, $start_date, $end_date);
            $next_total += $current_total;

            debug_event('streamhits.plugin', 'Next stream hits will be ' . $next_total . ' / ' . $this->hits_max, 3);

            return ($next_total <= $this->hits_max);
        }
        debug_event('streamhits.plugin', 'Access denied, statistical graph disabled.', 1);

        return true;
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

        $this->user_id = $user->id;
        if ((int)($data['stream_control_hits_max'])) {
            $this->hits_max = (int)($data['stream_control_hits_max']);
        } else {
            $this->hits_max = -1;
        }
        if ((int)($data['stream_control_hits_days']) > 0) {
            $this->hits_days = (int)($data['stream_control_hits_days']);
        } else {
            $this->hits_days = 30;
        }

        return true;
    }
}
