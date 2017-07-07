<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class AmpacheStreamTime
{
    public $name        = 'Stream Time';
    public $categories  = 'stream_control';
    public $description = 'Stream Control Time per user';
    public $url         = '';
    public $version     = '000001';
    public $min_ampache = '370024';
    public $max_ampache = '999999';
    
    private $user_id;
    private $time_days;
    private $time_max;
    
    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {
        if (Preference::exists('stream_control_time_max')) {
            return false;
        }
        Preference::insert('stream_control_time_max', 'Stream control maximal time (Minutes)', '-1', '50', 'integer', 'plugins', $this->name);
        Preference::insert('stream_control_time_days', 'Stream control time history (days)', '30', '50', 'integer', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('stream_control_time_max');
        Preference::delete('stream_control_time_days');

        return true;
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        return true;
    } // upgrade

    /**
     * Check stream control
     * @param array $media_ids
     * @return boolean
     */
    public function stream_control($media_ids)
    {
        // No check if unlimited bandwidth (= -1)
        if ($this->time_max < 0) {
            return true;
        }
        
        // Calculate all media time
        $next_total = 0;
        foreach ($media_ids as $media_id) {
            $media = new $media_id['object_type']($media_id['object_id']);
            $next_total += $media->time;
        }
        
        $graph         = new Graph();
        $end_date      = time();
        $start_date    = $end_date - ($this->time_days * 86400);
        $current_total = $graph->get_total_time($this->user_id, $start_date, $end_date);
        $next_total += $current_total;
        $max = $this->time_max * 60;
        
        debug_event('stream_control_time', 'Next stream time will be ' . $next_total . ' / ' . $max, 3);
        
        return ($next_total <= $max);
    }
    
    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;
        
        $this->user_id = $user->id;
        if (intval($data['stream_control_time_max'])) {
            $this->time_max = intval($data['stream_control_time_max']);
        } else {
            $this->time_max = 1024;
        }
        if (intval($data['stream_control_time_days']) > 0) {
            $this->time_days = intval($data['stream_control_time_days']);
        } else {
            $this->time_days = 30;
        }
        
        return true;
    } // load
}
