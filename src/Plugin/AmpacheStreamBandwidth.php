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
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\Graph;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

class AmpacheStreamBandwidth implements AmpachePluginInterface
{
    public string $name        = 'Stream Bandwidth';
    public string $categories  = 'stream_control';
    public string $description = 'Control bandwidth per user';
    public string $url         = '';
    public string $version     = '000001';
    public string $min_ampache = '370024';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $user_id;
    private $bandwidth_days;
    private $bandwidth_max;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Control bandwidth per user');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::exists('stream_control_bandwidth_max') && !Preference::insert('stream_control_bandwidth_max', T_('Stream control maximal bandwidth (month)'), 1024, 50, 'integer', 'plugins', $this->name)) {
            return false;
        }
        if (!Preference::exists('stream_control_bandwidth_days') && !Preference::insert('stream_control_bandwidth_days', T_('Stream control bandwidth history (days)'), 30, 50, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return true;
    } // install

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('stream_control_bandwidth_max') &&
            Preference::delete('stream_control_bandwidth_days')
        );
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        return true;
    } // upgrade

    /**
     * Check stream control
     * @param array $media_ids
     */
    public function stream_control($media_ids): bool
    {
        // No check if unlimited bandwidth (= -1)
        if ($this->bandwidth_max < 0) {
            return true;
        }
        // if using free software only you can't use this plugin
        if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../../vendor/szymach/c-pchart/src/Chart/')) {
            // Calculate all media size
            $next_total = 0;
            foreach ($media_ids as $media_id) {
                $className = ObjectTypeToClassNameMapper::map($media_id['object_type']);
                $media     = new $className($media_id['object_id']);
                $next_total += $media->size;
            }

            $graph         = new Graph();
            $end_date      = time();
            $start_date    = $end_date - ($this->bandwidth_days * 86400);
            $current_total = $graph->get_total_bandwidth($this->user_id, $start_date, $end_date);
            $next_total += $current_total;
            $max = $this->bandwidth_max * 1024 * 1024;

            debug_event('streambandwidth.plugin', 'Next stream bandwidth will be ' . $next_total . ' / ' . $max, 3);

            return ($next_total <= $max);
        }
        debug_event('streambandwidth.plugin', 'Access denied, statistical graph disabled.', 1);

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
        if ((int)($data['stream_control_bandwidth_max'])) {
            $this->bandwidth_max = (int)($data['stream_control_bandwidth_max']);
        } else {
            $this->bandwidth_max = 1024;
        }
        if ((int)($data['stream_control_bandwidth_days']) > 0) {
            $this->bandwidth_days = (int)($data['stream_control_bandwidth_days']);
        } else {
            $this->bandwidth_days = 30;
        }

        return true;
    } // load
}
