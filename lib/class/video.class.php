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

class Video extends database_object implements media, library_item
{
    /**
     * @var int $id
     */
    public $id;
    /**
     * @var string $title
     */
    public $title;
    /**
     * @var boolean $played
     */
    public $played;
    /**
     * @var boolean $enabled
     */
    public $enabled;
    /**
     * @var string $file
     */
    public $file;
    /**
     * @var int $size
     */
    public $size;
    /**
     * @var string $video_codec
     */
    public $video_codec;
    /**
     * @var string $audio_codec
     */
    public $audio_codec;
    /**
     * @var int $resolution_x
     */
    public $resolution_x;
    /**
     * @var int $resolution_y
     */
    public $resolution_y;
    /**
     * @var int $time
     */
    public $time;
    /**
     * @var string $mime
     */
    public $mime;
    /**
     * @var int $release_date
     */
    public $release_date;
    /**
     * @var int $catalog
     */
    public $catalog;
    /**
     * @var int $bitrate
     */
    public $bitrate;
    /**
     * @var string $mode
     */
    public $mode;
    /**
     * @var int $channels
     */
    public $channels;
    /**
     * @var int $display_x
     */
    public $display_x;
    /**
     * @var int $display_x
     */
    public $display_y;
    /**
     * @var float $frame_rate
     */
    public $frame_rate;
    /**
     * @var int $video_bitrate
     */
    public $video_bitrate;

    /**
     * @var string $type
     */
    public $type;
    /**
     * @var array $tags
     */
    public $tags;
    /**
     * @var string $f_title
     */
    public $f_title;
    /**
     * @var string $f_full_title
     */
    public $f_full_title;
    /**
     * @var string $f_time
     */
    public $f_time;
    /**
     * @var string $f_time_h
     */
    public $f_time_h;
    /**
     * @var string $link
     */
    public $link;
    /**
     * @var string $f_link
     */
    public $f_link;
    /**
     * @var string $f_codec
     */
    public $f_codec;
    /**
     * @var string $f_resolution
     */
    public $f_resolution;
    /**
     * @var string $f_display
     */
    public $f_display;
    /**
     * @var string $f_bitrate
     */
    public $f_bitrate;
    /**
     * @var string $f_video_bitrate
     */
    public $f_video_bitrate;
    /**
     * @var string $f_frame_rate
     */
    public $f_frame_rate;
    /**
     * @var string $f_tags
     */
    public $f_tags;
    /**
     * @var string $f_length
     */
    public $f_length;
    /**
     * @var string $f_file
     */
    public $f_file;
    /**
     * @var string $f_release_date
     */
    public $f_release_date;

    /**
     * Constructor
     * This pulls the information from the database and returns
     * a constructed object
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        if (!$id) {
            return false;
        }
        
        // Load the data from the database
        $info = $this->get_info($id, 'video');
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        $data       = pathinfo($this->file);
        $this->type = strtolower($data['extension']);

        return true;
    } // Constructor

    /**
     * Create a video strongly typed object from its id.
     * @param int $video_id
     * @return \Video
     */
    public static function create_from_id($video_id)
    {
        $dtypes = self::get_derived_types();
        foreach ($dtypes as $dtype) {
            $sql        = "SELECT `id` FROM `" . strtolower($dtype) . "` WHERE `id` = ?";
            $db_results = Dba::read($sql, array($video_id));
            if ($results = Dba::fetch_assoc($db_results)) {
                if ($results['id']) {
                    return new $dtype($video_id);
                }
            }
        }

        return new Video($video_id);
    }

    /**
     * build_cache
     * Build a cache based on the array of ids passed, saves lots of little queries
     * @param int[] $ids
     */
    public static function build_cache($ids=array())
    {
        if (!is_array($ids) or !count($ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql        = "SELECT * FROM `video` WHERE `video`.`id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('video', $row['id'], $row);
        }
    } // build_cache

    /**
     * format
     * This formats a video object so that it is human readable
     */
    public function format($details = true)
    {
        $this->f_title      = scrub_out($this->title);
        $this->f_full_title = $this->f_title;
        $this->link         = AmpConfig::get('web_path') . "/video.php?action=show_video&video_id=" . $this->id;
        $this->f_link       = "<a href=\"" . $this->link . "\" title=\"" . scrub_out($this->f_title) . "\"> " . scrub_out($this->f_title) . "</a>";
        $this->f_codec      = $this->video_codec . ' / ' . $this->audio_codec;
        if ($this->resolution_x || $this->resolution_y) {
            $this->f_resolution = $this->resolution_x . 'x' . $this->resolution_y;
        }
        if ($this->display_x || $this->display_y) {
            $this->f_display = $this->display_x . 'x' . $this->display_y;
        }

        // Format the Bitrate
        $this->f_bitrate       = intval($this->bitrate / 1000) . "-" . strtoupper($this->mode);
        $this->f_video_bitrate = (string) intval($this->video_bitrate / 1000);
        if ($this->frame_rate) {
            $this->f_frame_rate = $this->frame_rate . ' fps';
        }

        // Format the Time
        $min            = floor($this->time / 60);
        $sec            = sprintf("%02d", ($this->time % 60));
        $this->f_time   = $min . ":" . $sec;
        $hour           = sprintf("%02d", floor($min / 60));
        $min_h          = sprintf("%02d", ($min % 60));
        $this->f_time_h = $hour . ":" . $min_h . ":" . $sec;

        if ($details) {
            // Get the top tags
            $this->tags   = Tag::get_top_tags('video', $this->id);
            $this->f_tags = Tag::get_display($this->tags, true, 'video');
        }

        $this->f_length = floor($this->time / 60) . ' ' . T_('minutes');
        $this->f_file   = $this->f_title . '.' . $this->type;
        if ($this->release_date) {
            $this->f_release_date = date('Y-m-d', $this->release_date);
        }
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords          = array();
        $keywords['title'] = array('important' => true,
            'label' => T_('Title'),
            'value' => $this->f_title);

        return $keywords;
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_title;
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * Get item childrens.
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        return array();
    }

    /**
     * Get all childrens and sub-childrens medias.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if (!$filter_type || $filter_type == 'video') {
            $medias[] = array(
                'object_type' => 'video',
                'object_id' => $this->id
            );
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return int[]
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }

    /**
     * Get item's owner.
     * @return int|null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * Get default art kind for this item.
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'preview';
    }

    public function get_description()
    {
        return '';
    }

    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'video') || $force) {
            Art::display('video', $this->id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * gc
     *
     * Cleans up the inherited object tables
     */
    public static function gc()
    {
        Movie::gc();
        TVShow_Episode::gc();
        TVShow_Season::gc();
        TVShow::gc();
        Personal_Video::gc();
        Clip::gc();
    }

    /**
     * Get stream types.
     * @return array
     */
    public function get_stream_types($player = null)
    {
        return Song::get_stream_types_for_type($this->type, $player);
    }

    /**
     * play_url
     * This returns a "PLAY" url for the video in question here, this currently feels a little
     * like a hack, might need to adjust it in the future
     * @param int $oid
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @return string
     */
    public static function play_url($oid, $additional_params='', $player=null, $local=false)
    {
        return Song::generic_play_url('video', $oid, $additional_params, $player, $local);
    }

    /**
     * Get stream name.
     * @return string
     */
    public function get_stream_name()
    {
        return $this->title;
    }

    /**
     * get_transcode_settings
     * @param string $target
     * @param array $options
     * @return array
     */
    public function get_transcode_settings($target = null, $player = null, $options=array())
    {
        return Song::get_transcode_settings_for_media($this->type, $target, $player, 'video', $options);
    }

    /**
     * Get derived video types.
     * @return array
     */
    private static function get_derived_types()
    {
        return array('TVShow_Episode', 'Movie', 'Clip', 'Personal_Video');
    }

    /**
     * Validate video type.
     * @param string $type
     * @return string
     */
    public static function validate_type($type)
    {
        $dtypes = self::get_derived_types();
        foreach ($dtypes as $dtype) {
            if (strtolower($type) == strtolower($dtype)) {
                return $type;
            }
        }

        return 'Video';
    }

    /**
     * type_to_mime
     *
     * Returns the mime type for the specified file extension/type
     * @param string $type
     * @return string
     */
    public static function type_to_mime($type)
    {
        // FIXME: This should really be done the other way around.
        // Store the mime type in the database, and provide a function
        // to make it a human-friendly type.
        switch ($type) {
            case 'avi':
                return 'video/avi';
            case 'ogg':
            case 'ogv':
                return 'application/ogg';
            case 'wmv':
                return 'audio/x-ms-wmv';
            case 'mp4':
            case 'm4v':
                return 'video/mp4';
            case 'mkv':
                return 'video/x-matroska';
            case 'mkv':
                return 'video/x-matroska';
            case 'mov':
                return 'video/quicktime';
            case 'divx':
                return 'video/x-divx';
            case 'webm':
                return 'video/webm';
            case 'flv':
                return 'video/x-flv';
            case 'ts':
                return 'video/mp2t';
            case 'mpg':
            case 'mpeg':
            case 'm2ts':
            default:
                return 'video/mpeg';
        }
    }

    /**
     * Insert new video.
     * @param array $data
     * @param array $gtypes
     * @param array $options
     * @return int
     */
    public static function insert(array $data, $gtypes = array(), $options = array())
    {
        $bitrate        = intval($data['bitrate']);
        $mode           = $data['mode'];
        $rezx           = intval($data['resolution_x']);
        $rezy           = intval($data['resolution_y']);
        $release_date   = intval($data['release_date']);
        // No release date, then release date = production year
        if (!$release_date && $data['year']) {
            $release_date = strtotime(strval($data['year']) . '-01-01');
        }
        $tags           = $data['genre'];
        $channels       = intval($data['channels']);
        $disx           = intval($data['display_x']);
        $disy           = intval($data['display_y']);
        $frame_rate     = floatval($data['frame_rate']);
        $video_bitrate  = intval($data['video_bitrate']);

        $sql = "INSERT INTO `video` (`file`,`catalog`,`title`,`video_codec`,`audio_codec`,`resolution_x`,`resolution_y`,`size`,`time`,`mime`,`release_date`,`addition_time`, `bitrate`, `mode`, `channels`, `display_x`, `display_y`, `frame_rate`, `video_bitrate`) " .
            " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array($data['file'], $data['catalog'], $data['title'], $data['video_codec'], $data['audio_codec'], $rezx, $rezy, $data['size'], $data['time'], $data['mime'], $release_date, time(), $bitrate, $mode, $channels, $disx, $disy, $frame_rate, $video_bitrate);
        Dba::write($sql, $params);
        $vid = Dba::insert_id();

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    Tag::add('video', $vid, $tag, false);
                }
            }
        }

        if ($data['art'] && $options['gather_art']) {
            $art = new Art($vid, 'video');
            $art->insert_url($data['art']);
        }

        $data['id'] = $vid;

        return self::insert_video_type($data, $gtypes, $options);
    }

    /**
     * Insert video for derived type.
     * @param array $data
     * @param array $gtypes
     * @param array $options
     * @return int
     */
    private static function insert_video_type(array $data, $gtypes, $options = array())
    {
        if (count($gtypes) > 0) {
            $gtype = $gtypes[0];
            switch ($gtype) {
                case 'tvshow':
                    return TVShow_Episode::insert($data, $gtypes, $options);
                case 'movie':
                    return Movie::insert($data, $gtypes, $options);
                case 'clip':
                    return Clip::insert($data, $gtypes, $options);
                case 'personal_video':
                    return Personal_Video::insert($data, $gtypes, $options);
                default:
                    // Do nothing, video entry already created and no additional data for now
                    break;
            }
        }

        return $data['id'];
    }

    /**
     * update
     * This takes a key'd array of data as input and updates a video entry
     * @param array $data
     * @return int
     */
    public function update(array $data)
    {
        if (isset($data['release_date'])) {
            $f_release_date = $data['release_date'];
            $release_date   = strtotime($f_release_date);
        } else {
            $release_date = $this->release_date;
        }
        $title = isset($data['title']) ? $data['title'] : $this->title;

        $sql = "UPDATE `video` SET `title` = ?, `release_date` = ? WHERE `id` = ?";
        Dba::write($sql, array($title, $release_date, $this->id));

        if (isset($data['edit_tags'])) {
            Tag::update_tag_list($data['edit_tags'], 'video', $this->id, true);
        }

        $this->title        = $title;
        $this->release_date = $release_date;

        return $this->id;
    } // update

    public static function update_video($video_id, Video $new_video)
    {
        $update_time = time();

        $sql = "UPDATE `video` SET `title` = ?, `bitrate` = ?, " .
            "`size` = ?, `time` = ?, `video_codec` = ?, `audio_codec` = ?, " .
            "`resolution_x` = ?, `resolution_y` = ?, `release_date` = ?, `channels` = ?, " .
            "`display_x` = ?, `display_y` = ?, `frame_rate` = ?, `video_bitrate` = ?, " .
            "`update_time` = ? WHERE `id` = ?";

        Dba::write($sql, array($new_video->title, $new_video->bitrate, $new_video->size, $new_video->time, $new_video->video_codec, $new_video->audio_codec, $new_video->resolution_x, $new_video->resolution_y, $new_video->release_date, $new_video->channels, $new_video->display_x, $new_video->display_y, $new_video->frame_rate, $new_video->video_bitrate, $update_time, $video_id));
    }

    /**
     * Get release item art.
     * @return array
     */
    public function get_release_item_art()
    {
        return array('object_type' => 'video',
            'object_id' => $this->id
        );
    }

    /*
     * generate_preview
     * Generate video preview image from a video file
     * @param int $video_id
     * @param boolean $overwrite
     */
    public static function generate_preview($video_id, $overwrite = false)
    {
        if ($overwrite || !Art::has_db($video_id, 'video', 'preview')) {
            $artp  = new Art($video_id, 'video', 'preview');
            $video = new Video($video_id);
            $image = Stream::get_image_preview($video);
            $artp->insert($image, 'image/png');
        }
    }

    /**
     * get_random
     *
     * This returns a number of random videos.
     * @param int $count
     * @return int[]
     */
    public static function get_random($count = 1)
    {
        $results = array();

        if (!$count) {
            $count = 1;
        }

        $sql   = "SELECT DISTINCT(`video`.`id`) FROM `video` ";
        $where = "WHERE `video`.`enabled` = '1' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` ";
            $where .= "AND `catalog`.`enabled` = '1' ";
        }

        $sql .= $where;
        $sql .= "ORDER BY RAND() LIMIT " . intval($count);
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param int $user
     * @param string $agent
     * @param array $location
     * @return boolean
     */
    public function set_played($user, $agent, $location)
    {
        Stats::insert('video', $this->id, $user, $agent, $location);

        if ($this->played) {
            return true;
        }

        /* If it hasn't been played, set it! */
        Video::update_played(true, $this->id);

        return true;
    } // set_played

    /**
     * compare_video_information
     * this compares the new ID3 tags of a file against
     * the ones in the database to see if they have changed
     * it returns false if nothing has changes, or the true
     * if they have. Static because it doesn't need this
     * @param \Video $video
     * @param \Video $new_video
     * @return array
     */
    public static function compare_video_information(Video $video, Video $new_video)
    {
        // Remove some stuff we don't care about
        unset($video->catalog, $video->played, $video->enabled, $video->addition_time, $video->update_time, $video->type);
        $string_array = array('title','tags');
        $skip_array   = array('id','tag_id','mime','object_cnt');

        return Song::compare_media_information($video, $new_video, $string_array, $skip_array);
    } // compare_video_information

    /**
     * get_subtitles
     * Get existing subtitles list for this video
     * @return array
     */
    public function get_subtitles()
    {
        $subtitles = array();
        $pinfo     = pathinfo($this->file);
        $filter    = $pinfo['dirname'] . DIRECTORY_SEPARATOR . $pinfo['filename'] . '*.srt';

        foreach (glob($filter) as $srt) {
            $psrt      = explode('.', $srt);
            $lang_code = '__';
            $lang_name = T_("Unknown");
            if (count($psrt) >= 2) {
                $lang_code = $psrt[count($psrt) - 2];
                if (strlen($lang_code) == 2) {
                    $lang_name = $this->get_language_name($lang_code);
                }
            }
            $subtitles[] = array(
                'file' => $pinfo['dirname'] . DIRECTORY_SEPARATOR . $srt,
                'lang_code' => $lang_code,
                'lang_name' => $lang_name
            );
        }

        return $subtitles;
    }

    /**
     * Get language name from code.
     * @param string $code
     * @return string
     */
    protected function get_language_name($code)
    {
        $languageCodes = array(
         "aa" => T_("Afar"),
         "ab" => T_("Abkhazian"),
         "ae" => T_("Avestan"),
         "af" => T_("Afrikaans"),
         "ak" => T_("Akan"),
         "am" => T_("Amharic"),
         "an" => T_("Aragonese"),
         "ar" => T_("Arabic"),
         "as" => T_("Assamese"),
         "av" => T_("Avaric"),
         "ay" => T_("Aymara"),
         "az" => T_("Azerbaijani"),
         "ba" => T_("Bashkir"),
         "be" => T_("Belarusian"),
         "bg" => T_("Bulgarian"),
         "bh" => T_("Bihari"),
         "bi" => T_("Bislama"),
         "bm" => T_("Bambara"),
         "bn" => T_("Bengali"),
         "bo" => T_("Tibetan"),
         "br" => T_("Breton"),
         "bs" => T_("Bosnian"),
         "ca" => T_("Catalan"),
         "ce" => T_("Chechen"),
         "ch" => T_("Chamorro"),
         "co" => T_("Corsican"),
         "cr" => T_("Cree"),
         "cs" => T_("Czech"),
         "cu" => T_("Church Slavic"),
         "cv" => T_("Chuvash"),
         "cy" => T_("Welsh"),
         "da" => T_("Danish"),
         "de" => T_("German"),
         "dv" => T_("Divehi"),
         "dz" => T_("Dzongkha"),
         "ee" => T_("Ewe"),
         "el" => T_("Greek"),
         "en" => T_("English"),
         "eo" => T_("Esperanto"),
         "es" => T_("Spanish"),
         "et" => T_("Estonian"),
         "eu" => T_("Basque"),
         "fa" => T_("Persian"),
         "ff" => T_("Fulah"),
         "fi" => T_("Finnish"),
         "fj" => T_("Fijian"),
         "fo" => T_("Faroese"),
         "fr" => T_("French"),
         "fy" => T_("Western Frisian"),
         "ga" => T_("Irish"),
         "gd" => T_("Scottish Gaelic"),
         "gl" => T_("Galician"),
         "gn" => T_("Guarani"),
         "gu" => T_("Gujarati"),
         "gv" => T_("Manx"),
         "ha" => T_("Hausa"),
         "he" => T_("Hebrew"),
         "hi" => T_("Hindi"),
         "ho" => T_("Hiri Motu"),
         "hr" => T_("Croatian"),
         "ht" => T_("Haitian"),
         "hu" => T_("Hungarian"),
         "hy" => T_("Armenian"),
         "hz" => T_("Herero"),
         "ia" => T_("Interlingua (International Auxiliary Language Association)"),
         "id" => T_("Indonesian"),
         "ie" => T_("Interlingue"),
         "ig" => T_("Igbo"),
         "ii" => T_("Sichuan Yi"),
         "ik" => T_("Inupiaq"),
         "io" => T_("Ido"),
         "is" => T_("Icelandic"),
         "it" => T_("Italian"),
         "iu" => T_("Inuktitut"),
         "ja" => T_("Japanese"),
         "jv" => T_("Javanese"),
         "ka" => T_("Georgian"),
         "kg" => T_("Kongo"),
         "ki" => T_("Kikuyu"),
         "kj" => T_("Kwanyama"),
         "kk" => T_("Kazakh"),
         "kl" => T_("Kalaallisut"),
         "km" => T_("Khmer"),
         "kn" => T_("Kannada"),
         "ko" => T_("Korean"),
         "kr" => T_("Kanuri"),
         "ks" => T_("Kashmiri"),
         "ku" => T_("Kurdish"),
         "kv" => T_("Komi"),
         "kw" => T_("Cornish"),
         "ky" => T_("Kirghiz"),
         "la" => T_("Latin"),
         "lb" => T_("Luxembourgish"),
         "lg" => T_("Ganda"),
         "li" => T_("Limburgish"),
         "ln" => T_("Lingala"),
         "lo" => T_("Lao"),
         "lt" => T_("Lithuanian"),
         "lu" => T_("Luba-Katanga"),
         "lv" => T_("Latvian"),
         "mg" => T_("Malagasy"),
         "mh" => T_("Marshallese"),
         "mi" => T_("Maori"),
         "mk" => T_("Macedonian"),
         "ml" => T_("Malayalam"),
         "mn" => T_("Mongolian"),
         "mr" => T_("Marathi"),
         "ms" => T_("Malay"),
         "mt" => T_("Maltese"),
         "my" => T_("Burmese"),
         "na" => T_("Nauru"),
         "nb" => T_("Norwegian Bokmal"),
         "nd" => T_("North Ndebele"),
         "ne" => T_("Nepali"),
         "ng" => T_("Ndonga"),
         "nl" => T_("Dutch"),
         "nn" => T_("Norwegian Nynorsk"),
         "no" => T_("Norwegian"),
         "nr" => T_("South Ndebele"),
         "nv" => T_("Navajo"),
         "ny" => T_("Chichewa"),
         "oc" => T_("Occitan"),
         "oj" => T_("Ojibwa"),
         "om" => T_("Oromo"),
         "or" => T_("Oriya"),
         "os" => T_("Ossetian"),
         "pa" => T_("Panjabi"),
         "pi" => T_("Pali"),
         "pl" => T_("Polish"),
         "ps" => T_("Pashto"),
         "pt" => T_("Portuguese"),
         "qu" => T_("Quechua"),
         "rm" => T_("Raeto-Romance"),
         "rn" => T_("Kirundi"),
         "ro" => T_("Romanian"),
         "ru" => T_("Russian"),
         "rw" => T_("Kinyarwanda"),
         "sa" => T_("Sanskrit"),
         "sc" => T_("Sardinian"),
         "sd" => T_("Sindhi"),
         "se" => T_("Northern Sami"),
         "sg" => T_("Sango"),
         "si" => T_("Sinhala"),
         "sk" => T_("Slovak"),
         "sl" => T_("Slovenian"),
         "sm" => T_("Samoan"),
         "sn" => T_("Shona"),
         "so" => T_("Somali"),
         "sq" => T_("Albanian"),
         "sr" => T_("Serbian"),
         "ss" => T_("Swati"),
         "st" => T_("Southern Sotho"),
         "su" => T_("Sundanese"),
         "sv" => T_("Swedish"),
         "sw" => T_("Swahili"),
         "ta" => T_("Tamil"),
         "te" => T_("Telugu"),
         "tg" => T_("Tajik"),
         "th" => T_("Thai"),
         "ti" => T_("Tigrinya"),
         "tk" => T_("Turkmen"),
         "tl" => T_("Tagalog"),
         "tn" => T_("Tswana"),
         "to" => T_("Tonga"),
         "tr" => T_("Turkish"),
         "ts" => T_("Tsonga"),
         "tt" => T_("Tatar"),
         "tw" => T_("Twi"),
         "ty" => T_("Tahitian"),
         "ug" => T_("Uighur"),
         "uk" => T_("Ukrainian"),
         "ur" => T_("Urdu"),
         "uz" => T_("Uzbek"),
         "ve" => T_("Venda"),
         "vi" => T_("Vietnamese"),
         "vo" => T_("Volapuk"),
         "wa" => T_("Walloon"),
         "wo" => T_("Wolof"),
         "xh" => T_("Xhosa"),
         "yi" => T_("Yiddish"),
         "yo" => T_("Yoruba"),
         "za" => T_("Zhuang"),
         "zh" => T_("Chinese"),
         "zu" => T_("Zulu")
        );

        return $languageCodes[$code];
    }

    /**
     * Get subtitle file from language code.
     * @param string $lang_code
     * @return string
     */
    public function get_subtitle_file($lang_code)
    {
        $subtitle = '';
        if ($lang_code == '__' || $this->get_language_name($lang_code)) {
            $pinfo    = pathinfo($this->file);
            $subtitle = $pinfo['dirname'] . DIRECTORY_SEPARATOR . $pinfo['filename'];
            if ($lang_code != '__') {
                $subtitle .= '.' . $lang_code;
            }
            $subtitle .= '.srt';
        }

        return $subtitle;
    }

    /**
     * Remove the video from disk.
     */
    public function remove_from_disk()
    {
        if (file_exists($this->file)) {
            $deleted = unlink($this->file);
        } else {
            $deleted = true;
        }
        if ($deleted === true) {
            $sql     = "DELETE FROM `video` WHERE `id` = ?";
            $deleted = Dba::write($sql, array($this->id));
            if ($deleted) {
                Art::gc('video', $this->id);
                Userflag::gc('video', $this->id);
                Rating::gc('video', $this->id);
                Shoutbox::gc('video', $this->id);
                Useractivity::gc('video', $this->id);
            }
        } else {
            debug_event('video', 'Cannot delete ' . $this->file . 'file. Please check permissions.', 1);
        }

        return $deleted;
    }

    /**
     * update_played
     * sets the played flag
     * @param boolean $new_played
     * @param int $song_id
     */
    public static function update_played($new_played, $song_id)
    {
        self::_update_item('played', ($new_played ? 1 : 0), $song_id, '25');
    } // update_played

    /**
     * _update_item
     * This is a private function that should only be called from within the video class.
     * It takes a field, value video id and level. first and foremost it checks the level
     * against $GLOBALS['user'] to make sure they are allowed to update this record
     * it then updates it and sets $this->{$field} to the new value
     * @param string $field
     * @param mixed $value
     * @param int $song_id
     * @param int $level
     * @return boolean
     */
    private static function _update_item($field, $value, $song_id, $level)
    {
        /* Check them Rights! */
        if (!Access::check('interface', $level)) {
            return false;
        }

        /* Can't update to blank */
        if (!strlen(trim($value))) {
            return false;
        }

        $sql = "UPDATE `video` SET `$field` = ? WHERE `id` = ?";
        Dba::write($sql, array($value, $song_id));

        return true;
    } // _update_item
} // end Video class
