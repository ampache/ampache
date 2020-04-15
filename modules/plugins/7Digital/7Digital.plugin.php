<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class Ampache7digital
{
    public $name        = '7digital';
    public $categories  = 'misc,preview';
    public $description = 'Song preview from 7digital';
    public $url         = 'http://www.7digital.com';
    public $version     = '000001';
    public $min_ampache = '370015';
    public $max_ampache = '999999';

    private $api_key;
    private $secret;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Song preview from 7digital');
        require_once AmpConfig::get('prefix') . "/modules/oauth/OAuth.php";

        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {
        if (Preference::exists('7digital_api_key')) {
            return false;
        }
        Preference::insert('7digital_api_key', T_('7digital consumer key'), '', '75', 'string', 'plugins', $this->name);
        Preference::insert('7digital_secret_api_key', T_('7digital secret'), '', '75', 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('7digital_api_key');
        Preference::delete('7digital_secret_api_key');

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
     * Get song preview.
     * @param string $track_mbid
     * @param string $artist_name
     * @param string $title
     * @return array
     */
    public function get_song_preview($track_mbid, $artist_name, $title)
    {
        $file     = null;
        $echonest = new EchoNest_Client(new EchoNest_HttpClient_Requests());
        $echonest->authenticate(AmpConfig::get('echonest_api_key'));
        $enSong = null;
        try {
            $enProfile = $echonest->getTrackApi()->profile('musicbrainz:track:' . $track_mbid);
            $enSong    = $echonest->getSongApi()->profile($enProfile['song_id'], array( 'id:7digital-US', 'audio_summary', 'tracks'));
        } catch (Exception $error) {
            debug_event('7digital.plugin', 'EchoNest track error on `' . $track_mbid . '` (' . $title . '): ' . $error->getMessage(), 1);
        }

        // Wans't able to get the song with MusicBrainz ID, try a search
        if ($enSong == null) {
            try {
                $enSong = $echonest->getSongApi()->search(array(
                    'results' => '1',
                    'artist' => $artist_name,
                    'title' => $title,
                    'bucket' => array( 'id:7digital-US', 'audio_summary', 'tracks'),
                ));
            } catch (Exception $error) {
                debug_event('7digital.plugin', 'EchoNest song search error: ' . $error->getMessage(), 1);
            }
        }

        if ($enSong != null) {
            $file = $enSong[0]['tracks'][0]['preview_url'];

            debug_event('7digital.plugin', 'EchoNest `' . $title . '` preview: ' . $file, 1);
        }

        return $file;
    }

    public function stream_song_preview($file)
    {
        if (strpos($file, "7digital") !== false) {
            $consumer = new OAuthConsumer($this->api_key, $this->secret, null);
            $request  = OAuthRequest::from_consumer_and_token($consumer, null, 'GET', $file);
            $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, null);
            $url = $request->to_url();

            header("Location: " . $url);

            return false;
        }

        return false;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     * @param User $user
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim($data['7digital_api_key'])) || !strlen(trim($data['7digital_secret_api_key']))) {
            $data                            = array();
            $data['7digital_api_key']        = Preference::get_by_user(-1, '7digital_api_key');
            $data['7digital_secret_api_key'] = Preference::get_by_user(-1, '7digital_secret_api_key');
        }

        if (strlen(trim($data['7digital_api_key']))) {
            $this->api_key = trim($data['7digital_api_key']);
        } else {
            debug_event('7digital.plugin', 'No 7digital api key, song preview plugin skipped', 3);

            return false;
        }
        if (strlen(trim($data['7digital_secret_api_key']))) {
            $this->secret = trim($data['7digital_secret_api_key']);
        } else {
            debug_event('7digital.plugin', 'No 7digital secret, song preview plugin skipped', 3);

            return false;
        }

        return true;
    } // load
} // end Ampache7digital
;
