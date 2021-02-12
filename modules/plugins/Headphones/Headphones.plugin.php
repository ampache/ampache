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

class AmpacheHeadphones
{
    public $name        = 'Headphones';
    public $categories  = 'wanted';
    public $description = 'Automatically download accepted Wanted List albums with Headphones';
    public $url         = 'https://github.com/rembo10/headphones/';
    public $version     = '000001';
    public $min_ampache = '360030';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $api_url;
    private $api_key;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Automatically download accepted Wanted List albums with Headphones');

        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {

        // Check and see if it's already installed (they've just hit refresh, those dorks)
        if (Preference::exists('headphones_api_url')) {
            return false;
        }

        Preference::insert('headphones_api_url', T_('Headphones URL'), '', 25, 'string', 'plugins', $this->name);
        Preference::insert('headphones_api_key', T_('Headphones API key'), '', 25, 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('headphones_api_url');
        Preference::delete('headphones_api_key');

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
     * process_wanted
     * This takes care of auto-download accepted Wanted List albums
     * @param Wanted $wanted
     * @return boolean
     */
    public function process_wanted($wanted)
    {
        set_time_limit(0);

        $headartist = json_decode($this->headphones_call('getArtist', array(
            'id' => $wanted->artist_mbid
        )));

        // No artist info, need to add artist to Headphones first. Can be long!
        if (!$headartist->artist) {
            $this->headphones_call('addArtist', array(
                'id' => $wanted->artist_mbid
            ));
        }

        return ($this->headphones_call('queueAlbum', array(
            'id' => $wanted->mbid
        )) == 'OK');
    } // process_wanted

    /**
     * @param $command
     * @param $params
     * @return boolean
     */
    protected function headphones_call($command, $params)
    {
        if (empty($this->api_url) || empty($this->api_key)) {
            debug_event(self::class, 'Headphones url or api key missing', 3);

            return false;
        }

        $url = $this->api_url . '/api?apikey=' . $this->api_key . '&cmd=' . $command;
        foreach ($params as $key => $value) {
            $url .= '&' . $key . '=' . urlencode($value);
        }

        debug_event(self::class, 'Headphones api call: ' . $url, 5);
        try {
            // We assume Headphone server is local, don't use proxy here
            $request = Requests::get($url, array(), array(
                'timeout' => 600
            ));
        } catch (Exception $error) {
            debug_event(self::class, 'Headphones api http exception: ' . $error->getMessage(), 1);

            return false;
        }

        return $request->body;
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
        if (!strlen(trim($data['headphones_api_url'])) || !strlen(trim($data['headphones_api_key']))) {
            $data                       = array();
            $data['headphones_api_url'] = Preference::get_by_user(-1, 'headphones_api_url');
            $data['headphones_api_key'] = Preference::get_by_user(-1, 'headphones_api_key');
        }

        if (strlen(trim($data['headphones_api_url']))) {
            $this->api_url = rtrim(trim($data['headphones_api_url']), '/');
        } else {
            debug_event(self::class, 'No Headphones url, auto download skipped', 3);

            return false;
        }
        if (strlen(trim($data['headphones_api_key']))) {
            $this->api_key = trim($data['headphones_api_key']);
        } else {
            debug_event(self::class, 'No Headphones api key, auto download skipped', 3);

            return false;
        }

        return true;
    } // load
} // end AmpacheHeadphones
