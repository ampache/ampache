<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 *
 * This class takes the information pulled from getID3 and returns it in a
 * Ampache-friendly way.
 *
 */
class vainfo
{
    public $encoding = '';
    public $encoding_id3v1 = '';
    public $encoding_id3v2 = '';

    public $filename = '';
    public $type = '';
    public $tags = array();
    public $islocal;

    protected $_raw = array();
    protected $_getID3 = '';
    protected $_forcedSize = 0;

    protected $_file_encoding = '';
    protected $_file_pattern = '';
    protected $_dir_pattern = '';

    private $_pathinfo;
    private $_broken = false;

    /**
     * Constructor
     *
     * This function just sets up the class, it doesn't pull the information.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct($file, $encoding = null, $encoding_id3v1 = null, $encoding_id3v2 = null, $dir_pattern = '', $file_pattern ='', $islocal = true)
    {
        $this->islocal = $islocal;
        $this->filename = $file;
        $this->encoding = $encoding ?: AmpConfig::get('site_charset');

        /* These are needed for the filename mojo */
        $this->_file_pattern = $file_pattern;
        $this->_dir_pattern  = $dir_pattern;

        // FIXME: This looks ugly and probably wrong
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $this->_pathinfo = str_replace('%3A', ':', urlencode($this->filename));
            $this->_pathinfo = pathinfo(str_replace('%5C', '\\', $this->_pathinfo));
        } else {
            $this->_pathinfo = pathinfo(str_replace('%2F', '/', urlencode($this->filename)));
        }
        $this->_pathinfo['extension'] = strtolower($this->_pathinfo['extension']);

        if ($this->islocal) {
            // Initialize getID3 engine
            $this->_getID3 = new getID3();

            $this->_getID3->option_md5_data = false;
            $this->_getID3->option_md5_data_source = false;
            $this->_getID3->option_tags_html = false;
            $this->_getID3->option_extra_info = true;
            $this->_getID3->option_tag_lyrics3 = true;
            $this->_getID3->option_tags_process = true;
            $this->_getID3->encoding = $this->encoding;

            // get id3tag encoding (try to work around off-spec id3v1 tags)
            try {
                $this->_raw = $this->_getID3->analyze(Core::conv_lc_file($file));
            } catch (Exception $error) {
                debug_event('getID3', "Broken file detected: $file: " . $error->getMessage(), 1);
                $this->_broken = true;
                return false;
            }

            if (AmpConfig::get('mb_detect_order')) {
                $mb_order = AmpConfig::get('mb_detect_order');
            } elseif (function_exists('mb_detect_order')) {
                $mb_order = implode(", ", mb_detect_order());
            } else {
                $mb_order = "auto";
            }

            $test_tags = array('artist', 'album', 'genre', 'title');

            if ($encoding_id3v1) {
                $this->encoding_id3v1 = $encoding_id3v1;
            } else {
                $tags = array();
                foreach ($test_tags as $tag) {
                    if ($value = $this->_raw['id3v1'][$tag]) {
                        $tags[$tag] = $value;
                    }
                }

                $this->encoding_id3v1 = self::_detect_encoding($tags, $mb_order);
            }

            if (AmpConfig::get('getid3_detect_id3v2_encoding')) {
                // The user has told us to be moronic, so let's do that thing
                $tags = array();
                foreach ($test_tags as $tag) {
                    if ($value = $this->_raw['id3v2']['comments'][$tag]) {
                        $tags[$tag] = $value;
                    }
                }

                $this->encoding_id3v2 = self::_detect_encoding($tags, $mb_order);
                $this->_getID3->encoding_id3v2 = $this->encoding_id3v2;
            }

            $this->_getID3->encoding_id3v1 = $this->encoding_id3v1;
        }
    }

    public function forceSize($size)
    {
        $this->_forcedSize = $size;
    }

    /**
     * _detect_encoding
     *
     * Takes an array of tags and attempts to automatically detect their
     * encoding.
     */
    private static function _detect_encoding($tags, $mb_order)
    {
        if (function_exists('mb_detect_encoding')) {
            $encodings = array();
            if (is_array($tags)) {
                foreach ($tags as $tag) {
                    $encodings[mb_detect_encoding($tag, $mb_order, true)]++;
                }
            }

            debug_event('vainfo', 'encoding detection: ' . json_encode($encodings), 5);
            $high = 0;
            $encoding = '';
            foreach ($encodings as $key => $value) {
                if ($value > $high) {
                    $encoding = $key;
                    $high = $value;
                }
            }

            if ($encoding != 'ASCII' && $encoding != '0') {
                return $encoding;
            } else {
                return 'ISO-8859-1';
            }
        }
        return 'ISO-8859-1';
    }


    /**
     * get_info
     *
     * This function runs the various steps to gathering the metadata
     */
    public function get_info()
    {
        // If this is broken, don't waste time figuring it out a second
        // time, just return their rotting carcass of a media file.
        if ($this->_broken) {
            $this->tags = $this->set_broken();
            return true;
        }

        if ($this->islocal) {
            try {
                $this->_raw = $this->_getID3->analyze(Core::conv_lc_file($this->filename));
                //debug_event('vainfo', print_r($this->_raw, true), '5');
            } catch (Exception $error) {
                debug_event('getID2', 'Unable to catalog file: ' . $error->getMessage(), 1);
            }
        }

        /* Figure out what type of file we are dealing with */
        $this->type = $this->_get_type();

        $enabled_sources = (array) AmpConfig::get('metadata_order');

        if (in_array('filename', $enabled_sources)) {
            $this->tags['filename'] = $this->_parse_filename($this->filename);
        }

        if (in_array('getID3', $enabled_sources) && $this->islocal) {
            $this->tags['getID3'] = $this->_get_tags();
        }

        $this->_get_plugin_tags();

    } // get_info

    /**
     * get_tag_type
     *
     * This takes the result set and the tag_order defined in your config
     * file and tries to figure out which tag type(s) it should use. If your
     * tag_order doesn't match anything then it throws up its hands and uses
     * everything in random order.
     */
    public static function get_tag_type($results, $config_key = 'metadata_order')
    {
        $order = (array) AmpConfig::get($config_key);

        // Iterate through the defined key order adding them to an ordered array.
        $returned_keys = array();
        foreach ($order as $key) {
            if ($results[$key]) {
                $returned_keys[] = $key;
            }
        }

        // If we didn't find anything then default to everything.
        if (!isset($returned_keys)) {
            $returned_keys = array_keys($results);
            $returned_keys = sort($returned_keys);
        }

        // Unless they explicitly set it, add bitrate/mode/mime/etc.
        if (is_array($returned_keys)) {
            if (!in_array('general', $returned_keys)) {
                $returned_keys[] = 'general';
            }
        }

        return $returned_keys;
    }

    /**
     * clean_tag_info
     *
     * This function takes the array from vainfo along with the
     * key we've decided on and the filename and returns it in a
     * sanitized format that ampache can actually use
     */
    public static function clean_tag_info($results, $keys, $filename = null)
    {
        $info = array();

        $info['file'] = $filename;

        // Iteration!
        foreach ($keys as $key) {
            $tags = $results[$key];

            $info['file'] = $info['file'] ?: $tags['file'];
            $info['bitrate'] = $info['bitrate'] ?: intval($tags['bitrate']);
            $info['rate'] = $info['rate'] ?: intval($tags['rate']);
            $info['mode'] = $info['mode'] ?: $tags['mode'];
            $info['size'] = $info['size'] ?: $tags['size'];
            $info['mime'] = $info['mime'] ?: $tags['mime'];
            $info['encoding'] = $info['encoding'] ?: $tags['encoding'];
            $info['rating'] = $info['rating'] ?: $tags['rating'];
            $info['time'] = $info['time'] ?: intval($tags['time']);
            $info['channels'] = $info['channels'] ?: $tags['channels'];

            $info['title'] = $info['title'] ?: stripslashes(trim($tags['title']));

            $info['year'] = $info['year'] ?: intval($tags['year']);

            $info['disk'] = $info['disk'] ?: intval($tags['disk']);

            $info['totaldisks'] = $info['totaldisks'] ?: intval($tags['totaldisks']);

            $info['artist']    = $info['artist'] ?: trim($tags['artist']);

            $info['album'] = $info['album'] ?: trim($tags['album']);

            $info['band'] = $info['band'] ?: trim($tags['band']);

            // multiple genre support
            if ((!$info['genre']) && $tags['genre']) {
                if (!is_array($tags['genre'])) {
                    // not all tag formats will return an array, but we need one
                    $info['genre'][] = trim($tags['genre']);
                } else {
                    // if we trim the array we lose everything after 1st entry
                    foreach ($tags['genre'] as $genre) {
                        $info['genre'][] = trim($genre);
                    }
                }
            }

            $info['mb_trackid'] = $info['mb_trackid'] ?: trim($tags['mb_trackid']);
            $info['mb_albumid'] = $info['mb_albumid'] ?: trim($tags['mb_albumid']);
            $info['mb_artistid'] = $info['mb_artistid'] ?: trim($tags['mb_artistid']);

            $info['language'] = $info['language'] ?: trim($tags['language']);

            $info['lyrics']    = $info['lyrics']
                    ?: str_replace(
                        array("\r\n","\r","\n"),
                        '<br />',
                        strip_tags($tags['lyrics']));

            $info['track'] = $info['track'] ?: intval($tags['track']);
            $info['resolution_x'] = $info['resolution_x'] ?: intval($tags['resolution_x']);
            $info['resolution_y'] = $info['resolution_y'] ?: intval($tags['resolution_y']);
            $info['audio_codec'] = $info['audio_codec'] ?: trim($tags['audio_codec']);
            $info['video_codec'] = $info['video_codec'] ?: trim($tags['video_codec']);
        }

        // Some things set the disk number even though there aren't multiple
        if ($info['totaldisks'] == 1 && $info['disk'] == 1) {
            unset($info['disk']);
            unset($info['totaldisks']);
        }

        return $info;
    }

    /**
     * _get_type
     *
     * This function takes the raw information and figures out what type of
     * file we are dealing with.
     */
    private function _get_type()
    {
        // There are a few places that the file type can come from, in the end
        // we trust the encoding type.
        if ($type = $this->_raw['video']['dataformat']) {
            return $this->_clean_type($type);
        }
        if ($type = $this->_raw['audio']['streams']['0']['dataformat']) {
            return $this->_clean_type($type);
        }
        if ($type = $this->_raw['audio']['dataformat']) {
            return $this->_clean_type($type);
        }
        if ($type = $this->_raw['fileformat']) {
            return $this->_clean_type($type);
        }

        return false;
    }


    /**
     * _get_tags
     *
     * This processes the raw getID3 output and bakes it.
     */
    private function _get_tags()
    {
        $results = array();

        // The tags can come in many different shapes and colors
        // depending on the encoding time of day and phase of the moon.

        if (is_array($this->_raw['tags'])) {
            foreach ($this->_raw['tags'] as $key => $tag_array) {
                switch ($key) {
                    case 'ape':
                    case 'avi':
                    case 'flv':
                    case 'matroska':
                        debug_event('vainfo', 'Cleaning ' . $key, 5);
                        $parsed = $this->_cleanup_generic($tag_array);
                    break;
                    case 'vorbiscomment':
                        debug_event('vainfo', 'Cleaning vorbis', 5);
                        $parsed = $this->_cleanup_vorbiscomment($tag_array);
                    break;
                    case 'id3v1':
                        debug_event('vainfo', 'Cleaning id3v1', 5);
                        $parsed = $this->_cleanup_id3v1($tag_array);
                    break;
                    case 'id3v2':
                        debug_event('vainfo', 'Cleaning id3v2', 5);
                        $parsed = $this->_cleanup_id3v2($tag_array);
                    break;
                    case 'quicktime':
                        debug_event('vainfo', 'Cleaning quicktime', 5);
                        $parsed = $this->_cleanup_quicktime($tag_array);
                    break;
                    case 'riff':
                        debug_event('vainfo', 'Cleaning riff', 5);
                        $parsed = $this->_cleanup_riff($tag_array);
                    break;
                    case 'mpg':
                    case 'mpeg':
                        $key = 'mpeg';
                        debug_event('vainfo', 'Cleaning MPEG', 5);
                        $parsed = $this->_cleanup_generic($tag_array);
                    break;
                    case 'asf':
                    case 'wmv':
                        $key = 'asf';
                        debug_event('vainfo', 'Cleaning WMV/WMA/ASF', 5);
                        $parsed = $this->_cleanup_generic($tag_array);
                    break;
                    case 'lyrics3':
                        debug_event('vainfo', 'Cleaning lyrics3', 5);
                        $parsed = $this->_cleanup_lyrics($tag_array);
                    break;
                    default:
                        debug_event('vainfo', 'Cleaning unrecognised tag type ' . $key . ' for file ' . $this->filename, 5);
                        $parsed = $this->_cleanup_generic($tag_array);
                    break;
                }

                $results[$key] = $parsed;
            }
        }

        $results['general'] = $this->_parse_general($this->_raw);

        $cleaned = self::clean_tag_info($results, self::get_tag_type($results, 'getid3_tag_order'), $this->filename);
        $cleaned['raw'] = $results;

        return $cleaned;
    }

    /**
     * _get_plugin_tags
     *
     * Get additional metadata from plugins
     */
    private function _get_plugin_tags()
    {
        $tag_order = AmpConfig::get('metadata_order');
        if (!is_array($tag_order)) {
            $tag_order = array($tag_order);
        }

        $plugin_names = Plugin::get_plugins('get_metadata');
        foreach ($tag_order as $tag_source) {
            if (in_array($tag_source, $plugin_names)) {
                $plugin = new Plugin($tag_source);
                if ($plugin->load($GLOBALS['user'])) {
                    $this->tags[$tag_source] = $plugin->_plugin->get_metadata(self::clean_tag_info($this->tags, self::get_tag_type($this->tags), $this->filename));
                }
            }
        }
    }

    /**
     * _parse_general
     *
     * Gather and return the general information about a file (vbr/cbr,
     * sample rate, channels, etc.)
     */
    private function _parse_general($tags)
    {
        $parsed = array();

        $parsed['title'] = urldecode($this->_pathinfo['filename']);
        $parsed['mode'] = $tags['audio']['bitrate_mode'];
        if ($parsed['mode'] == 'con') {
            $parsed['mode'] = 'cbr';
        }
        $parsed['bitrate'] = $tags['audio']['bitrate'];
        $parsed['channels'] = intval($tags['audio']['channels']);
        $parsed['rate'] = intval($tags['audio']['sample_rate']);
        $parsed['size'] = $this->_forcedSize ?: intval($tags['filesize']);
        $parsed['encoding'] = $tags['encoding'];
        $parsed['mime'] = $tags['mime_type'];
        $parsed['time'] = ($this->_forcedSize ? ((($this->_forcedSize - $tags['avdataoffset']) * 8) / $tags['bitrate']) : $tags['playtime_seconds']);
        $parsed['video_codec'] = $tags['video']['fourcc'];
        $parsed['audio_codec'] = $tags['audio']['dataformat'];
        $parsed['resolution_x'] = $tags['video']['resolution_x'];
        $parsed['resolution_y'] = $tags['video']['resolution_y'];

        return $parsed;
    }

    /**
     * _clean_type
     * This standardizes the type that we are given into a recognized type.
     */
    private function _clean_type($type)
    {
        switch ($type) {
            case 'mp3':
            case 'mp2':
            case 'mpeg3':
                return 'mp3';
            case 'vorbis':
                return 'ogg';
            case 'flac':
            case 'flv':
            case 'mpg':
            case 'mpeg':
            case 'asf':
            case 'wmv':
            case 'avi':
            case 'quicktime':
                return $type;
            default:
                /* Log the fact that we couldn't figure it out */
                debug_event('vainfo','Unable to determine file type from ' . $type . ' on file ' . $this->filename,'5');
                return $type;
        }
    }

    /**
     * _cleanup_generic
     *
     * This does generic cleanup.
     */
    private function _cleanup_generic($tags)
    {
        $parsed = array();
        foreach ($tags as $tagname => $data) {
            switch ($tagname) {
                case 'genre':
                    // Pass the array through
                    $parsed[$tagname] = $data;
                break;
                default:
                    $parsed[$tagname] = $data[0];
                break;
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_lyrics
     *
     * This is supposed to handle lyrics3. FIXME: does it?
     */
    private function _cleanup_lyrics($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            if ($tag == 'unsynchedlyrics' || $tag == 'unsynchronised lyric') {
                $tag = 'lyrics';
            }
            $parsed[$tag] = $data[0];
        }
        return $parsed;
    }

    /**
     * _cleanup_vorbiscomment
     *
     * Standardises tag names from vorbis.
     */
    private function _cleanup_vorbiscomment($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            switch ($tag) {
                case 'genre':
                    // Pass the array through
                    $parsed[$tag] = $data;
                break;
                case 'tracknumber':
                    $parsed['track'] = $data[0];
                break;
                case 'discnumber':
                    $elements = explode('/', $data[0]);
                    $parsed['disk'] = $elements[0];
                    $parsed['totaldisks'] = $elements[1];
                break;
                case 'date':
                    $parsed['year'] = $data[0];
                break;
                default:
                    $parsed[$tag] = $data[0];
                break;
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_id3v1
     *
     * Doesn't do much.
     */
    private function _cleanup_id3v1($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            // This is our baseline for naming so everything's already right,
            // we just need to shuffle off the array.
            $parsed[$tag] = $data[0];
        }

        return $parsed;
    }

    /**
     * _cleanup_id3v2
     *
     * Whee, v2!
     */
    private function _cleanup_id3v2($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {

            switch ($tag) {
                case 'genre':
                    // Pass the array through
                    $parsed['genre'] = $data;
                break;
                case 'part_of_a_set':
                    $elements = explode('/', $data[0]);
                    $parsed['disk'] = $elements[0];
                    $parsed['totaldisks'] = $elements[1];
                break;
                case 'track_number':
                    $parsed['track'] = $data[0];
                break;
                case 'comments':
                    $parsed['comment'] = $data[0];
                break;
                default:
                    $parsed[$tag] = $data[0];
                break;
            }
        }

        // getID3 doesn't do all the parsing we need, so grab the raw data
        $id3v2 = $this->_raw['id3v2'];

        if (!empty($id3v2['UFID'])) {
            // Find the MBID for the track
            foreach ($id3v2['UFID'] as $ufid) {
                if ($ufid['ownerid'] == 'http://musicbrainz.org') {
                    $parsed['mb_trackid'] = $ufid['data'];
                }
            }

            if (!empty($id3v2['TXXX'])) {
                // Find the MBIDs for the album and artist
                foreach ($id3v2['TXXX'] as $txxx) {
                    switch ($txxx['description']) {
                        case 'MusicBrainz Album Id':
                            $parsed['mb_albumid'] = $txxx['data'];
                        break;
                        case 'MusicBrainz Artist Id':
                            $parsed['mb_artistid'] = $txxx['data'];
                        break;
                    }
                }
            }
        }

        // Find all genre
        if (!empty($id3v2['TCON'])) {
            // Find the MBID for the track
            foreach ($id3v2['TCON'] as $tcid) {
                if ($tcid['framenameshort'] == "genre") {
                    // Removing unwanted UTF-8 charaters
                    $tcid['data'] = str_replace("\xFF", "", $tcid['data']);
                    $tcid['data'] = str_replace("\xFE", "", $tcid['data']);

                    if (!empty($tcid['data'])) {
                        // Parsing string with the null character
                        $genres = explode("\0", $tcid['data']);
                        $parsed_genres = array();
                        foreach ($genres as $g) {
                            if (strlen($g) > 2) {   // Only allow tags with at least 3 characters
                                $parsed_genres[] = $g;
                            }
                        }

                        if (count($parsed_genres)) {
                            $parsed['genre'] = $parsed_genres;
                        }
                    }
                }
                break;
            }
        }

        // Find the rating
        if (is_array($id3v2['POPM'])) {
            foreach ($id3v2['POPM'] as $popm) {
                if (array_key_exists('email', $popm) &&
                    $user = User::get_from_email($popm['email'])) {
                    if ($user) {
                        // Ratings are out of 255; scale it
                        $parsed['rating'][$user->id] = $popm['rating'] / 255 * 5;
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_riff
     */
    private function _cleanup_riff($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            switch ($tag) {
                case 'product':
                    $parsed['album'] = $data[0];
                break;
                default:
                    $parsed[$tag] = $data[0];
                break;
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_quicktime
     */
    private function _cleanup_quicktime($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            switch ($tag) {
                case 'creation_date':
                    if (strlen($data['0']) > 4) {
                        // Weird date format, attempt to normalize it
                        $data[0] = date('Y', strtotime($data[0]));
                    }
                    $parsed['year'] = $data[0];
                break;
                case 'MusicBrainz Track Id':
                    $parsed['mb_trackid'] = $data[0];
                break;
                case 'MusicBrainz Album Id':
                    $parsed['mb_albumid'] = $data[0];
                break;
                case 'MusicBrainz Artist Id':
                    $parsed['mb_artistid'] = $data[0];
                break;
                default:
                    $parsed[$tag] = $data[0];
                break;
            }
        }

        return $parsed;
    }

    /**
     * _parse_filename
     *
     * This function uses the file and directory patterns to pull out extra tag
     * information.
     */
    private function _parse_filename($filename)
    {
        $origin = $filename;
        $results = array();

        // Correctly detect the slash we need to use here
        if (strpos($filename, '/') !== false) {
            $slash_type = '/';
            $slash_type_preg = $slash_type;
        } else {
            $slash_type = '\\';
            $slash_type_preg = $slash_type . $slash_type;
        }

        // Combine the patterns
        $pattern = preg_quote($this->_dir_pattern) . $slash_type_preg . preg_quote($this->_file_pattern);

        // Remove first left directories from filename to match pattern
        $cntslash = substr_count($pattern, $slash_type) + 1;
        $filepart = explode($slash_type, $filename);
        if (count($filepart) > $cntslash) {
            $filename = implode($slash_type, array_slice($filepart, count($filepart) - $cntslash));
        }

        // Pull out the pattern codes into an array
        preg_match_all('/\%\w/', $pattern, $elements);

        // Mangle the pattern by turning the codes into regex captures
        $pattern = preg_replace('/\%[Ty]/', '([0-9]+?)', $pattern);
        $pattern = preg_replace('/\%\w/', '(.+?)', $pattern);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = str_replace(' ', '\s', $pattern);
        $pattern = '/' . $pattern . '\..+$/';

        // Pull out our actual matches
        preg_match($pattern, $filename, $matches);

        if ($matches != null) {
            // The first element is the full match text
            $matched = array_shift($matches);
            debug_event('vainfo', $pattern . ' matched ' . $matched . ' on ' . $filename, 5);

            // Iterate over what we found
            foreach ($matches as $key => $value) {
                $new_key = translate_pattern_code($elements['0'][$key]);
                if ($new_key) {
                    $results[$new_key] = $value;
                }
            }

            $results['title'] = $results['title'] ?: basename($filename);
            if ($this->islocal) {
                $results['size'] = filesize(Core::conv_lc_file($origin));
            }
        }

        return $results;
    }

    /**
     * set_broken
     *
     * This fills all tag types with Unknown (Broken)
     *
     * @return    array    Return broken title, album, artist
     */
    public function set_broken()
    {
        /* Pull In the config option */
        $order = AmpConfig::get('tag_order');

        if (!is_array($order)) {
            $order = array($order);
        }

        $key = array_shift($order);

        $broken = array();
        $broken[$key] = array();
        $broken[$key]['title'] = '**BROKEN** ' . $this->filename;
        $broken[$key]['album'] = 'Unknown (Broken)';
        $broken[$key]['artist'] = 'Unknown (Broken)';

        return $broken;

    } // set_broken

} // end class vainfo
