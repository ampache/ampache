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

class AmpacheStreamTime extends AmpachePlugin implements PluginStreamControlInterface
{
    public string $name = 'Stream Time';

    public string $categories = 'stream_control';

    public string $description = 'Control time per user';

    public string $url = '';

    public string $version = '000001';

    public string $min_ampache = '370024';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private int $user_id;

    private int $time_days;

    private int $time_max;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Control time per user');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('stream_control_time_max', T_('Stream control maximal time (minutes)'), -1, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('stream_control_time_days', T_('Stream control time history (days)'), 30, AccessLevelEnum::CONTENT_MANAGER->value, 'integer', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('stream_control_time_max') &&
            Preference::delete('stream_control_time_days')
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
        if ($this->time_max < 0) {
            return true;
        }

        // if using free software only you can't use this plugin
        if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../../vendor/szymach/c-pchart/src/Chart/')) {
            // Calculate all media time
            $next_total = 0;
            foreach ($media_ids as $media_id) {
                $className = ObjectTypeToClassNameMapper::map($media_id['object_type']);
                /** @var Media $media */
                $media = new $className($media_id['object_id']);
                $next_total += $media->time ?? 0;
            }

            $graph         = new Graph();
            $end_date      = time();
            $start_date    = $end_date - ($this->time_days * 86400);
            $current_total = $graph->get_total_time($this->user_id, $start_date, $end_date);
            $next_total += $current_total;
            $max = $this->time_max * 60;

            debug_event('streamtime.plugin', 'Next stream time will be ' . $next_total . ' / ' . $max, 3);

            return ($next_total <= $max);
        }

        debug_event('streamtime.plugin', 'Access denied, statistical graph disabled.', 1);

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

        $this->user_id   = $user->id;
        $this->time_max  = (int)($data['stream_control_time_max']) ?: 1024;
        $this->time_days = ((int)($data['stream_control_time_days']) > 0)
            ? (int)($data['stream_control_time_days'])
            : 30;

        return true;
    }
}
