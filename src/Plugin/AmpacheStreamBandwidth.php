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
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\Graph;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

class AmpacheStreamBandwidth extends AmpachePlugin implements PluginStreamControlInterface
{
    public string $name        = 'Stream Bandwidth';

    public string $categories  = 'stream_control';

    public string $description = 'Control bandwidth per user';

    public string $url         = '';

    public string $version     = '000001';

    public string $min_ampache = '370024';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private int $user_id = 0;

    private int $bandwidth_days = 30;

    private int $bandwidth_max = 1024;

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
        if (!Preference::insert('stream_control_bandwidth_max', T_('Stream control maximal bandwidth (month)'), 1024, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('stream_control_bandwidth_days', T_('Stream control bandwidth history (days)'), 30, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name);
    }

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
     */
    public function stream_control(array $media_ids): bool
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
                /** @var Media $media */
                $media = new $className($media_id['object_id']);
                $next_total += $media->size ?? 0;
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
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->user_id       = $user->id;
        $this->bandwidth_max = (int)($data['stream_control_bandwidth_max']) ?: 1024;

        if ((int)($data['stream_control_bandwidth_days']) > 0) {
            $this->bandwidth_days = (int)($data['stream_control_bandwidth_days']);
        } else {
            $this->bandwidth_days = 30;
        }

        return true;
    }
}
