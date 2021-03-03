<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

class AmpacheGoogleMaps
{
    public $name        = 'GoogleMaps';
    public $categories  = 'geolocation';
    public $description = 'Show user\'s location with Google Maps';
    public $url         = 'http://maps.google.com';
    public $version     = '000001';
    public $min_ampache = '370022';
    public $max_ampache = '999999';

    private $api_key;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_("Show user's location with Google Maps");

        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {
        if (Preference::exists('gmaps_api_key')) {
            return false;
        }
        Preference::insert('gmaps_api_key', T_('Google Maps API key'), '', 75, 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('gmaps_api_key');

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
     * @param $latitude
     * @param $longitude
     * @return string
     */
    public function get_location_name($latitude, $longitude)
    {
        $name = "";
        try {
            $url     = "http://maps.googleapis.com/maps/api/geocode/json?latlng=" . $latitude . "," . $longitude . "&sensor=false";
            $request = Requests::get($url, array(), Core::requests_options());

            $place = json_decode($request->body, true);
            if (count($place['results']) > 0) {
                $name = $place['results'][0]['formatted_address'];
            }
        } catch (Exception $error) {
            debug_event(self::class, 'Error getting location name: ' . $error->getMessage(), 1);
        }

        return $name;
    }

    /**
     * @param $pts
     * @return boolean
     */
    public function display_map($pts)
    {
        if (!$this->api_key) {
            debug_event(self::class, 'Missing API key, display map plugin skipped.', 3);

            return false;
        }

        echo '<script>' . "\n";
        echo 'function map_ready() {' . "\n";
        echo 'var mapOptions = {' . "\n";
        if (count($pts) > 0) {
            echo 'center: { lat: ' . $pts[0]['latitude'] . ', lng: ' . $pts[0]['longitude'] . ' }, ' . "\n";
        } else {
            // No geolocation data? Display `Paris` city.
            echo 'center: { lat: 48.853, lng: 2.348 }, ' . "\n";
        }
        echo 'zoom: 11' . "\n";
        echo '};' . "\n";
        echo 'var map = new google.maps.Map(document.getElementById("map-canvas"), ' . "\n";
        echo 'mapOptions);' . "\n";
        echo 'var marker;' . "\n";
        foreach ($pts as $pt) {
            $ptdescr = T_("Hits") . ": " . $pt['hits'] . "\\n";
            $ptdescr .= T_("Last activity") . ": " . date("r", $pt['last_date']);
            if (!empty($pt['name'])) {
                $ptdescr = $pt['name'] . "\\n" . $ptdescr;
            }
            echo 'marker = new google.maps.Marker({' . "\n";
            echo 'position: { lat: ' . $pt['latitude'] . ', lng: ' . $pt['longitude'] . ' }, ' . "\n";
            echo 'title:"' . $ptdescr . '"' . "\n";
            echo '});' . "\n";
            echo 'marker.setMap(map);' . "\n";
        }
        echo '}' . "\n";

        echo 'function loadMapScript() {' . "\n";
        echo 'var script = document.createElement("script");' . "\n";
        echo 'script.src = "https://maps.googleapis.com/maps/api/js?key=' . $this->api_key . '&" + "callback=map_ready";' . "\n";
        echo 'document.body.appendChild(script);' . "\n";
        echo '}' . "\n";
        echo 'loadMapScript();';

        echo '</script>' . "\n";
        echo '<div id="map-canvas" style="display: inline-block; height: 300px; width:680px; margin: 0; padding: 0;"></div>' . "\n";

        return true;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim($data['gmaps_api_key']))) {
            $data                  = array();
            $data['gmaps_api_key'] = Preference::get_by_user(-1, 'gmaps_api_key');
        }

        if (strlen(trim($data['gmaps_api_key']))) {
            $this->api_key = trim($data['gmaps_api_key']);
        }

        return true;
    } // load
}
