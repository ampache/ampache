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

/**
 * Ampache_RSS Class
 *
 */
class Ampache_RSS
{
    /**
     *  @var string $type
     */
    private $type;
    public $data;

    /**
     * Constructor
     * This takes a flagged.id and then pulls in the information for said flag entry
     */
    public function __construct($type)
    {
        $this->type = self::validate_type($type);
    } // constructor

    /**
     * get_xml
     * This returns the xmldocument for the current rss type, it calls a sub function that gathers the data
     * and then uses the xmlDATA class to build the document
     * @return string
     */
    public function get_xml($params = null)
    {
        if ($this->type === "podcast") {
            if ($params != null && is_array($params)) {
                $object_type = $params['object_type'];
                $object_id   = $params['object_id'];
                if (Core::is_library_item($object_type)) {
                    $libitem = new $object_type($object_id);
                    if ($libitem->id) {
                        $libitem->format();

                        return XML_Data::podcast($libitem);
                    }
                }
            }
        } else {
            // Function call name
            $data_function     = 'load_' . $this->type;
            $pub_date_function = 'pubdate_' . $this->type;

            $data     = call_user_func(array('Ampache_RSS', $data_function));
            $pub_date = null;
            if (method_exists('Ampache_RSS', $pub_date_function)) {
                $pub_date = call_user_func(array('Ampache_RSS', $pub_date_function));
            }

            XML_Data::set_type('rss');
            $xml_document = XML_Data::rss_feed($data, $this->get_title(), $pub_date);

            return $xml_document;
        }

        return null;
    } // get_xml

    /**
     * get_title
     * This returns the standardized title for the rss feed based on this->type
     * @return string
     */
    public function get_title()
    {
        $titles = array('now_playing' => T_('Now Playing'),
            'recently_played' => T_('Recently Played'),
            'latest_album' => T_('Newest Albums'),
            'latest_artist' => T_('Newest Artists'),
            'latest_shout' => T_('Newest Shouts')
        );

        return scrub_out(AmpConfig::get('site_title')) . ' - ' . $titles[$this->type];
    } // get_title

    /**
     * get_description
     * This returns the standardized description for the rss feed based on this->type
     * @return string
     */
    public function get_description()
    {
        //FIXME: For now don't do any kind of translating
        return 'Ampache RSS Feeds';
    } // get_description

    /**
     * validate_type
     * this returns a valid type for an rss feed, if the specified type is invalid it returns a default value
     * @param string $type
     * @return string
     */
    public static function validate_type($type)
    {
        $valid_types = array('now_playing', 'recently_played', 'latest_album', 'latest_artist', 'latest_shout', 'podcast');

        if (!in_array($type, $valid_types)) {
            return 'now_playing';
        }

        return $type;
    } // validate_type

    /**
      * get_display
     * This dumps out some html and an icon for the type of rss that we specify
     * @param string $type
     * @param string $title
     * @param array|null $params
     * @return string
     */
    public static function get_display($type = 'now_playing', $title = '', $params = null)
    {
        // Default to Now Playing
        $type = self::validate_type($type);

        $strparams = "";
        if ($params != null && is_array($params)) {
            foreach ($params as $key => $value) {
                $strparams .= "&" . scrub_out($key) . "=" . scrub_out($value);
            }
        }

        $string = '<a class="nohtml" href="' . AmpConfig::get('web_path') . '/rss.php?type=' . $type . $strparams . '">' . UI::get_icon('feed', T_('RSS Feed'));
        if (!empty($title)) {
            $string .= ' &nbsp;' . $title;
        }
        $string .= '</a>';

        return $string;
    } // get_display

    // type specific functions below, these are called semi-dynamically based on the current type //

    /**
     * load_now_playing
     * This loads in the Now Playing information. This is just the raw data with key=>value pairs that could be turned
     * into an xml document if we so wished
     * @return array
     */
    public static function load_now_playing()
    {
        $data = Stream::get_now_playing();

        $results    = array();
        $format     = AmpConfig::get('rss_format') ?: '%t - %a - %A';
        $string_map = array(
            '%t' => 'title',
            '%a' => 'artist',
            '%A' => 'album'
        );
        foreach ($data as $element) {
            $song        = $element['media'];
            $client      = $element['user'];
            $title       = $format;
            $description = $format;
            foreach ($string_map as $search => $replace) {
                $trep        = 'f_' . $replace;
                $drep        = 'f_' . $replace . '_full';
                $title       = str_replace($search, $song->$trep, $title);
                $description = str_replace($search, $song->$drep, $description);
            }
            $xml_array = array(
                    'title' => $title,
                    'link' => $song->link,
                    'description' => $description,
                    'comments' => $client->f_name . ' - ' . $element['agent'],
                    'pubDate' => date('r', (int) $element['expire'])
                    );
            $results[] = $xml_array;
        } // end foreach

        return $results;
    } // load_now_playing

    /**
     * pubdate_now_playing
     * this is the pub date we should use for the Now Playing information,
     * this is a little specific as it uses the 'newest' expire we can find
     * @return integer
     */
    public static function pubdate_now_playing()
    {
        // Little redundent, should be fixed by an improvement in the get_now_playing stuff
        $data = Stream::get_now_playing();

        $element = array_shift($data);

        return $element['expire'];
    } // pubdate_now_playing

    /**
     * load_recently_played
     * This loads in the Recently Played information and formats it up real nice like
     * @return array
     */
    public static function load_recently_played()
    {
        //FIXME: The time stuff should be centralized, it's currently in two places, lame

        $time_unit = array('', T_('seconds ago'), T_('minutes ago'), T_('hours ago'), T_('days ago'), T_('weeks ago'), T_('months ago'), T_('years ago'));
        $data      = Song::get_recently_played();

        $results = array();

        foreach ($data as $item) {
            $client = new User($item['user']);
            $song   = new Song($item['object_id']);
            if ($song->enabled) {
                $song->format();
                $amount     = (int) (time() - $item['date'] + 2);
                $final      = '0';
                $time_place = '0';
                while ($amount >= 1) {
                    $final = $amount;
                    $time_place++;
                    if ($time_place <= 2) {
                        $amount = floor($amount / 60);
                    }
                    if ($time_place == '3') {
                        $amount = floor($amount / 24);
                    }
                    if ($time_place == '4') {
                        $amount = floor($amount / 7);
                    }
                    if ($time_place == '5') {
                        $amount = floor($amount / 4);
                    }
                    if ($time_place == '6') {
                        $amount = floor($amount / 12);
                    }
                    if ($time_place > '6') {
                        $final = $amount . '+';
                        break;
                    }
                } // end while

                $time_string = $final . ' ' . $time_unit[$time_place];

                $xml_array = array('title' => $song->f_title . ' - ' . $song->f_artist . ' - ' . $song->f_album,
                            'link' => str_replace('&amp;', '&', $song->link),
                            'description' => $song->title . ' - ' . $song->f_artist_full . ' - ' . $song->f_album_full . ' - ' . $time_string,
                            'comments' => $client->username,
                            'pubDate' => date("r", (int) $item['date']));
                $results[] = $xml_array;
            }
        } // end foreach

        return $results;
    } // load_recently_played

    /**
     * load_latest_album
     * This loads in the latest added albums
     * @return array
     */
    public static function load_latest_album()
    {
        $ids = Stats::get_newest('album', 10);

        $results = array();

        foreach ($ids as $albumid) {
            $album = new Album($albumid);
            $album->format();

            $xml_array = array('title' => $album->f_name,
                    'link' => $album->link,
                    'description' => $album->f_artist_name . ' - ' . $album->f_name,
                    'image' => Art::url($album->id, 'album', null, 2),
                    'comments' => '',
                    'pubDate' => date("c", (int) $album->get_addtime_first_song())
            );
            $results[] = $xml_array;
        } // end foreach

        return $results;
    } // load_latest_album

    /**
     * load_latest_artist
     * This loads in the latest added artists
     * @return array
     */
    public static function load_latest_artist()
    {
        $ids = Stats::get_newest('artist', 10);

        $results = array();

        foreach ($ids as $artistid) {
            $artist = new Artist($artistid);
            $artist->format();

            $xml_array = array('title' => $artist->f_name,
                    'link' => $artist->link,
                    'description' => $artist->summary,
                    'image' => Art::url($artist->id, 'artist', null, 2),
                    'comments' => '',
                    'pubDate' => ''
            );
            $results[] = $xml_array;
        } // end foreach

        return $results;
    } // load_latest_artist

    /**
     * load_latest_shout
     * This loads in the latest added shouts
     * @return array
     */
    public static function load_latest_shout()
    {
        $ids = Shoutbox::get_top(10);

        $results = array();

        foreach ($ids as $shoutid) {
            $shout = new Shoutbox($shoutid);
            $shout->format();
            $object = Shoutbox::get_object($shout->object_type, $shout->object_id);
            if ($object !== null) {
                $object->format();
                $user = new User($shout->user);
                $user->format();

                $xml_array = array('title' => $user->username . ' ' . T_('on') . ' ' . $object->get_fullname(),
                        'link' => $object->link,
                        'description' => $shout->text,
                        'image' => Art::url($shout->object_id, $shout->object_type, null, 2),
                        'comments' => '',
                        'pubDate' => date("c", (int) $shout->date)
                );
                $results[] = $xml_array;
            }
        } // end foreach

        return $results;
    } // load_latest_shout

    /**
     * pubdate_recently_played
     * This just returns the 'newest' Recently Played entry
     * @return integer
     */
    public static function pubdate_recently_played()
    {
        $data = Song::get_recently_played();

        $element = array_shift($data);

        return $element['date'];
    } // pubdate_recently_played
} // end ampache_rss.class
