<?php
declare(strict_types=0);
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
 *
 * This class takes the information pulled from getID3 and returns it in a
 * Ampache-friendly way.
 *
 */
class vainfo
{
    public $encoding       = '';
    public $encoding_id3v1 = '';
    public $encoding_id3v2 = '';

    public $filename = '';
    public $type     = '';
    public $tags     = array();
    public $islocal;
    public $gather_types = array();

    protected $_raw        = array();
    protected $_getID3     = null;
    protected $_forcedSize = 0;

    protected $_file_encoding = '';
    protected $_file_pattern  = '';
    protected $_dir_pattern   = '';

    private $_pathinfo;
    private $_broken = false;

    /**
     * Constructor
     *
     * This function just sets up the class, it doesn't pull the information.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param $file
     * @param array $gather_types
     * @param string $encoding
     * @param string $encoding_id3v1
     * @param string $encoding_id3v2
     * @param string $dir_pattern
     * @param string $file_pattern
     * @param boolean $islocal
     */
    public function __construct($file, $gather_types = array(), $encoding = null, $encoding_id3v1 = null, $encoding_id3v2 = null, $dir_pattern = '', $file_pattern = '', $islocal = true)
    {
        $this->islocal      = $islocal;
        $this->filename     = $file;
        $this->gather_types = $gather_types;
        $this->encoding     = $encoding ?: AmpConfig::get('site_charset');

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

        $enabled_sources = (array) $this->get_metadata_order();

        if (in_array('getID3', $enabled_sources) && $this->islocal) {
            // Initialize getID3 engine
            $this->_getID3 = new getID3();

            $this->_getID3->option_md5_data        = false;
            $this->_getID3->option_md5_data_source = false;
            $this->_getID3->option_tags_html       = false;
            $this->_getID3->option_extra_info      = true;
            $this->_getID3->option_tag_lyrics3     = true;
            $this->_getID3->option_tags_process    = true;
            $this->_getID3->option_tag_apetag      = true;
            $this->_getID3->encoding               = $this->encoding;

            // get id3tag encoding (try to work around off-spec id3v1 tags)
            try {
                $this->_raw = $this->_getID3->analyze(Core::conv_lc_file($file));
            } catch (Exception $error) {
                debug_event('vainfo.class', 'getID3 Broken file detected: $file: ' . $error->getMessage(), 1);
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

            if ($encoding_id3v1 !== null) {
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

                $this->encoding_id3v2    = self::_detect_encoding($tags, $mb_order);
                $this->_getID3->encoding = $this->encoding_id3v2;
            }

            $this->_getID3->encoding_id3v1 = $this->encoding_id3v1;
        }

        return true;
    }

    /**
     * @param $size
     */
    public function forceSize($size)
    {
        $this->_forcedSize = $size;
    }

    /**
     * _detect_encoding
     *
     * Takes an array of tags and attempts to automatically detect their
     * encoding.
     * @param $tags
     * @param $mb_order
     * @return string
     */
    private static function _detect_encoding($tags, $mb_order)
    {
        if (!function_exists('mb_detect_encoding')) {
            return 'ISO-8859-1';
        }

        $encodings = array();
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                if (is_array($tag)) {
                    $tag = implode(" ", $tag);
                }
                $enc = mb_detect_encoding($tag, $mb_order, true);
                if ($enc !== false) {
                    $encodings[$enc]++;
                }
            }
        } else {
            $enc = mb_detect_encoding($tags, $mb_order, true);
            if ($enc !== false) {
                $encodings[$enc]++;
            }
        }

        //!!debug_event('vainfo.class', 'encoding detection: ' . json_encode($encodings), 5);
        $high     = 0;
        $encoding = 'ISO-8859-1';
        foreach ($encodings as $key => $value) {
            if ($value > $high) {
                $encoding = $key;
                $high     = $value;
            }
        }

        if ($encoding != 'ASCII') {
            return (string) $encoding;
        } else {
            return 'ISO-8859-1';
        }
    }

    /**
     * get_info
     *
     * This function runs the various steps to gathering the metadata
     * @throws Exception
     */
    public function get_info()
    {
        // If this is broken, don't waste time figuring it out a second
        // time, just return their rotting carcass of a media file.
        if ($this->_broken) {
            $this->tags = $this->set_broken();

            return true;
        }

        $enabled_sources = (array) $this->get_metadata_order();

        if (in_array('getID3', $enabled_sources) && $this->islocal) {
            try {
                $this->_raw = $this->_getID3->analyze(Core::conv_lc_file($this->filename));
            } catch (Exception $error) {
                debug_event('vainfo.class', 'getID2 Unable to catalog file: ' . $error->getMessage(), 1);
            }
        }

        /* Figure out what type of file we are dealing with */
        $this->type = $this->_get_type() ?: '';

        if (in_array('filename', $enabled_sources)) {
            $this->tags['filename'] = $this->_parse_filename($this->filename);
        }

        if (in_array('getID3', $enabled_sources) && $this->islocal) {
            $this->tags['getID3'] = $this->_get_tags();
        }

        $this->_get_plugin_tags();

        return true;
    } // get_info

    /**
     * write_id3
     * This function runs the various steps to gathering the metadata
     * @param $data
     */
    public function write_id3($data)
    {
        // Get the Raw file information
        $this->read_id3();
        if (isset($this->_raw['tags']['id3v2'])) {
            getid3_lib::IncludeDependency(GETID3_INCLUDEPATH . 'write.php', __FILE__, true);
            $tagWriter           = new getid3_writetags();
            $tagWriter->filename = $this->filename;
            //'id3v2.4' doesn't saves the year;
            $tagWriter->tagformats        = array('id3v1', 'id3v2.3');
            $tagWriter->overwrite_tags    = true;
            $tagWriter->remove_other_tags = true;
            $tagWriter->tag_encoding      = 'UTF-8';
            $TagData                      = $this->_raw['tags']['id3v2'];

            // Foreach what we've got
            foreach ($data as $key => $value) {
                if ($key != 'APIC') {
                    $TagData[$key][0] = $value;
                }
            }

            if (isset($data['APIC'])) {
                $TagData['attached_picture'][0]['data']          = $data['APIC']['data'];
                $TagData['attached_picture'][0]['picturetypeid'] = '3';
                $TagData['attached_picture'][0]['description']   = 'Cover';
                $TagData['attached_picture'][0]['mime']          = $data['APIC']['mime'];
            }

            $tagWriter->tag_data = $TagData;

            if ($tagWriter->WriteTags()) {
                if (!empty($tagWriter->warnings)) {
                    debug_event('vainfo.class', 'FWarnings ' . implode("\n", $tagWriter->warnings), 5);
                }
            } else {
                debug_event('vainfo.class', 'Failed to write tags! ' . implode("\n", $tagWriter->errors), 3);
            }
        }
    } // write_id3

    /**
     * read_id3
     * This function runs the various steps to gathering the metadata
     * @return array
     */
    public function read_id3()
    {
        // Get the Raw file information
        try {
            $this->_raw = $this->_getID3->analyze($this->filename);

            return $this->_raw;
        } catch (Exception $error) {
            debug_event('vainfo.class', "Unable to read file:" . $error->getMessage(), 1);
        }

        return array();
    } // read_id3

    /**
     * get_tag_type
     *
     * This takes the result set and the tag_order defined in your config
     * file and tries to figure out which tag type(s) it should use. If your
     * tag_order doesn't match anything then it throws up its hands and uses
     * everything in random order.
     * @param array $results
     * @param string $config_key
     * @return array
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
            sort($returned_keys);
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
     * sanitized format that Ampache can actually use
     * @param array $results
     * @param array $keys
     * @param string $filename
     * @return array
     */
    public static function clean_tag_info($results, $keys, $filename = null)
    {
        $info = array();

        $info['file'] = $filename;

        // Iteration!
        foreach ($keys as $key) {
            $tags = $results[$key];

            $info['file']    = $info['file'] ?: $tags['file'];
            $info['bitrate'] = $info['bitrate'] ?: (int) ($tags['bitrate']);
            $info['rate']    = $info['rate'] ?: (int) ($tags['rate']);
            $info['mode']    = $info['mode'] ?: $tags['mode'];
            // size will be added later, because of conflicts between real file size and getID3 reported filesize
            $info['mime']     = $info['mime'] ?: $tags['mime'];
            $info['encoding'] = $info['encoding'] ?: $tags['encoding'];
            $info['rating']   = $info['rating'] ?: $tags['rating'];
            $info['time']     = $info['time'] ?: (int) ($tags['time']);
            $info['channels'] = $info['channels'] ?: $tags['channels'];

            // This because video title are almost always bad...
            $info['original_name'] = $info['original_name'] ?: stripslashes(trim((string) $tags['original_name']));
            $info['title']         = $info['title'] ?: stripslashes(trim((string) $tags['title']));

            // Not even sure if these can be negative, but better safe than llama.
            $info['year'] = Catalog::normalize_year($info['year'] ?: (int) ($tags['year']));
            $info['disk'] = abs($info['disk'] ?: (int) ($tags['disk']));

            $info['totaldisks'] = $info['totaldisks'] ?: (int) ($tags['totaldisks']);

            $info['artist']         = $info['artist'] ?: trim((string) $tags['artist']);
            $info['albumartist']    = $info['albumartist'] ?: trim((string) $tags['albumartist']);

            $info['album'] = $info['album'] ?: trim((string) $tags['album']);

            $info['band']      = $info['band'] ?: trim((string) $tags['band']);
            $info['composer']  = $info['composer'] ?: trim((string) $tags['composer']);
            $info['publisher'] = $info['publisher'] ?: trim((string) $tags['publisher']);

            $info['genre'] = self::clean_array_tag('genre', $info, $tags);

            $info['mb_trackid']       = $info['mb_trackid'] ?: trim((string) $tags['mb_trackid']);
            $info['mb_albumid']       = $info['mb_albumid'] ?: trim((string) $tags['mb_albumid']);
            $info['mb_albumid_group'] = $info['mb_albumid_group'] ?: trim((string) $tags['mb_albumid_group']);
            $info['mb_artistid']      = $info['mb_artistid'] ?: trim((string) $tags['mb_artistid']);
            $info['mb_albumartistid'] = $info['mb_albumartistid'] ?: trim((string) $tags['mb_albumartistid']);
            if (trim((string) $tags['release_type']) !== '') {
                $info['release_type'] = $info['release_type'] ?: trim((string) $tags['release_type']);
            }

            $info['original_year']  = $info['original_year'] ?: trim((string) $tags['original_year']);
            $info['barcode']        = $info['barcode'] ?: trim((string) $tags['barcode']);
            $info['catalog_number'] = $info['catalog_number'] ?: trim((string) $tags['catalog_number']);

            $info['language'] = $info['language'] ?: trim((string) $tags['language']);
            $info['comment']  = $info['comment'] ?: trim((string) $tags['comment']);

            $info['lyrics']    = $info['lyrics']
                    ?: strip_tags(nl2br((string) $tags['lyrics']), "<br>");
            $info['replaygain_track_gain'] = $info['replaygain_track_gain'] ?: floatval($tags['replaygain_track_gain']);
            $info['replaygain_track_peak'] = $info['replaygain_track_peak'] ?: floatval($tags['replaygain_track_peak']);
            $info['replaygain_album_gain'] = $info['replaygain_album_gain'] ?: floatval($tags['replaygain_album_gain']);
            $info['replaygain_album_peak'] = $info['replaygain_album_peak'] ?: floatval($tags['replaygain_album_peak']);

            $info['track']         = $info['track'] ?: (int) ($tags['track']);
            $info['resolution_x']  = $info['resolution_x'] ?: (int) ($tags['resolution_x']);
            $info['resolution_y']  = $info['resolution_y'] ?: (int) ($tags['resolution_y']);
            $info['display_x']     = $info['display_x'] ?: (int) ($tags['display_x']);
            $info['display_y']     = $info['display_y'] ?: (int) ($tags['display_y']);
            $info['frame_rate']    = $info['frame_rate'] ?: floatval($tags['frame_rate']);
            $info['video_bitrate'] = $info['video_bitrate'] ?: (int) ($tags['video_bitrate']);
            $info['audio_codec']   = $info['audio_codec'] ?: trim((string) $tags['audio_codec']);
            $info['video_codec']   = $info['video_codec'] ?: trim((string) $tags['video_codec']);
            $info['description']   = $info['description'] ?: trim((string) $tags['description']);

            $info['tvshow']         = $info['tvshow'] ?: trim((string) $tags['tvshow']);
            $info['tvshow_year']    = $info['tvshow_year'] ?: trim((string) $tags['tvshow_year']);
            $info['tvshow_season']  = $info['tvshow_season'] ?: trim((string) $tags['tvshow_season']);
            $info['tvshow_episode'] = $info['tvshow_episode'] ?: trim((string) $tags['tvshow_episode']);
            $info['release_date']   = $info['release_date'] ?: trim((string) $tags['release_date']);
            $info['summary']        = $info['summary'] ?: trim((string) $tags['summary']);
            $info['tvshow_summary'] = $info['tvshow_summary'] ?: trim((string) $tags['tvshow_summary']);

            $info['tvshow_art']        = $info['tvshow_art'] ?: trim((string) $tags['tvshow_art']);
            $info['tvshow_season_art'] = $info['tvshow_season_art'] ?: trim((string) $tags['tvshow_season_art']);
            $info['art']               = $info['art'] ?: trim((string) $tags['art']);

            if (AmpConfig::get('enable_custom_metadata') && is_array($tags)) {
                // Add rest of the tags without typecast to the array
                foreach ($tags as $tag => $value) {
                    if (!isset($info[$tag]) && !is_array($value)) {
                        $info[$tag] = (!is_array($value)) ? trim((string) $value) : $value;
                    }
                }
            }
        }

        // Determine the correct file size, do not get fooled by the size which may be returned by id3v2!
        if (isset($results['general']['size'])) {
            $size = $results['general']['size'];
        } else {
            $size = Core::get_filesize(Core::conv_lc_file($filename));
        }

        $info['size'] = $info['size'] ?: $size;

        return $info;
    }

    /**
     * clean_array_tag
     * @param string $field
     * @param $info
     * @param $tags
     * @return array
     */
    private static function clean_array_tag($field, $info, $tags)
    {
        $arr = array();
        if ((!$info[$field] || count($info[$field]) == 0) && $tags[$field]) {
            if (!is_array($tags[$field])) {
                // not all tag formats will return an array, but we need one
                $arr[] = trim((string) $tags[$field]);
            } else {
                foreach ($tags[$field] as $genre) {
                    $arr[] = trim((string) $genre);
                }
            }
        } else {
            $arr = $info[$field];
        }

        return $arr;
    }

    /**
     * _get_type
     *
     * This function takes the raw information and figures out what type of
     * file we are dealing with.
     * @return string|false
     */
    private function _get_type()
    {
        // There are a few places that the file type can come from, in the end
        // we trust the encoding type.
        $video_type = $this->_raw['video']['dataformat'];
        if ($video_type) {
            return $this->_clean_type($video_type);
        }
        $stream_type = $this->_raw['audio']['streams']['0']['dataformat'];
        if ($stream_type) {
            return $this->_clean_type($stream_type);
        }
        $audio_type = $this->_raw['audio']['dataformat'];
        if ($audio_type) {
            return $this->_clean_type($audio_type);
        }
        $type = $this->_raw['fileformat'];
        if ($type) {
            return $this->_clean_type($type);
        }

        return false;
    }


    /**
     * _get_tags
     *
     * This processes the raw getID3 output and bakes it.
     * @return array
     * @throws Exception
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
                        debug_event('vainfo.class', 'Cleaning ' . $key, 5);
                        $parsed = $this->_cleanup_generic($tag_array);
                    break;
                    case 'vorbiscomment':
                        debug_event('vainfo.class', 'Cleaning vorbis', 5);
                        $parsed = $this->_cleanup_vorbiscomment($tag_array);
                    break;
                    case 'id3v1':
                        debug_event('vainfo.class', 'Cleaning id3v1', 5);
                        $parsed = $this->_cleanup_id3v1($tag_array);
                    break;
                    case 'id3v2':
                        debug_event('vainfo.class', 'Cleaning id3v2', 5);
                        $parsed = $this->_cleanup_id3v2($tag_array);
                    break;
                    case 'quicktime':
                        debug_event('vainfo.class', 'Cleaning quicktime', 5);
                        $parsed = $this->_cleanup_quicktime($tag_array);
                    break;
                    case 'riff':
                        debug_event('vainfo.class', 'Cleaning riff', 5);
                        $parsed = $this->_cleanup_riff($tag_array);
                    break;
                    case 'mpg':
                    case 'mpeg':
                        $key = 'mpeg';
                        debug_event('vainfo.class', 'Cleaning MPEG', 5);
                        $parsed = $this->_cleanup_generic($tag_array);
                    break;
                    case 'asf':
                    case 'wmv':
                        $key = 'asf';
                        debug_event('vainfo.class', 'Cleaning WMV/WMA/ASF', 5);
                        $parsed = $this->_cleanup_generic($tag_array);
                    break;
                    case 'lyrics3':
                        debug_event('vainfo.class', 'Cleaning lyrics3', 5);
                        $parsed = $this->_cleanup_lyrics($tag_array);
                    break;
                    default:
                        debug_event('vainfo.class', 'Cleaning unrecognised tag type ' . $key . ' for file ' . $this->filename, 5);
                        $parsed = $this->_cleanup_generic($tag_array);
                    break;
                }

                $results[$key] = $parsed;
            }
        }

        $results['general'] = $this->_parse_general($this->_raw);

        $cleaned        = self::clean_tag_info($results, self::get_tag_type($results, 'getid3_tag_order'), $this->filename);
        $cleaned['raw'] = $results;

        return $cleaned;
    }

    /**
     * get_metadata_order_key
     * @return string
     */
    private function get_metadata_order_key()
    {
        if (!in_array('music', $this->gather_types)) {
            return 'metadata_order_video';
        }

        return 'metadata_order';
    }

    /**
     * get_metadata_order
     * @return array
     */
    private function get_metadata_order()
    {
        return (array) AmpConfig::get($this->get_metadata_order_key());
    }

    /**
     * _get_plugin_tags
     *
     * Get additional metadata from plugins
     */
    private function _get_plugin_tags()
    {
        $tag_order = $this->get_metadata_order();
        if (!is_array($tag_order)) {
            $tag_order = array($tag_order);
        }

        $plugin_names = Plugin::get_plugins('get_metadata');
        foreach ($tag_order as $tag_source) {
            if (in_array($tag_source, $plugin_names)) {
                $plugin            = new Plugin($tag_source);
                $installed_version = Plugin::get_plugin_version($plugin->_plugin->name);
                if ($installed_version) {
                    if ($plugin->load(Core::get_global('user'))) {
                        $this->tags[$tag_source] = $plugin->_plugin->get_metadata($this->gather_types, self::clean_tag_info($this->tags, self::get_tag_type($this->tags, $this->get_metadata_order_key()), $this->filename));
                    }
                }
            }
        }
    }

    /**
     * _parse_general
     *
     * Gather and return the general information about a file (vbr/cbr,
     * sample rate, channels, etc.)
     * @param $tags
     * @return array
     */
    private function _parse_general($tags)
    {
        $parsed = array();

        if ((in_array('movie', $this->gather_types)) || (in_array('tvshow', $this->gather_types))) {
            $parsed['title'] = $this->formatVideoName(urldecode($this->_pathinfo['filename']));
        } else {
            $parsed['title'] = urldecode($this->_pathinfo['filename']);
        }

        $parsed['mode']  = $tags['audio']['bitrate_mode'];
        if ($parsed['mode'] == 'con') {
            $parsed['mode'] = 'cbr';
        }
        $parsed['bitrate']       = $tags['audio']['bitrate'];
        $parsed['channels']      = (int) ($tags['audio']['channels']);
        $parsed['rate']          = (int) ($tags['audio']['sample_rate']);
        $parsed['size']          = $this->_forcedSize ?: $tags['filesize'];
        $parsed['encoding']      = $tags['encoding'];
        $parsed['mime']          = $tags['mime_type'];
        $parsed['time']          = ($this->_forcedSize ? ((($this->_forcedSize - $tags['avdataoffset']) * 8) / $tags['bitrate']) : $tags['playtime_seconds']);
        $parsed['audio_codec']   = $tags['audio']['dataformat'];
        $parsed['video_codec']   = $tags['video']['dataformat'];
        $parsed['resolution_x']  = $tags['video']['resolution_x'];
        $parsed['resolution_y']  = $tags['video']['resolution_y'];
        $parsed['display_x']     = $tags['video']['display_x'];
        $parsed['display_y']     = $tags['video']['display_y'];
        $parsed['frame_rate']    = $tags['video']['frame_rate'];
        $parsed['video_bitrate'] = $tags['video']['bitrate'];

        if (isset($tags['ape'])) {
            if (isset($tags['ape']['items'])) {
                foreach ($tags['ape']['items'] as $key => $tag) {
                    switch (strtolower($key)) {
                        case 'replaygain_track_gain':
                        case 'replaygain_track_peak':
                        case 'replaygain_album_gain':
                        case 'replaygain_album_peak':
                            $parsed[$key] = floatval($tag['data'][0]);
                            break;
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * @param $string
     * @return string
     */
    private function trimAscii($string)
    {
        return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim((string) $string));
    }

    /**
     * _clean_type
     * This standardizes the type that we are given into a recognized type.
     * @param $type
     * @return string
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
                debug_event('vainfo.class', 'Unable to determine file type from ' . $type . ' on file ' . $this->filename, 3);

                return $type;
        }
    }

    /**
     * _cleanup_generic
     *
     * This does generic cleanup.
     * @param $tags
     * @return array
     * @throws Exception
     */
    private function _cleanup_generic($tags)
    {
        $parsed = array();
        foreach ($tags as $tagname => $data) {
            switch (strtolower($tagname)) {
                case 'genre':
                    // Pass the array through
                    $parsed[$tagname] = $this->parseGenres($data);
                    break;
                case 'musicbrainz_artistid':
                    $parsed['mb_artistid'] = $data[0];
                    break;
                case 'musicbrainz_albumid':
                    $parsed['mb_albumid'] = $data[0];
                    break;
                case 'musicbrainz_albumartistid':
                    $parsed['mb_albumartistid'] = $data[0];
                    break;
                case 'musicbrainz_releasegroupid':
                    $parsed['mb_albumid_group'] = $data[0];
                    break;
                case 'musicbrainz_trackid':
                    $parsed['mb_trackid'] = $data[0];
                    break;
                case 'musicbrainz_albumtype':
                    $parsed['release_type'] = $data[0];
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
     * @param $tags
     * @return array
     */
    private function _cleanup_lyrics($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            if ($tag == 'unsyncedlyrics' || $tag == 'unsynced lyrics' || $tag == 'unsynchronised lyric') {
                $tag = 'lyrics';
            }
            $parsed[$tag] = $data[0];
        }

        return $parsed;
    }

    /**
     * _cleanup_vorbiscomment
     *
     * Standardizes tag names from vorbis.
     * @param $tags
     * @return array
     * @throws Exception
     */
    private function _cleanup_vorbiscomment($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            //debug_event('vainfo.class', 'Vorbis tag: ' . $tag . ' value: ' . $data[0], 5);
            switch (strtolower($tag)) {
                case 'genre':
                    // Pass the array through
                    $parsed[$tag] = $this->parseGenres($data);
                    break;
                case 'tracknumber':
                    $parsed['track'] = $data[0];
                break;
                case 'discnumber':
                    $elements             = explode('/', $data[0]);
                    $parsed['disk']       = $elements[0];
                    $parsed['totaldisks'] = $elements[1];
                break;
                case 'date':
                    $parsed['year'] = $data[0];
                break;
                case 'musicbrainz_artistid':
                    $parsed['mb_artistid'] = $data[0];
                    break;
                case 'musicbrainz_albumid':
                    $parsed['mb_albumid'] = $data[0];
                    break;
                case 'musicbrainz_albumartistid':
                    $parsed['mb_albumartistid'] = $data[0];
                    break;
                case 'musicbrainz_releasegroupid':
                    $parsed['mb_albumid_group'] = $data[0];
                    break;
                case 'musicbrainz_trackid':
                    $parsed['mb_trackid'] = $data[0];
                    break;
                case 'musicbrainz_albumtype':
                    $parsed['release_type'] = $data[0];
                    break;
                case 'unsyncedlyrics':
                case 'unsynced lyrics':
                case 'lyrics':
                    $parsed['lyrics'] = $data[0];
                    break;
                case 'originalyear':
                    $parsed['original_year'] = $data[0];
                    break;
                case 'barcode':
                    $parsed['barcode'] = $data[0];
                    break;
                case 'catalognumber':
                    $parsed['catalog_number'] = $data[0];
                    break;
                case 'rating':
                    $parsed['rating'][-1] = floor($data[0] * 5 / 100);
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
     * @param $tags
     * @return array
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
     * @param $tags
     * @return array
     * @throws Exception
     */
    private function _cleanup_id3v2($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            //debug_event('vainfo.class', 'id3v2 tag: ' . strtolower($tag) . ' value: ' . $data[0], 5);
            switch ($tag) {
                case 'genre':
                    $parsed['genre'] = $this->parseGenres($data);
                    break;
                case 'part_of_a_set':
                    $elements             = explode('/', $data[0]);
                    $parsed['disk']       = $elements[0];
                    $parsed['totaldisks'] = $elements[1];
                break;
                case 'track_number':
                    $parsed['track'] = $data[0];
                break;
                case 'comment':
                    // First array key can be xFF\xFE in case of UTF-8, better to get it this way
                    $parsed['comment'] = reset($data);
                break;
                case 'composer':
                    $BOM = chr(0xff) . chr(0xfe);
                    if (strlen($data[0]) == 2 && $data[0] == $BOM) {
                        $parsed['composer'] = str_replace($BOM, '', $data[0]);
                    } else {
                        $parsed['composer'] = reset($data);
                    }
                     break;
                case 'comments':
                    $parsed['comment'] = $data[0];
                    break;
                case 'unsynchronised_lyric':
                    $parsed['lyrics'] = $data[0];
                    break;
                case 'originalyear':
                    $parsed['original_year'] = $data[0];
                    break;
                case 'barcode':
                    $parsed['barcode'] = $data[0];
                    break;
                case 'catalognumber':
                    $parsed['catalog_number'] = $data[0];
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
                // Use trimAscii to remove noise (see #225 and #438 issues). Is this a GetID3 bug?
                // not a bug those strings are UTF-16 encoded
                // getID3 has copies of text properly converted to utf-8 encoding in comments/text
                foreach ($id3v2['TXXX'] as $txxx) {
                    switch (strtolower($this->trimAscii($txxx['description']))) {
                        case 'musicbrainz album id':
                            $parsed['mb_albumid'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                        case 'musicbrainz release group id':
                            $parsed['mb_albumid_group'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                        case 'musicbrainz artist id':
                            $parsed['mb_artistid'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                        case 'musicbrainz album artist id':
                            $parsed['mb_albumartistid'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                        case 'musicbrainz album type':
                            $parsed['release_type'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                        case 'replaygain_track_gain':
                            $parsed['replaygain_track_gain'] = floatval($txxx['data']);
                        break;
                        case 'replaygain_track_peak':
                            $parsed['replaygain_track_peak'] = floatval($txxx['data']);
                        break;
                        case 'replaygain_album_gain':
                            $parsed['replaygain_album_gain'] = floatval($txxx['data']);
                        break;
                        case 'replaygain_album_peak':
                            $parsed['replaygain_album_peak'] = floatval($txxx['data']);
                        break;
                        case 'original_year':
                            $parsed['original_year'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                        case 'barcode':
                            $parsed['barcode'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                        case 'catalognumber':
                            $parsed['catalog_number'] = $id3v2['comments']['text'][$txxx['description']];
                        break;
                    }
                }
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
                // Rating made by an unknow user, adding it to super user (id=-1)
                else {
                    $parsed['rating'][-1] = $popm['rating'] / 255 * 5;
                }
            }
        }

        return $parsed;
    }

    /**
     * _cleanup_riff
     * @param $tags
     * @return array
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
     * @param $tags
     * @return array
     */
    private function _cleanup_quicktime($tags)
    {
        $parsed = array();

        foreach ($tags as $tag => $data) {
            switch ($tag) {
                case 'creation_date':
                    $parsed['release_date'] = strtotime(str_replace(" ", "", $data[0]));
                    if (strlen($data['0']) > 4) {
                        $data[0] = date('Y', $parsed['release_date']);
                    }
                    $parsed['year'] = $data[0];
                break;
                case 'MusicBrainz Track Id':
                    $parsed['mb_trackid'] = $data[0];
                break;
                case 'MusicBrainz Album Id':
                    $parsed['mb_albumid'] = $data[0];
                break;
                case 'MusicBrainz Album Artist Id':
                    $parsed['mb_albumartistid'] = $data[0];
                break;
                case 'MusicBrainz Release Group Id':
                    $parsed['mb_albumid_group'] = $data[0];
                break;
                case 'MusicBrainz Artist Id':
                    $parsed['mb_artistid'] = $data[0];
                break;
                case 'MusicBrainz Album Type':
                $parsed['release_type'] = $data[0];
                break;
                case 'track_number':
                    $parsed['track'] = $data[0];
                break;
                case 'disc_number':
                    $parsed['disk'] = $data[0];
                break;
                case 'album_artist':
                    $parsed['albumartist'] = $data[0];
                break;
                case 'tv_episode':
                    $parsed['tvshow_episode'] = $data[0];
                    break;
                case 'tv_season':
                    $parsed['tvshow_season'] = $data[0];
                    break;
                case 'tv_show_name':
                    $parsed['tvshow'] = $data[0];
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
     * This function uses the file and directory patterns to pull out extra tag
     * information.
     *  parses TV show name variations:
     *    1. title.[date].S#[#]E#[#].ext        (Upper/lower case)
     *    2. title.[date].#[#]X#[#].ext        (both upper/lower case letters
     *    3. title.[date].Season #[#] Episode #[#].ext
     *    4. title.[date].###.ext        (maximum of 9 seasons)
     *  parse directory  path for name, season and episode numbers
     *   /TV shows/show name [(year)]/[season ]##/##.Episode.Title.ext
     *  parse movie names:
     *    title.[date].ext
     *    /movie title [(date)]/title.ext
     * @param string $filepath
     * @return array
     */
    private function _parse_filename($filepath)
    {
        $origin  = $filepath;
        $results = array();
        $file    = pathinfo($filepath, PATHINFO_FILENAME);

        if (in_array('tvshow', $this->gather_types)) {
            $season  = array();
            $episode = array();
            $tvyear  = array();
            $temp    = array();
            preg_match("~(?<=\(\[\<\{)[1|2][0-9]{3}|[1|2][0-9]{3}~", $filepath, $tvyear);
            $results['year'] = !empty($tvyear) ? (int) ($tvyear[0]) : null;

            if (preg_match("~[Ss](\d+)[Ee](\d+)~", $file, $seasonEpisode)) {
                $temp = preg_split("~(((\.|_|\s)[Ss]\d+(\.|_)*[Ee]\d+))~", $file, 2);
                preg_match("~(?<=[Ss])\d+~", $file, $season);
                preg_match("~(?<=[Ee])\d+~", $file, $episode);
            } else {
                if (preg_match("~[\_\-\.\s](\d{1,2})[xX](\d{1,2})~", $file, $seasonEpisode)) {
                    $temp = preg_split("~[\.\_\s\-\_]\d+[xX]\d{2}[\.\s\-\_]*|$~", $file);
                    preg_match("~\d+(?=[Xx])~", $file, $season);
                    preg_match("~(?<=[Xx])\d+~", $file, $episode);
                } else {
                    if (preg_match("~[S|s]eason[\_\-\.\s](\d+)[\.\-\s\_]?\s?[e|E]pisode[\s\-\.\_]?(\d+)[\.\s\-\_]?~", $file, $seasonEpisode)) {
                        $temp = preg_split("~[\.\s\-\_][S|s]eason[\s\-\.\_](\d+)[\.\s\-\_]?\s?[e|E]pisode[\s\-\.\_](\d+)([\s\-\.\_])*~", $file, 3);
                        preg_match("~(?<=[Ss]eason[\.\s\-\_])\d+~", $file, $season);
                        preg_match("~(?<=[Ee]pisode[\.\s\-\_])\d+~", $file, $episode);
                    } else {
                        if (preg_match("~[\_\-\.\s](\d)(\d\d)[\_\-\.\s]*~", $file, $seasonEpisode)) {
                            $temp       = preg_split("~[\.\s\-\_](\d)(\d\d)[\.\s\-\_]~", $file);
                            $season[0]  = $seasonEpisode[1];
                            if (preg_match("~[\_\-\.\s](\d)(\d\d)[\_\-\.\s]~", $file, $seasonEpisode)) {
                                $temp       = preg_split("~[\.\s\-\_](\d)(\d\d)[\.\s\-\_]~", $file);
                                $season[0]  = $seasonEpisode[1];
                                $episode[0] = $seasonEpisode[2];
                            }
                        }
                    }
                }
            }

            $results['tvshow_season']  = $season[0];
            $results['tvshow_episode'] = $episode[0];
            $results['tvshow']         = $this->formatVideoName($temp[0]);
            $results['original_name']  = $this->formatVideoName($temp[1]);

            // Try to identify the show information from parent folder
            if (!$results['tvshow']) {
                $folders = preg_split("~" . DIRECTORY_SEPARATOR . "~", $filepath, -1, PREG_SPLIT_NO_EMPTY);
                if ($results['tvshow_season'] && $results['tvshow_episode']) {
                    // We have season and episode, we assume parent folder is the tvshow name
                    $filetitle         = end($folders);
                    $results['tvshow'] = $this->formatVideoName($filetitle);
                } else {
                    // Or we assume each parent folder contains one missing information
                    if (preg_match('/[\/\\\\]([^\/\\\\]*)[\/\\\\]Season (\d{1,2})[\/\\\\]((E|Ep|Episode)\s?(\d{1,2})[\/\\\\])?/i', $filepath, $matches)) {
                        if ($matches != null) {
                            $results['tvshow']        = $this->formatVideoName($matches[1]);
                            $results['tvshow_season'] = $matches[2];
                            if (isset($matches[5])) {
                                $results['tvshow_episode'] = $matches[5];
                            } else {
                                //match pattern like 10.episode name.mp4
                                if (preg_match("~^(\d\d)[\_\-\.\s]?(.*)~", $file, $matches)) {
                                    $results['tvshow_episode'] = $matches[1];
                                    $results['original_name']  = $this->formatVideoName($matches[2]);
                                } else {
                                    //Fallback to match any 3-digit Season/Episode that fails the standard pattern above.
                                    preg_match("~(\d)(\d\d)[\_\-\.\s]?~", $file, $matches);
                                    $results['tvshow_episode'] = $matches[2];
                                }
                            }
                        }
                    }
                }
            }

            $results['title'] = $results['tvshow'];
        }

        if (in_array('movie', $this->gather_types)) {
            $results['original_name'] = $results['title'] = $this->formatVideoName($file);
        }

        if (in_array('music', $this->gather_types) || in_array('clip', $this->gather_types)) {
            $patres  = vainfo::parse_pattern($filepath, $this->_dir_pattern, $this->_file_pattern);
            $results = array_merge($results, $patres);
            if ($this->islocal) {
                $results['size'] = Core::get_filesize(Core::conv_lc_file($origin));
            }
        }

        return $results;
    }

    /**
     * parse_pattern
     * @param string $filepath
     * @param string $dir_pattern
     * @param string $file_pattern
     * @return array
     */
    public static function parse_pattern($filepath, $dir_pattern, $file_pattern)
    {
        $results         = array();
        $slash_type_preg = DIRECTORY_SEPARATOR;
        if ($slash_type_preg == '\\') {
            $slash_type_preg .= DIRECTORY_SEPARATOR;
        }
        // Combine the patterns
        $pattern = preg_quote($dir_pattern) . $slash_type_preg . preg_quote($file_pattern);

        // Remove first left directories from filename to match pattern
        $cntslash = substr_count($pattern, preg_quote(DIRECTORY_SEPARATOR)) + 1;
        $filepart = explode(DIRECTORY_SEPARATOR, $filepath);
        if (count($filepart) > $cntslash) {
            $filepath = implode(DIRECTORY_SEPARATOR, array_slice($filepart, count($filepart) - $cntslash));
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
        preg_match($pattern, $filepath, $matches);
        if ($matches != null) {
            // The first element is the full match text
            $matched = array_shift($matches);
            debug_event('vainfo.class', $pattern . ' matched ' . $matched . ' on ' . $filepath, 5);

            // Iterate over what we found
            foreach ($matches as $key => $value) {
                $new_key = translate_pattern_code($elements['0'][$key]);
                if ($new_key !== false) {
                    $results[$new_key] = $value;
                }
            }

            $results['title'] = $results['title'] ?: basename($filepath);
        }

        return $results;
    }

    /**
     * removeCommonAbbreviations
     * @param $name
     * @return string
     */
    private function removeCommonAbbreviations($name)
    {
        $abbr         = explode(",", AmpConfig::get('common_abbr'));
        $commonabbr   = preg_replace("~\n~", '', $abbr);
        $commonabbr[] = '[1|2][0-9]{3}';   //Remove release year
        $abbr_count   = count($commonabbr);

        //scan for brackets, braces, etc and ignore case.
        for ($count=0; $count < $abbr_count;$count++) {
            $commonabbr[$count] = "~\[*|\(*|\<*|\{*\b(?i)" . trim((string) $commonabbr[$count]) . "\b\]*|\)*|\>*|\}*~";
        }

        return preg_replace($commonabbr, '', $name);
    }

    /**
     * formatVideoName
     * @param $name
     * @return string
     */
    private function formatVideoName($name)
    {
        return ucwords(trim((string) $this->removeCommonAbbreviations(str_replace(['.', '_', '-'], ' ', $name)), "\s\t\n\r\0\x0B\.\_\-"));
    }

    /**
     * set_broken
     *
     * This fills all tag types with Unknown (Broken)
     *
     * @return array Return broken title, album, artist
     */
    public function set_broken()
    {
        /* Pull In the config option */
        $order = AmpConfig::get('tag_order');

        if (!is_array($order)) {
            $order = array($order);
        }

        $key = array_shift($order);

        $broken                 = array();
        $broken[$key]           = array();
        $broken[$key]['title']  = '**BROKEN** ' . $this->filename;
        $broken[$key]['album']  = 'Unknown (Broken)';
        $broken[$key]['artist'] = 'Unknown (Broken)';

        return $broken;
    }
    // set_broken

    /**
     *
     * @param  array     $data
     * @return array
     * @throws Exception
     */
    private function parseGenres($data)
    {
        // read additional id3v2 delimiters from config
        $delimiters = AmpConfig::get('additional_genre_delimiters');
        if (isset($data) && is_array($data) && count($data) === 1 && isset($delimiters)) {
            $pattern = '~[\s]?(' . $delimiters . ')[\s]?~';
            $genres  = preg_split($pattern, reset($data));
            if ($genres === false) {
                throw new Exception('Pattern given in additional_genre_delimiters is not functional. Please ensure is it a valid regex (delimiter ~)');
            }
            $data = $genres;
        }

        return $data;
    }
} // end vainfo.class
